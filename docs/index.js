'use strict';

const shayu = require('shayu');

const config = {
    basePath: __dirname,
    defaultMeta: {
        title: 'selfoss',
        description: 'Open source web based RSS reader and multi-source mashup aggregator.',
        baseUrl: 'https://selfoss.aditu.de',
        author: 'Tobias Zeising',
        authorAddress: 'tobias.zeising@aditu.de',
        currentVersion: '2.19-SNAPSHOT',

        layout: './page',
    },
    assets: {
        postcssModules: [
            require('postcss-import')(),
            require('autoprefixer')(),
        ],
    },
    livereload: 'env',
    HTMLcomponents: {
        a: './components/a',
        // TODO: allow sharing single component for multiple headings
        h2: './components/h2',
    },
};

shayu(config);
