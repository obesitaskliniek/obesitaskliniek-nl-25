// webpack.config.js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path          = require( 'path' );

module.exports = {
    ...defaultConfig,

    // 1) merge in all your existing block.json entries…
    entry: {
        ...defaultConfig.entry,

        // 2) …and add one more, keyed to the folder you want:
        'assets/js/nok-page-part-preview': path.resolve(
            __dirname,
            'src/nok-page-part-preview.js'
        ),
    },

    // 3) keep the default filename pattern
    output: {
        ...defaultConfig.output,
        filename: '[name].js',
    },
};
