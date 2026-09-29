import js from '@eslint/js';
import globals from 'globals';

const STATIC_MARKUP_ONLY =
    ":not([right.type='Literal']):not([right.type='Identifier'][right.name=/^[A-Z][A-Z0-9_]*$/]):not([right.type='TemplateLiteral'][right.expressions.length=0])";

export default [
    { ignores: ['webroot/js/admin/vendor/graphiql/**', 'webroot/js/admin/vendor/tiptap.bundle.mjs'] },
    js.configs.recommended,
    {
        files: ['webroot/js/**/*.{js,mjs}'],
        languageOptions: { globals: globals.browser, sourceType: 'module' },
        rules: {
            'no-console': 'error',
            'no-restricted-syntax': [
                'error',
                {
                    message: 'Select and match by data-* attribute, never by cms-* class (see .claude/rules/frontend.md).',
                    selector: 'Literal[value=/\\.cms-/]',
                },
                {
                    message:
                        'innerHTML takes only a string literal or an UPPER_CASE constant; build everything else with el(), textContent or svgIcon() (see .claude/rules/frontend.md).',
                    selector: `AssignmentExpression[left.property.name='innerHTML']${STATIC_MARKUP_ONLY}`,
                },
            ],
        },
    },
];
