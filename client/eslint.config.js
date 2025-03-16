import globals from 'globals';
import js from '@eslint/js';
import eslintConfigPrettier from 'eslint-config-prettier';
import eslintPluginReact from 'eslint-plugin-react';
import eslintPluginReactHooks from 'eslint-plugin-react-hooks';

const config = {
    languageOptions: {
        globals: {
            ...globals.browser,
            ...globals.jquery,
            selfoss: 'writable',
        },

        ecmaVersion: 'latest',
        sourceType: 'module',
    },

    settings: {
        react: {
            version: 'detect',
        },
    },

    files: ['**/*.js', '**/*.jsx'],

    rules: {
        'no-eval': 'error',
        'no-array-constructor': 'error',
        camelcase: 'error',
        'no-use-before-define': 'error',

        'react-hooks/exhaustive-deps': [
            'warn',
            {
                additionalHooks: '(useStateWithDeps)',
            },
        ],

        'unicode-bom': 'error',
    },
};

export default [
    js.configs.recommended,
    eslintPluginReact.configs.flat.recommended,
    eslintPluginReactHooks.configs['recommended-latest'],
    eslintConfigPrettier,
    config,
];
