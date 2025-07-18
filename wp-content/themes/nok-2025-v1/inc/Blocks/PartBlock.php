<?php
namespace NOK2025\V1\Blocks;

class PartBlock {
    public static function register(): void {
        register_block_type( THEME_ROOT_ABS . '/build/blocks/template', [
            'render_callback' => [ self::class, 'render' ],
            'attributes'      => [
                'partId' => [ 'type' => 'integer' ],
            ],
        ] );
    }

    public static function render( array $attrs ): string {
        $id = $attrs['partId'] ?? 0;
        if ( ! $id || ! $post = get_post( $id ) ) {
            return '';
        }
        $content = apply_filters( 'the_content', $post->post_content );
        return sprintf(
            '<div class="wp-block-' . THEME_TEXT_DOMAIN . '-template">%s</div>',
            $content
        );
    }
}

// Hook it up in your Theme class after_setup_theme or init
add_action( 'init', [ \NOK2025\V1\Blocks\PartBlock::class, 'register' ] );
