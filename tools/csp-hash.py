#!/usr/bin/env python3
"""Compute the CSP source hash for an inline script.

The theme sets `data-theme` on <html> before first paint, which requires an
inline <script>. The Content-Security-Policy allows exactly that one script by
its hash, rather than opening the policy with 'unsafe-inline' or issuing a
per-request nonce (which would make every response unique and defeat the static
page cache).

The hash covers the exact bytes between <script> and </script>, with no
surrounding whitespace of its own. If the snippet changes by one character the
hash changes and the browser silently refuses to run it, so this script is a
build step, not a one-off.

Usage:
    python3 tools/csp-hash.py path/to/theme-switch-inline.js
"""

import base64
import hashlib
import sys
from pathlib import Path


def csp_hash(source: bytes) -> str:
    """Return the 'sha256-...' CSP source expression for these exact bytes."""
    digest = hashlib.sha256(source).digest()
    return "sha256-" + base64.b64encode(digest).decode("ascii")


def main(argv: list[str]) -> int:
    """Print the hash for the file named on the command line."""
    if len(argv) != 2:
        print(__doc__, file=sys.stderr)
        return 2

    path = Path(argv[1])
    if not path.is_file():
        print(f"not a file: {path}", file=sys.stderr)
        return 1

    source = path.read_bytes()
    if source != source.strip():
        print(
            "warning: the file has leading or trailing whitespace. The hash must "
            "cover exactly what ends up between the script tags.",
            file=sys.stderr,
        )

    print(csp_hash(source))
    print(f"({len(source)} bytes from {path})", file=sys.stderr)
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv))
