# Ziphaven: Learning Guide

![CWE-22](https://img.shields.io/badge/CWE--22-Path%20Traversal-red?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-Apache-777BB4?style=flat-square&logo=php&logoColor=white)
![Docker](https://img.shields.io/badge/Lab-Docker-2496ED?style=flat-square&logo=docker&logoColor=white)

**Ziphaven is an intentionally vulnerable application built for security education.** It is a small release & download hub with a deliberate [path traversal](https://owasp.org/www-community/attacks/Path_Traversal)
(CWE-22) flaw — hidden behind a filter that inspects the filename _before_ decoding it — so you can practice finding and exploiting it in a safe and controlled environment.

<br>

> [!TIP]
> A guided, **non-spoiler** path to solving this lab. It teaches the *method*, not the answer — the exact winning payload and the flag stay in [SOLUTION.md](SOLUTION.md).

<br>

## 🧬 What path traversal is

Here is the whole idea in one sentence:

> **Path traversal happens when an app builds a file path out of user input and
> then trusts that path to stay where it's supposed to.**

A web app usually wants to serve files from *one* folder — here, `downloads/`.
The intended deal is: "give me a filename, I'll give you that artefact *from
this folder*." The catch is that the folder boundary is a **convention**, not a
**rule** the code enforces. Filesystems understand `..` as "go up one level", so
a filename like `../something` is a perfectly valid instruction to leave the
folder. If the guard inspects the *wrong form* of the input, the app obeys.

Think of `downloads/` as a room and the filename as directions.
"`ziphaven-sdk-2.9.0.zip`" means "the SDK in this room." "`../something`" means
"step out the door, then grab a file in the hallway." Ziphaven *does* check for
that this time — but it reads the directions while they're still written in
code, then *translates them to plain language afterwards*. By then it's too
late.

That "filename → file read" shape is the **sink** you're training your eye to
spot. Once you can recognise it on sight, you can find this bug in seconds.

> Want the deeper background — history, variants, encoding tricks, and the full
> manual-testing workflow with **Burp Suite** and **OWASP ZAP**? Read the shared
> **[Path Traversal field guide](https://github.com/iv0ks/appsec-labs/blob/main/docs/path-traversal.md)**.
> This lab is a hands-on application of it.

<br>

## 🎓 What this level teaches

**Level 5 — filter runs *before* decoding.** Ziphaven's endpoint rejects any
value containing a literal `../` — but only *after* that, it decodes the value
"one more time" to be lenient about encoded filenames. The order is fatal: a
percent-encoded traversal is invisible to a filter that looks at the
still-encoded text, and is turned back into `../` only once the check has
passed. The lesson is about **decode/validate ordering** and **multiple encoding
layers**.

- **Entry point:** `GET /download.php?file=<name>`
- **The broken defence:** a literal-`../` check applied *before* an extra decode.
- **Served directory:** `downloads/` · **target:** a file one level above it.

## 🪧 How to approach it

1. Download an artefact through your proxy and capture the `file` parameter.
   Re-send it for your **baseline**.
2. Probe: a plain `../` is rejected (400). A filter exists and it reads the raw
   string.
3. The key question is about *order*: when does the filter run relative to
   decoding, and what does the app do to the value **afterwards**? Remember the
   webserver already decodes the query string once. If the `../` were still
   *encoded* when the filter looked, would it see it?
4. Build an encoded payload with your proxy's encoder, send it **as-is**, and
   confirm the escape to the target above `downloads/`.

## 🕵️‍♂️ Going further

Encoding bypasses generalise far beyond traversal — they defeat naive WAF rules,
auth filters and more. Practise single- vs double-encoding and observe exactly
which component peels which layer (the webserver, then the app). The labs run as
a non-root user inside Docker, so the bug is real but the blast radius is
contained on purpose.

## 🤔 Why it matters

"Validate, then normalise" is a real and recurring bug. Internalising *decode
fully first, validate second* — and recognising how many decode layers sit
between the client and the sink — is a transferable security instinct that
applies far beyond file paths.

---

When you have captured the flag (or want to check the fix and the *why* behind
it), continue to **[SOLVE → SOLUTION.md](SOLUTION.md)**.
