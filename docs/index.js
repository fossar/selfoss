'use strict';

const shayu = require('shayu');

const config = {
    basePath: __dirname, // easy way to get the path where this index.js is in, all directories will be based from here
    assets: {
        postcssModules: [
            require('postcss-import')(),
            require('postcss-mixins')(),
            require('postcss-nested')(),
            require('postcss-simple-vars')(),
            require('postcss-color-function')(),
            require('autoprefixer')(),
            require('postcss-math')()
        ]
    },
    livereload: 'env'
};

shayu(config);
