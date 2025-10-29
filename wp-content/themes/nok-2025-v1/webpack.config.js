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

        'assets/js/yoast-page-parts-integration': path.resolve(
            __dirname,
            'src/yoast-page-parts-integration.js'
        ),

        'assets/js/nok-post-meta-panel': path.resolve(
            __dirname,
            'src/nok-post-meta-panel.js'
        ),

        'blocks/embed-nok-page-part/index': path.resolve(
            __dirname,
            'src/blocks/embed-nok-page-part/index.js'
        ),

        'blocks/content-placeholder-nok-template/index': path.resolve(
            __dirname,
            'src/blocks/content-placeholder-nok-template/index.js'
        ),
    },

    // 3) keep the default filename pattern
    output: {
        ...defaultConfig.output,
        filename: '[name].js',
    },
};
