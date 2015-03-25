module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        

        uglify: {
            my_target: {
                files: {
                    'public/all-v<%= pkg.ver %>.js' : [
                        'public/js/jquery-2.1.1.min.js',
                        'public/js/jquery-ui.js',
                        'public/js/jquery.mCustomScrollbar.min.js',
                        'public/js/jquery.mousewheel.min.js',
                        'public/js/lazy-image-loader.js',
                        'public/js/spectrum.js',
                        'public/js/jquery.hotkeys.js',
                        'public/js/selfoss-base.js',
                        'public/js/selfoss-events.js',
                        'public/js/selfoss-events-navigation.js',
                        'public/js/selfoss-events-search.js',
                        'public/js/selfoss-events-entries.js',
                        'public/js/selfoss-events-entriestoolbar.js',
                        'public/js/selfoss-events-sources.js',
                        'public/js/selfoss-shortcuts.js',
                        'public/js/jquery.fancybox.pack.js'
                    ]
                }
            }
        },

        cssmin: {
            options: {
                shorthandCompacting: false,
                roundingPrecision: -1
            },
            target: {
                files: {
                    'public/all-v<%= pkg.ver %>.css': [
                        'public/css/jquery.mCustomScrollbar.css',
                        'public/css/jquery.fancybox.css',
                        'public/css/spectrum.css',
                        'public/css/reset.css',
                        'public/css/style.css'
                    ]
                }
            }
        },

        watch: {
            js: {
                files: ['public/js/*.js'],
                tasks: ['js'],
                options: {
                    spawn: false,
                }
            },
            css: {
                files: ['public/css/*.css'],
                tasks: ['css'],
                options: {
                    spawn: false,
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
                    from: /'version','\d+\.\d+(\-SNAPSHOT)?'/,
                    to: ("'version','" + grunt.option('newversion') + "'")
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
                    { expand: true, cwd: 'libs/', src: ['**'], dest: '/libs'},
                    
                    // public = don't zip all.js and all.css
                    { expand: true, cwd: 'public/', src: ['**'], dest: '/public', filter: function(file) {
                        return file.indexOf('all.js') === -1 && file.indexOf('all.css') === -1;
                    }},
                    
                    // copy data: only directory structure and .htaccess for deny
                    { expand: true, cwd: 'data/', src: ['**'], dest: '/data', filter: 'isDirectory'},
                    { src: ['data/cache/.htaccess'], dest: '' },
                    { src: ['data/logs/.htaccess'], dest: '' },
                    { src: ['data/sqlite/.htaccess'], dest: '' },
                    
                    { expand: true, cwd: 'spouts/', src: ['**'], dest: '/spouts'},
                    { expand: true, cwd: 'templates/', src: ['**'], dest: '/templates'},
                    
                    { src: ['.htaccess'], dest: '' },
                    { src: ['README.md'], dest: '' },
                    { src: ['defaults.ini'], dest: '' },
                    { src: ['index.php'], dest: '' },
                    { src: ['common.php'], dest: '' },
                    { src: ['run.php'], dest: '' },
                    { src: ['update.php'], dest: '' }
                ]
            }
        }
    });

    grunt.loadNpmTasks('grunt-text-replace');
    grunt.loadNpmTasks('grunt-contrib-compress');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-watch');

    /* task checks whether newversion is given and start replacement in files if correct format is given */
    grunt.registerTask('versionupdater', 'version update task', function() {
        var version = "" + grunt.option('newversion');
        if (typeof grunt.option('newversion') != 'undefined') {
            grunt.log.writeln('replace version ' + grunt.option('newversion'));
            if (version.search(/^\d+\.\d+(\-SNAPSHOT)?$/) == -1)
                grunt.fail.warn('newversion must have the format n.m.x or n.m.x-SNAPSHOT (n, m and x are integer numbers)');
            grunt.task.run('replace');
        }
    });

    grunt.registerTask('default', ['version', 'zip']);
    grunt.registerTask('version', ['versionupdater']);
    grunt.registerTask('zip', ['assets', 'compress']);
    grunt.registerTask('js', ['uglify']);
    grunt.registerTask('css', ['cssmin']);
    grunt.registerTask('assets', ['css', 'js']);
};
