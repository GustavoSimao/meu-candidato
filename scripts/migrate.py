#!/usr/bin/env python
"""Script to run alembic migrations."""

import subprocess
import sys


def run_migrations():
    result = subprocess.run(
        ["alembic", "upgrade", "head"],
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        print(f"Migration failed: {result.stderr}")
        sys.exit(1)
    print(result.stdout)


if __name__ == "__main__":
    run_migrations()
