const fs = require('fs');

if (process.argv.length <= 2) {
    console.error('Usage: bump-version.js <newVersion>');
    process.exit(1);
}

const newVersion = process.argv[2];

if (newVersion.search(/^\d+\.\d+(\-SNAPSHOT|\-[0-9a-f]+)?$/) === -1) {
    console.error('newVersion argument must have the format n.m or n.m-SNAPSHOT or n.m-hash (n and m are whole numbers, hash is hex number)');
    process.exit(1);
}

const sources = [
    'package.json',
    'README.md',
    'common.php',
    'docs/api-description.json',
    '_docs/website/index.html'
];

const replacements = [
    // rule for package.json
    {
        from: /"ver": "\d+\.\d+(\-SNAPSHOT|\-[0-9a-f]+)?"/,
        to: '"ver": "' + newVersion + '"'
    },

    // rule for README.md
    {
        from: /'version', '\d+\.\d+(\-SNAPSHOT|\-[0-9a-f]+)?'/,
        to: "'version', '" + newVersion + "'"
    },

    // rule for common.php
    {
        from: /Version \d+\.\d+(\-SNAPSHOT|\-[0-9a-f]+)?/,
        to: "Version " + newVersion
    },

    // rule for docs/api-description.json
    {
        from: /"version": "\d+\.\d+(\-SNAPSHOT|\-[0-9a-f]+)?"/,
        to: '"version": "' + newVersion + '"'
    },

    // rule for website/index.html
    {
        from: /selfoss( |\-)\d+\.\d+(\-SNAPSHOT|\-[0-9a-f]+)?/g,
        to: "selfoss$1" + newVersion
    },
];

console.log(`Replacing version with ${newVersion}.`);

for (const source of sources) {
    fs.readFile(source, 'utf-8', (err, data) => {
        if (err) {
            throw err;
        }

        const newData = replacements.reduce((data, {from, to}) => data.replace(from, to), data);

        fs.writeFile(source, newData, 'utf-8', (err) => {
            if (err) {
                throw err;
            }

            console.log(`- ${source}${data === newData ? ' (not changed)' : ''}`);
        });
    });
}
