module.exports = {
  extends: [
    'stylelint-config-standard',
    'stylelint-config-standard-typed-css',
  ],
  plugins: [
    'stylelint-no-unused-selectors',
    'stylelint-order',
    'stylelint-scss',
  ],
  rules: {
    'plugin/no-unused-selectors': true,
    'order/order': [
      'custom-properties',
      'declarations',
      'rules',
      'at-rules',
    ],
    'scss/at-rule-no-unknown': true,
    'order/properties-alphabetical-order': true,
  }
};
