import globals from 'globals';
import js from '@eslint/js';
import { defineConfig } from 'eslint/config';
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
        'operator-assignment': 'error',

        'react-hooks/exhaustive-deps': [
            'warn',
            {
                additionalHooks: '(useStateWithDeps)',
            },
        ],

        '@typescript-eslint/explicit-member-accessibility': 'error',

        'unicode-bom': 'error',
    },
};

export default defineConfig(
    js.configs.recommended,
    eslintPluginReact.configs.flat.recommended,
    eslintPluginReactHooks.configs.flat.recommended,
    eslintConfigPrettier,
    tseslint.configs.recommended,
    config,
);
