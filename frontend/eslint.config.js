import js from '@eslint/js';

export default [
    {
        ignores: [
            'node_modules/**',
            'dist/**',
            'build/**',
            'coverage/**',
            '**/*.min.js',
            'playwright-report/**',
            'test-results/**',
            'babel.config.js',
            'webpack.config.js',
            'jest.config.js'
        ]
    },
    {
        ...js.configs.recommended,
        files: ['src/**/*.js'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                fetch: 'readonly',
                URLSearchParams: 'readonly',
                FormData: 'readonly',
                localStorage: 'readonly',
                sessionStorage: 'readonly',
                alert: 'readonly',
                confirm: 'readonly',
                prompt: 'readonly',
                setTimeout: 'readonly',
                clearTimeout: 'readonly',
                setInterval: 'readonly',
                clearInterval: 'readonly',
                process: 'readonly',
                global: 'readonly'
            }
        },
        rules: {
            'indent': ['error', 2],
            'linebreak-style': ['error', 'unix'],
            'quotes': ['error', 'single'],
            'semi': ['error', 'always'],
            'no-unused-vars': 'warn',
            'no-console': 'warn',
            'prefer-const': 'error',
            'no-var': 'error',
            'eqeqeq': 'error',
            'curly': 'error'
        }
    },
    {
        ...js.configs.recommended,
        files: ['tests/**/*.js'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                fetch: 'readonly',
                URLSearchParams: 'readonly',
                FormData: 'readonly',
                localStorage: 'readonly',
                sessionStorage: 'readonly',
                alert: 'readonly',
                confirm: 'readonly',
                prompt: 'readonly',
                setTimeout: 'readonly',
                clearTimeout: 'readonly',
                setInterval: 'readonly',
                clearInterval: 'readonly',
                process: 'readonly',
                global: 'readonly',
                // Jest globals
                describe: 'readonly',
                it: 'readonly',
                test: 'readonly',
                expect: 'readonly',
                beforeAll: 'readonly',
                afterAll: 'readonly',
                beforeEach: 'readonly',
                afterEach: 'readonly',
                jest: 'readonly',
                // Playwright/E2E globals
                page: 'readonly',
                browser: 'readonly',
                context: 'readonly'
            }
        },
        rules: {
            'indent': ['error', 2],
            'linebreak-style': ['error', 'unix'],
            'quotes': ['error', 'single'],
            'semi': ['error', 'always'],
            'no-unused-vars': 'warn',
            'no-console': 'off',
            'prefer-const': 'error',
            'no-var': 'error',
            'eqeqeq': 'error',
            'curly': 'error'
        }
    }
];