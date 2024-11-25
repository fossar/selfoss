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

// Does not currently support flat config.
// https://github.com/facebook/react/issues/28313
const eslintPluginReactHooksConfigsRecommended = {
    plugins: {
        'react-hooks': eslintPluginReactHooks,
    },
    rules: eslintPluginReactHooks.configs.recommended.rules,
};

export default [
    js.configs.recommended,
    eslintPluginReact.configs.flat.recommended,
    eslintPluginReactHooksConfigsRecommended,
    eslintConfigPrettier,
    config,
];
