#!/usr/bin/env python3
"""Generate real (but tiny) decoy release artefacts for Ziphaven's downloads/.

Build-time asset tool only — NOT a runtime dependency. The download manager
serves real archives so the UI behaves like an actual release hub; placeholder
text files with a .zip/.tar.gz extension would be obviously fake and could even
break tools that open them.

Creates, under downloads/:
  - ziphaven-cli-3.4.1-linux-x64.tar.gz   (valid gzip tarball)
  - ziphaven-desktop-3.4.1-win.zip        (valid zip)
  - ziphaven-sdk-2.9.0.zip                (valid zip)

The release-notes-*.txt file is committed as-is (it is human-readable).
"""

from __future__ import annotations

import io
import os
import tarfile
import zipfile

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(os.path.dirname(HERE), "downloads")

README = (
    b"Ziphaven release artefact (lab decoy).\n"
    b"This is a real, valid archive used to make the download manager behave\n"
    b"like a genuine product. It contains no proprietary code.\n"
)


def _make_tar_gz(path: str, members: dict[str, bytes]) -> None:
    with tarfile.open(path, "w:gz") as tar:
        for name, data in members.items():
            info = tarfile.TarInfo(name)
            info.size = len(data)
            tar.addfile(info, io.BytesIO(data))


def _make_zip(path: str, members: dict[str, bytes]) -> None:
    with zipfile.ZipFile(path, "w", zipfile.ZIP_DEFLATED) as zf:
        for name, data in members.items():
            zf.writestr(name, data)


def main() -> None:
    os.makedirs(OUT, exist_ok=True)

    _make_tar_gz(
        os.path.join(OUT, "ziphaven-cli-3.4.1-linux-x64.tar.gz"),
        {
            "ziphaven/README": README,
            "ziphaven/bin/zh": b"#!/bin/sh\necho 'ziphaven cli 3.4.1'\n",
            "ziphaven/VERSION": b"3.4.1\n",
        },
    )

    _make_zip(
        os.path.join(OUT, "ziphaven-desktop-3.4.1-win.zip"),
        {
            "Ziphaven/README.txt": README,
            "Ziphaven/VERSION.txt": b"3.4.1\n",
            "Ziphaven/ziphaven.cfg": b"channel=stable\nmirror=auto\n",
        },
    )

    _make_zip(
        os.path.join(OUT, "ziphaven-sdk-2.9.0.zip"),
        {
            "sdk/README.txt": README,
            "sdk/VERSION.txt": b"2.9.0\n",
            "sdk/include/ziphaven.h": b"/* ziphaven sdk 2.9.0 */\n",
        },
    )

    for name in sorted(os.listdir(OUT)):
        full = os.path.join(OUT, name)
        print(f"{name:40s} {os.path.getsize(full):>7d} bytes")


if __name__ == "__main__":
    main()
