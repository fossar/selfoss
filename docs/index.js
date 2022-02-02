'use strict';

const shayu = require('shayu');

const config = {
    basePath: __dirname, // easy way to get the path where this index.js is in, all directories will be based from here
    defaultMeta: {
        title: 'selfoss',
        description: 'Open source web based RSS reader and multi-source mashup aggregator.',
        baseUrl: 'https://selfoss.aditu.de',
        author: 'Tobias Zeising',
        authorAddress: 'tobias.zeising@aditu.de',
        currentVersion: '2.19-SNAPSHOT',

        layout: './default',
    },
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
