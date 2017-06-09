const path = require('path');

function isNotUnimportant(dest) {
    const filename = path.basename(dest);

    const filenameDisallowed = [
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
        /^phpmd\.xml$/
    ].some(function(expr) { return expr.test(filename); });

    const destDisallowed = [
        /^vendor\/htmlawed\/htmlawed\/htmLawed(Test\.php|(.*\.(htm|txt)))$/,
        /^vendor\/smottt\/wideimage\/demo/,
        /^vendor\/simplepie\/simplepie\/(db\.sql|autoload\.php)$/,
        /^vendor\/composer\/installed\.json$/,
        /^vendor\/[^/]+\/[^/]+\/(test|doc)s?/i,
        /^vendor\/smalot\/pdfparser\/samples/,
        /^vendor\/smalot\/pdfparser\/src\/Smalot\/PdfParser\/Tests/,
    ].some(function(expr) { return expr.test(dest); });

    const allowed = !(filenameDisallowed || destDisallowed);

    return allowed;
}

module.exports = function(grunt) {
    const requiredAssets = (function() {
        const files = grunt.file.readJSON('public/package.json').extra.requiredFiles;

        return files.css.concat(files.js);
    })();

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        /* Install client-side dependencies */
        auto_install: {
            subdir: {
                options: {
                    cwd: 'public',
                    npm: '--production'
                }
            }
        },

        /* version text replace */
        replace: {
            version: {
                src: [
                    'package.json',
                    'README.md',
                    'common.php',
                    '_docs/website/index.html'
                ],
                overwrite: true,
                replacements: [
                // rule for package.json
                {
                    from: /"ver": "\d+\.\d+(\-SNAPSHOT)?"/,
                    to: ('"ver": "' + grunt.option('newversion') + '"')
                },

                // rule for README.md
                {
                    from: /'version', '\d+\.\d+(\-SNAPSHOT)?'/,
                    to: ("'version', '" + grunt.option('newversion') + "'")
                },

                // rule for common.php
                {
                    from: /Version \d+\.\d+(\-SNAPSHOT)?/,
                    to: ("Version " + grunt.option('newversion'))
                },

                // rule for website/index.html
                {
                    from: /selfoss( |\-)\d+\.\d+(\-SNAPSHOT)?/g,
                    to: ("selfoss$1" + grunt.option('newversion'))
                }]
            }
        },

        /* create zip */
        compress: {
            main: {
                options: {
                    archive: 'selfoss-<%= pkg.ver %>.zip'
                },
                files: [
                    { expand: true, cwd: 'controllers/', src: ['**'], dest: '/controllers'},
                    { expand: true, cwd: 'daos/', src: ['**'], dest: '/daos'},
                    { expand: true, cwd: 'helpers/', src: ['**'], dest: '/helpers'},
                    { expand: true, cwd: 'vendor/', src: ['**'], dest: '/vendor', filter: isNotUnimportant},

                    // do not pack bundled assets and assets not listed in index.php
                    { expand: true, cwd: 'public/', src: ['**'], dest: '/public', filter: function(file) {
                        const bundle = file === 'public/all.js' || file === 'public/all.css';
                        const thirdPartyRubbish = file.startsWith('public/node_modules/') && requiredAssets.indexOf(file) === -1;
                        const allowed = !bundle && !thirdPartyRubbish;

                        return allowed;
                    }},

                    // copy data: only directory structure and .htaccess for deny
                    { expand: true, cwd: 'data/', src: ['**'], dest: '/data', filter: 'isDirectory'},
                    { src: ['data/cache/.htaccess'], dest: '' },
                    { src: ['data/logs/.htaccess'], dest: '' },
                    { src: ['data/sqlite/.htaccess'], dest: '' },
                    { expand: true, cwd: 'data/fulltextrss', src: ['**'], dest: '/data/fulltextrss'},

                    { expand: true, cwd: 'spouts/', src: ['**'], dest: '/spouts'},
                    { expand: true, cwd: 'templates/', src: ['**'], dest: '/templates'},

                    { src: ['.htaccess'], dest: '' },
                    { src: ['README.md'], dest: '' },
                    { src: ['defaults.ini'], dest: '' },
                    { src: ['index.php'], dest: '' },
                    { src: ['common.php'], dest: '' },
                    { src: ['run.php'], dest: '' },
                    { src: ['cliupdate.php'], dest: '' }
                ]
            }
        },

        eslint: {
            target: ['public/js/selfoss-*.js']
        }
    });

    grunt.loadNpmTasks('grunt-auto-install');
    grunt.loadNpmTasks('grunt-text-replace');
    grunt.loadNpmTasks('grunt-contrib-compress');
    grunt.loadNpmTasks('grunt-composer');
    grunt.loadNpmTasks('grunt-eslint');

    /* task checks whether newversion is given and start replacement in files if correct format is given */
    grunt.registerTask('versionupdater', 'version update task', function() {
        var version = "" + grunt.option('newversion');
        if (typeof grunt.option('newversion') != 'undefined') {
            grunt.log.writeln('replace version ' + grunt.option('newversion'));
            if (version.search(/^\d+\.\d+(\-SNAPSHOT)?$/) == -1)
                grunt.fail.warn('newversion must have the format n.m or n.m-SNAPSHOT (n and m are integer numbers)');
            grunt.task.run('replace');
        }
    });

    grunt.registerTask('client:install', 'Install client-side dependencies.', ['auto_install']);
    grunt.registerTask('server:install', 'Install server-side dependencies.', ['composer:install:no-dev:optimize-autoloader:prefer-dist']);
    grunt.registerTask('install', 'Install both client-side and server-side dependencies.', ['client:install', 'server:install']);
    grunt.registerTask('default', ['install', 'versionupdater', 'compress']);
    grunt.registerTask('version', ['versionupdater']);
    grunt.registerTask('zip', ['compress']);
    grunt.registerTask('lint:client', 'Check JS syntax', ['eslint']);
    grunt.registerTask('cs:server', 'Check PHP coding style', ['composer:run-script cs']);
    grunt.registerTask('lint:server', 'Check PHP syntax', ['composer:run-script lint']);
    grunt.registerTask('check:server', 'Check PHP source code for problems and style violation', ['lint:server', 'cs:server']);
    grunt.registerTask('check', 'Check the whole source code for problems and style violation', ['lint:client', 'check:server']);
};
