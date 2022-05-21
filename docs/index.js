'use strict';

import shayu from 'shayu';
import path from 'path';
import { fileURLToPath } from 'url';

import postcssImport from 'postcss-import';
import autoprefixer from 'postcss-import';

const config = {
    basePath: path.dirname(fileURLToPath(import.meta.url)),
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
            postcssImport(),
            autoprefixer(),
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
