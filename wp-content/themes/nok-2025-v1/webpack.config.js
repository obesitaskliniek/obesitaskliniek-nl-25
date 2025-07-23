// webpack.config.js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path          = require( 'path' );

module.exports = {
    ...defaultConfig,

    // 1) merge in all your existing block.json entries…
    entry: {
        ...defaultConfig.entry,

        'assets/js/nok-page-part-preview': path.resolve(
            __dirname,
            'src/nok-page-part-preview.js'
        ),

        'assets/js/nok-page-part-design-selector': path.resolve(
            __dirname,
            'src/nok-page-part-design-selector.js'
        ),
    },

    // 3) keep the default filename pattern
    output: {
        ...defaultConfig.output,
        filename: '[name].js',
    },
};
