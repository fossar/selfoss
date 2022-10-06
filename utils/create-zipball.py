#!/usr/bin/env python3
import json
import logging
import os
import re
import subprocess
import tempfile
import zipfile
from pathlib import Path

logger = logging.getLogger('create-zipfile')

DISALLOWED_FILENAME_PATTERNS = list(map(re.compile, [
    r'^\.git(hub|ignore|attributes|keep)$',
    r'^\.travis\.yml$',
    r'^\.editorconfig$',
    r'(?i)^changelog',
    r'(?i)^contributing',
    r'(?i)^upgrading',
    r'(?i)^copying',
    r'(?i)^readme',
    r'(?i)^licen[cs]e',
    r'(?i)^version',
    r'^phpunit',
    r'^l?gpl\.txt$',
    r'^composer\.(json|lock)$',
    r'^Makefile$',
    r'^build\.xml$',
    r'^phpcs-ruleset\.xml$',
    r'^\.php_cs$',
    r'^phpmd\.xml$',
]))

DISALLOWED_DEST_PATTERNS = list(map(re.compile, [
    r'^vendor/htmlawed/htmlawed/htmLawed(Test\.php|(.*\.(htm|txt)))$',
    r'^vendor/smalot/pdfparser/\.atoum\.php$',
    r'^vendor/smottt/wideimage/demo',
    r'^vendor/simplepie/simplepie/(db\.sql|autoload\.php)$',
    r'^vendor/composer/installed\.json$',
    r'(?i)^vendor/[^/]+/[^/]+/(test|doc)s?',
    r'^vendor/[^/]+/[^/]+/\.git(/|$)',
    r'^vendor/smalot/pdfparser/samples',
    r'^vendor/smalot/pdfparser/src/Smalot/PdfParser/Tests',
]))

def is_not_unimportant(dest: Path) -> bool:
    filename = dest.name

    filename_disallowed = any([expr.match(filename) for expr in DISALLOWED_FILENAME_PATTERNS])

    dest_disallowed = any([expr.match(str(dest)) for expr in DISALLOWED_DEST_PATTERNS])

    allowed = not (filename_disallowed or dest_disallowed)

    return allowed

class ZipFile(zipfile.ZipFile):
    def directory(self, name, allowed=None):
        if allowed is None:
            allowed = lambda item: True

        for root, dirs, files in os.walk(name):
            root = Path(root)

            for directory in dirs:
                directory = Path(directory)
                path = root / directory

                if allowed(path):
                    # Directories are empty files whose path ends with a slash.
                    # https://mail.python.org/pipermail/python-list/2003-June/205859.html
                    self.writestr(str(self.prefix / path) + '/', '')

            for file in files:
                path = root / file

                if allowed(path):
                    self.write(path, self.prefix / path)
    def file(self, name):
        self.write(name, self.prefix / name)

def main():
    source_dir = Path.cwd()
    with tempfile.TemporaryDirectory(prefix='selfoss-dist-') as temp_dir:
        dirty = subprocess.run(['git','-C', source_dir, 'diff-index', '--quiet', 'HEAD']).returncode == 1
        if dirty:
            logger.warning('Repository contains uncommitted changes that will not be included in the dist archive.')

        logger.info('Cloning the repository into a temporary directory…')
        subprocess.check_call(['git', 'clone', '--shared', source_dir, temp_dir])

        os.chdir(temp_dir)

        with open('package.json', encoding='utf-8') as package_json:
            pkg = json.load(package_json)

        version = pkg['ver']

        # Tagged releases will be bumped by a developer to version not including -SNAPSHOT suffix and we do not need to include the commit.
        if 'SNAPSHOT' in version:
            logger.info('Inserting commit hash into version numbers…')
            short_commit = subprocess.check_output(['git', 'rev-parse', '--short', 'HEAD'], encoding='utf-8').strip()
            version = version.replace('SNAPSHOT', short_commit)
            subprocess.check_call(['npm', 'run', 'bump-version', version])

        logger.info('Installing dependencies…')
        subprocess.check_call(['npm', 'run', 'install-dependencies'])

        logger.info('Building asset bundles…')
        subprocess.check_call(['npm', 'run', 'build'])

        logger.info('Generating config-example.ini…')
        subprocess.check_call(['php', 'utils/generate-config-example.php'])

        logger.info('Optimizing PHP dependencies…')
        subprocess.check_call(['composer', 'install', '--no-dev', '--optimize-autoloader'])

        filename = 'selfoss-{}.zip'.format(version)

        # fill archive with data
        with ZipFile(source_dir / filename, 'w', zipfile.ZIP_DEFLATED) as archive:
            archive.prefix = Path('selfoss')

            archive.directory('src/')
            archive.directory('vendor/', is_not_unimportant)

            # pack all bundles and bundled assets
            archive.directory('public/')

            # copy data: only directory structure and .htaccess for deny
            archive.directory('data/', lambda file: file.is_dir())
            archive.file('data/cache/.htaccess')
            archive.file('data/logs/.htaccess')
            archive.file('data/sqlite/.htaccess')
            archive.directory('data/fulltextrss')

            archive.file('.htaccess')
            archive.file('.nginx.conf')
            archive.file('README.md')
            archive.file('config-example.ini')
            archive.file('index.php')
            archive.file('run.php')
            archive.file('cliupdate.php')

            logger.info('Zipball ‘{}’ was successfully generated.'.format(filename))

if __name__ == '__main__':
    main()
