import globals from 'globals';
import js from '@eslint/js';
import eslintConfigPrettier from 'eslint-config-prettier';
import eslintPluginReact from 'eslint-plugin-react';
import eslintPluginReactHooks from 'eslint-plugin-react-hooks';
import tseslint from 'typescript-eslint';

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

    files: ['**/*.ts', '**/*.tsx', '**/*.js'],

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

export default tseslint.config(
    js.configs.recommended,
    eslintPluginReact.configs.flat.recommended,
    eslintPluginReactHooks.configs.flat.recommended,
    eslintConfigPrettier,
    tseslint.configs.recommended,
    config,
);
