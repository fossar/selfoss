#!/usr/bin/env python3
import json
import logging
import os
import re
import subprocess
import tempfile
import zipfile
from pathlib import Path
from typing import Callable


DISALLOWED_FILENAME_PATTERNS = [
    re.compile(pattern)
    for pattern in [
        r"^\.git(hub|ignore|attributes|keep)$",
        r"^\.travis\.yml$",
        r"^\.editorconfig$",
        r"(?i)^changelog",
        r"(?i)^contributing",
        r"(?i)^upgrading",
        r"(?i)^copying",
        r"(?i)^readme",
        r"(?i)^licen[cs]e",
        r"(?i)^version",
        r"^phpunit",
        r"^l?gpl\.txt$",
        r"^composer\.(json|lock)$",
        r"^Makefile$",
        r"^build\.xml$",
        r"^phpcs-ruleset\.xml$",
        r"^\.php_cs$",
        r"^phpmd\.xml$",
    ]
]

DISALLOWED_DEST_PATTERNS = [
    re.compile(pattern)
    for pattern in [
        r"^vendor/htmlawed/htmlawed/htmLawed(Test\.php|(.*\.(htm|txt)))$",
        r"^vendor/smalot/pdfparser/\.atoum\.php$",
        r"^vendor/smottt/wideimage/demo",
        r"^vendor/simplepie/simplepie/(db\.sql|autoload\.php)$",
        r"^vendor/simplepie/simplepie/library$",
        r"^vendor/composer/installed\.json$",
        r"(?i)^vendor/[^/]+/[^/]+/(test|doc)s?",
        r"^vendor/smalot/pdfparser/samples",
        r"^vendor/smalot/pdfparser/src/Smalot/PdfParser/Tests",
    ]
]


def is_not_unimportant(dest: Path) -> bool:
    filename = dest.name

    filename_disallowed = any(r.match(filename) for r in DISALLOWED_FILENAME_PATTERNS)

    dest_disallowed = any(r.match(str(dest)) for r in DISALLOWED_DEST_PATTERNS)

    allowed = not (filename_disallowed or dest_disallowed)

    return allowed


class ZipFile(zipfile.ZipFile):
    def create_directory_entry(self, path: str) -> None:
        # Directories are empty files whose path ends with a slash.
        # https://mail.python.org/pipermail/python-list/2003-June/205859.html
        self.writestr(str(self.prefix / path) + "/", "")

    def directory(
        self,
        name: str,
        allowed: Callable[[Path], bool] = lambda item: True,
    ) -> None:
        self.create_directory_entry(name)

        for _root, dirs, files in os.walk(name):
            root = Path(_root)

            if not allowed(root):
                # Do not traverse child directories.
                dirs.clear()
                continue

            for directory in dirs:
                path = root / directory

                if allowed(path):
                    self.create_directory_entry(str(path))

            for file in files:
                path = root / file

                if allowed(path):
                    self.write(path, self.prefix / path)

    def file(self, name: str) -> None:
        self.write(name, self.prefix / name)


def is_repo_dirty(source_dir: Path) -> bool:
    p = subprocess.run(["git", "-C", source_dir, "diff-index", "--quiet", "HEAD"])
    return p.returncode == 1


def clone_repo_to(source_dir: Path, target_dir: Path) -> None:
    subprocess.check_call(["git", "clone", "--shared", source_dir, target_dir])


def get_short_commit_id() -> str:
    return subprocess.check_output(
        ["git", "rev-parse", "--short", "HEAD"],
        encoding="utf-8",
    ).strip()


def main() -> None:
    logging.basicConfig(level=logging.DEBUG)
    logger = logging.getLogger("create-zipfile")

    source_dir = Path.cwd()
    with tempfile.TemporaryDirectory(prefix="selfoss-dist-") as _temp_dir:
        temp_dir = Path(_temp_dir)

        if is_repo_dirty(source_dir):
            logger.warning(
                "Repository contains uncommitted changes that will not be included in the dist archive."
            )

        logger.info("Cloning the repository into a temporary directory…")
        clone_repo_to(source_dir, temp_dir)

        os.chdir(temp_dir)

        with open("package.json", encoding="utf-8") as package_json:
            pkg = json.load(package_json)

        version = pkg["ver"]

        # Tagged releases will be bumped by a developer to version not including -SNAPSHOT suffix and we do not need to include the commit.
        if "SNAPSHOT" in version:
            logger.info("Inserting commit hash into version numbers…")
            version = version.replace("SNAPSHOT", get_short_commit_id())
            subprocess.check_call(["npm", "run", "bump-version", version])

        logger.info("Installing dependencies…")
        subprocess.check_call(["npm", "run", "install-dependencies"])

        logger.info("Building asset bundles…")
        subprocess.check_call(["npm", "run", "build"])

        logger.info("Generating config-example.ini…")
        subprocess.check_call(["php", "utils/generate-config-example.php"])

        logger.info("Optimizing PHP dependencies…")
        subprocess.check_call(
            ["composer", "install", "--no-dev", "--optimize-autoloader"]
        )

        filename = f"selfoss-{version}.zip"

        # Fill archive with data.
        with ZipFile(
            source_dir / filename,
            mode="w",
            compression=zipfile.ZIP_DEFLATED,
            strict_timestamps=False,
        ) as archive:
            archive.prefix = Path("selfoss")

            archive.create_directory_entry("")

            archive.directory("src/")
            archive.directory("vendor/", is_not_unimportant)

            # Pack all bundles and bundled client assets.
            archive.directory("public/")

            # Copy data directory structure and .htaccess for deny.
            archive.directory("data/")

            archive.file(".htaccess")
            archive.file(".nginx.conf")
            archive.file("README.md")
            archive.file("config-example.ini")
            archive.file("index.php")
            archive.file("run.php")
            archive.file("cliupdate.php")

            logger.info(f"Zipball ‘{filename}’ was successfully generated.")


if __name__ == "__main__":
    main()
