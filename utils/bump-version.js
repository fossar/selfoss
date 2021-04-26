#!/usr/bin/env node
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
    'src/common.php',
    'docs/config.toml'
];

const replacements = [
    // rule for package.json
    {
        from: /"ver": "\d+\.\d+(\-SNAPSHOT|\-[0-9a-f]+)?"/,
        to: '"ver": "' + newVersion + '"'
    },

    // rule for README.md
    {
        from: /# selfoss \d+\.\d+(\-SNAPSHOT|\-[0-9a-f]+)?/,
        to: "# selfoss " + newVersion
    },

    // rule for src/common.php
    {
        from: /SELFOSS_VERSION = '\d+\.\d+(\-SNAPSHOT|\-[0-9a-f]+)?'/,
        to: "SELFOSS_VERSION = '" + newVersion + "'"
    },

    // rule for docs/config.toml
    {
        from: /current_version = "\d+\.\d+(\-SNAPSHOT|\-[0-9a-f]+)?"/g,
        to: `current_version = "${newVersion}"`
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
