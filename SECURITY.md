# Security Policy

## This project is intentionally vulnerable

Ziphaven is an **educational security lab**. It contains a **deliberate**
path traversal (CWE-22) vulnerability: the download endpoint checks the
requested filename for `../` **before** applying an extra `urldecode()`, so a
percent-encoded traversal sequence (e.g. `%2e%2e%2f`, or doubly-encoded
`%252e%252e%252f`) slips past the check and is turned back into `../`
afterwards. Please do **not** report the intended path traversal flaw as a
security issue — it is the entire point of the project and is documented in the
README and SOLUTION.md.

## Reporting *unintended* issues

If you find a problem that is **not** the intended lab vulnerability — for
example, a supply-chain issue, a problem in the CI/CD configuration, or a way
the container could harm the host — please open a private report via GitHub
Security Advisories, or open an issue describing the concern.

## Safe usage

- Run this only on systems you own, locally, isolated (Docker is recommended).
- Never expose this application to the public internet.
- Never use the techniques demonstrated here against systems without explicit,
  written authorization.
