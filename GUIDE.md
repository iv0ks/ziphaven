# Ziphaven — Learning Guide

> A guided, **non-spoiler** path to solving this lab. It teaches the *method*,
> not the answer — the exact winning payload and the flag stay in
> [SOLUTION.md](SOLUTION.md).

## Before you start

Read the shared **[Path Traversal field guide](https://github.com/iv0ks/appsec-labs/blob/main/docs/path-traversal.md)**
first. It covers what path traversal (CWE-22) is, why it appears, and the manual
testing workflow with **Burp Suite** and **OWASP ZAP**. This guide assumes it.

## What this level teaches

**Level 5 — filter runs *before* decoding.** Ziphaven's download endpoint
rejects any value that contains a literal `../` — but only *after* that, it
decodes the value "one more time" to be lenient about encoded filenames. The
order is fatal: a percent-encoded traversal is invisible to a filter that looks
at the still-encoded text, and is turned back into `../` only once the check has
passed. The lesson is about **decode/validate ordering** and **multiple encoding
layers**.

- **Entry point:** `GET /download.php?file=<name>`
- **The broken defence:** a literal-`../` check applied *before* an extra
  `urldecode()`.
- **Served directory:** `downloads/` · **target:** a file one level above it.

## How to approach it

1. Download a release artefact through your proxy and capture the `file`
   parameter.
2. Baseline it, then try a plain `../` — it is rejected. A filter exists and it
   reads the raw string.
3. The key question: *when* does the filter run relative to decoding, and what
   does the app do to the value **afterwards**? Remember the webserver already
   decodes the query string once. To keep a `../` hidden from the filter but
   alive for the app's extra decode, how many times must you encode it?
4. Use your proxy's encoder (Burp **Decoder**, ZAP **Encoder/Decoder**) to build
   the encoded payload, send it as-is, and confirm the escape.

## Going further

Encoding bypasses generalise far beyond traversal (they defeat naive WAF rules,
auth filters and more). Practise single- vs double-encoding and observe exactly
which layer each component peels.

## Why it matters

"Validate, then normalise" is a real and recurring bug. Internalising *decode
fully first, validate second* — and recognising how many decode layers sit
between the client and the sink — is a transferable security instinct.

When you have captured the flag (or want to check the fix), read
[SOLUTION.md](SOLUTION.md).
