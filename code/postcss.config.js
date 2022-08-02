module.exports = {
  plugins: [
    require('postcss-import'),
    require('postcss-partial-import')({ prefix: '_' }),
    require('postcss-color-function'),
    require('postcss-color-mod-function'),
    require('postcss-custom-media'),
    require('postcss-discard-comments'),
    require('postcss-nested'),
    require('postcss-each'),
    require('postcss-each-variables')
  ],
}