const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

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

const requiredAssets = (function() {
    const files = JSON.parse(fs.readFileSync('public/package.json', 'utf-8')).extra.requiredFiles;

    return files.css.concat(files.js);
})();


const pkg = JSON.parse(fs.readFileSync('package.json', 'utf-8'));

var output = fs.createWriteStream(`selfoss-${pkg.ver}.zip`);
var archive = archiver('zip');
archive.pipe(output);


// fill archive with data

archive.directory('controllers/', '/controllers');
archive.directory('daos/', '/daos');
archive.directory('helpers/', '/helpers');
archive.directory('vendor/', '/vendor', filterEntry(isNotUnimportant));

// do not pack bundled assets and assets not listed in index.php
archive.directory('public/', '/public', filterEntry(file => {
    const bundle = file === 'public/all.js' || file === 'public/all.css';
    const thirdPartyRubbish = file.startsWith('public/node_modules/') && requiredAssets.indexOf(file) === -1;
    const allowed = !bundle && !thirdPartyRubbish;

    return allowed;
}));

// copy data: only directory structure and .htaccess for deny
archive.directory('data/', '/data', filterEntry(file => fs.lstatSync(file).isDirectory()));
archive.file('data/cache/.htaccess');
archive.file('data/logs/.htaccess');
archive.file('data/sqlite/.htaccess');
archive.directory('data/fulltextrss', '/data/fulltextrss');

archive.directory('spouts/', '/spouts');
archive.directory('templates/', '/templates');

archive.file('.htaccess');
archive.file('README.md');
archive.file('defaults.ini');
archive.file('index.php');
archive.file('common.php');
archive.file('run.php');
archive.file('cliupdate.php');

archive.finalize();
