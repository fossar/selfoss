'use strict';

const { spawnSync } = require('child_process');
const fs = require('fs');
const os = require('os');
const path = require('path');
const archiver = require('archiver');

function runChecked(command, ...params) {
    const spawned = spawnSync(command, params, {stdio: 'inherit'});

    if (spawned.status != 0) {
        process.exit(spawned.status);
    }
}

function filterEntry(fn) {
    return (entry) => {
        const obj = path.join(entry.prefix, entry.name).replace(/^\//, '');

        return fn(obj) ? entry : false;
    }
}

function isNotUnimportant(dest) {
    const filename = path.basename(dest);

    const filenameDisallowed = [
        /^\.git(ignore|attributes|keep)$/,
        /^\.travis\.yml$/,
        /^\.editorconfig$/,
        /^changelog/i,
        /^contributing/i,
        /^upgrading/i,
        /^copying/i,
        /^readme/i,
        /^licen[cs]e/i,
        /^version/i,
        /^phpunit/,
        /^l?gpl\.txt$/,
        /^composer\.(json|lock)$/,
        /^Makefile$/,
        /^build\.xml$/,
        /^phpcs-ruleset\.xml$/,
        /^\.php_cs$/,
        /^phpmd\.xml$/
    ].some(expr => expr.test(filename));

    const destDisallowed = [
        /^vendor\/htmlawed\/htmlawed\/htmLawed(Test\.php|(.*\.(htm|txt)))$/,
        /^vendor\/smalot\/pdfparser\/\.atoum\.php$/,
        /^vendor\/smottt\/wideimage\/demo/,
        /^vendor\/simplepie\/simplepie\/(db\.sql|autoload\.php)$/,
        /^vendor\/composer\/installed\.json$/,
        /^vendor\/[^/]+\/[^/]+\/(test|doc)s?/i,
        /^vendor\/[^/]+\/[^/]+\/\.git(\/|$)/,
        /^vendor\/smalot\/pdfparser\/samples/,
        /^vendor\/smalot\/pdfparser\/src\/Smalot\/PdfParser\/Tests/,
    ].some(expr => expr.test(dest));

    const allowed = !(filenameDisallowed || destDisallowed);

    return allowed;
}

const sourceDir = process.cwd();
const tempDir = fs.mkdtempSync(path.join(os.tmpdir(), 'selfoss-dist-'));

const dirty = spawnSync('git', ['-C', sourceDir, 'diff-index', '--quiet', 'HEAD']).status === 1;
if (dirty) {
    console.warn('Repository contains uncommitted changes that will not be included in the dist archive.');
}

console.info('Cloning the repository into a temporary directory…');
runChecked('git', 'clone', '--shared', sourceDir, tempDir);

process.chdir(tempDir);

console.info('Installing dependencies…');
runChecked('npm', 'run', 'install-dependencies');

console.info('Building asset bundles…');
runChecked('npm', 'run', 'build');

const pkg = JSON.parse(fs.readFileSync('package.json', 'utf-8'));

const filename = `selfoss-${pkg.ver}.zip`;
var output = fs.createWriteStream(path.join(sourceDir, filename));
var archive = archiver('zip');
archive.pipe(output);


// fill archive with data

// we only care for locale assets now, since those are still used by backend code
archive.directory('assets/locale/', '/assets/locale');

archive.directory('src/', '/src');
archive.directory('vendor/', '/vendor', filterEntry(isNotUnimportant));

// pack all bundles and bundled assets
archive.directory('public/', '/public');

// copy data: only directory structure and .htaccess for deny
archive.directory('data/', '/data', filterEntry(file => fs.lstatSync(file).isDirectory()));
archive.file('data/cache/.htaccess');
archive.file('data/logs/.htaccess');
archive.file('data/sqlite/.htaccess');
archive.directory('data/fulltextrss', '/data/fulltextrss');

archive.file('.htaccess');
archive.file('README.md');
archive.file('defaults.ini');
archive.file('index.php');
archive.file('run.php');
archive.file('cliupdate.php');

archive.finalize().then(() => {
    // clean up
    runChecked('rm', '-rf', tempDir);

    console.info(`Zipball ‘${filename}’ was successfully generated.`);
});
