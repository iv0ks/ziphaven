# Ziphaven

![CWE-22](https://img.shields.io/badge/CWE--22-Path%20Traversal-red?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-Apache-777BB4?style=flat-square&logo=php&logoColor=white)
![Docker](https://img.shields.io/badge/Lab-Docker-2496ED?style=flat-square&logo=docker&logoColor=white)
[![GHCR](https://img.shields.io/badge/ghcr.io-iv0ks%2Fziphaven-f59e0b?logo=docker)](https://github.com/iv0ks/ziphaven/pkgs/container/ziphaven)

**Ziphaven is an intentionally vulnerable application built for security education.** It is a small release & download hub with a deliberate [path traversal](https://owasp.org/www-community/attacks/Path_Traversal)
(CWE-22) flaw — hidden behind a filter that inspects the filename _before_ decoding it — so you can practice finding and exploiting it in a safe and controlled environment.

<br>

<div align="center">

| 🛡️ Vulnerability | 🎯 Difficulty | 🛠️ Stack | 🚩 Entry point |
| :--- | :--- | :--- | :--- |
| Path Traversal (CWE-22) | ⭐⭐⭐⭐ Level 5 (Filter before decode) | PHP · Apache | `GET /download.php?file=<name>` |

</div>

<br>

> [!NOTE]
> The guided, spoiler-free path is [GUIDE.md](GUIDE.md); the full answer waits in [SOLUTION.md](SOLUTION.md) whenever you want it.

<br>

## 📂 Project layout

The repository is organised so the vulnerable code is easy to find and reason
about. Three areas, clearly separated: the **application** (PHP) with its public
front controllers in `public/` and its logic in `src/`, the **served data** in
`downloads/` (the directory the app is _meant_ to expose), and the **tooling**
around it (Docker image with Apache + mod_php, CI/CD, linters). The challenge
flag is deliberately kept _out_ of both the repo and the served directory — see
the note under the tree.

```
ziphaven/
├── public/                 # web root (DocumentRoot, served by Apache)
│   ├── index.php           # downloads list (home)
│   ├── download.php        # the VULNERABLE download endpoint
│   ├── error.php           # styled 404 (Apache ErrorDocument)
│   ├── style.css           # download-hub UI styles
│   └── favicon.svg
├── src/                    # application logic (PSR-4 Ziphaven\)
│   ├── Download.php        # the filter-before-decode bug (lives here)
│   ├── Config.php          # paths resolved from DATA_ROOT
│   ├── Files.php           # demo release catalogue
│   └── View.php            # layout / rendering helpers
├── downloads/              # served release files (the "allowed" directory)
├── tests/                  # PHPUnit tests (assert the traversal)
├── tools/                  # build-time asset generator + traefik config
│   ├── generate_downloads.py  # builds real tar.gz/zip decoy artefacts
│   └── traefik/            # reverse-proxy config (static + dynamic routing)
├── SOLUTION.md             # exploit writeup + the fix (spoilers)
├── SECURITY.md             # security policy (it's a lab — read before reporting)
├── .github/workflows/      # integration · delivery · security · release
├── Dockerfile              # single image: php:8.3-apache (mod_php)
├── docker-compose.yml      # ziphaven + traefik stack
├── composer.json           # autoload + dev tools (phpunit, php-cs-fixer, phpstan)
├── phpstan.neon · .php-cs-fixer.php · phpunit.xml
└── .env.example            # Traefik ACME credentials template (copy to .env)
```

The PHP code lives in `public/` + `src/` and reads its files from
`downloads/`, just like a real release hub would. The target `flag.txt` is
**not** in the repo: the Docker image writes it to `/app/flag.txt` at build
time — _outside_ the served `downloads/` dir — so traversal can reach it while
the flag stays out of version control.

<br>

## 🚀 The Lab

Ziphaven runs as a Docker Compose stack:

- **`ziphaven`** — the download manager: a single `php:8.3-apache` image where
  Apache serves static files and executes the PHP directly. Published on `8100`.
- **`traefik`** — a reverse proxy that serves the app over HTTPS on a real
  hostname. Optional: only needed for the domain-based access.

Because the stack attaches to an **external** Docker network (`iv0ks-dev`),
create it once before the first run:

```bash
docker network create iv0ks-dev
```

<br>

#### Option A — just the app

Start only the `ziphaven` service (Compose skips `traefik` when you name a
service explicitly):

```bash
docker compose up -d --build ziphaven
```

The download hub is now reachable directly at:

- **<http://localhost:8100>**

That is all you need to start playing with Burp Suite.

<br>

#### Option B — full stack with Traefik (HTTPS + hostname)

Bring up everything to also expose the app through Traefik with TLS:

```bash
docker compose up -d --build
```

Now the _same_ app is reachable two ways at once:

- **<http://localhost:8100>** — direct, no TLS (handy for quick testing).
- **<https://ziphaven.iv0ks.dev>** — proxied by Traefik with a real certificate.

Traefik routing is defined in `tools/traefik/`. TLS uses the ACME DNS-01
challenge via Namecheap, so Traefik needs those API credentials — copy
`.env.example` to `.env` and fill them in (the `.env` file is gitignored).

<br>

> [!CAUTION]
> The `ziphaven.iv0ks.dev` hostname must resolve to the host running the stack.
> For purely local experiments, Option A (localhost) is simpler and needs no
> DNS or certificates.

<br>

#### Developing without Docker

For quick frontend iteration you can use PHP's built-in server (the flag is
seeded locally and gitignored):

```bash
printf 'FLAG{ZIPHAVEN_UNZIPPED}\n' > flag.txt
DATA_ROOT="$PWD" php -S 127.0.0.1:8100 -t public
```

<br>

## 🎯 The Challenge

### The Scenario

Ziphaven is a release & download hub: it serves build artefacts from a
`downloads/` folder. This developer *knows* about traversal and rejects any
filename containing `../` — but runs that check **before** an extra decode step.
You're going to prove that validating before you decode is the same as not
validating at all.

### Your objective

Read a file that lives _outside_ the downloads directory — specifically, capture the flag in `flag.txt`.

> [!NOTE]
> The flag has the format `FLAG{...}`.

### The rules

Everything happens over HTTP against the running app — no need to
read the source to win (though you can). The flag is **not** in the repo: it's
baked into the container, outside `downloads/`, so you have to actually reach
it.

> [!TIP]
> For the full method, the **[Learning Guide](GUIDE.md)** walks you through it.
