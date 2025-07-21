<?php
/**
 * Server‑side render for Embed NOK Page Part.
 *
 * @return callable
 */
return function( array $attributes ): string {
    $post_id = absint( $attributes['postId'] ?? 0 );
    if ( ! $post_id || get_post_type( $post_id ) !== 'page_part' ) {
        return '<p>' . esc_html__( 'Geen blok geselecteerd.', THEME_TEXT_DOMAIN ) . '</p>';
    }
    $post = get_post( $post_id );
    if ( ! $post ) {
        return '<p>' . esc_html__( 'Part not found.', THEME_TEXT_DOMAIN ) . '</p>';
    }

    $design = get_post_meta( $post_id, 'design_slug', true ) ?: 'header-top-level';

    // Enqueue the per‑design CSS
    /*
    if ( wp_style_is( $design, 'registered' ) ) {
        wp_enqueue_style( $design );
    }*/

    $tpl = get_theme_file_path( "template-parts/page-parts/{$design}.php" );
    if ( ! file_exists( $tpl ) ) {
        return '<p>' . sprintf(
                esc_html__( 'Template for "%s" not found.', THEME_TEXT_DOMAIN ),
                esc_html( $design )
            ) . '</p>';
    }

    $args = [ 'post' => $post ];
    ob_start();
    include $tpl;
    return ob_get_clean();
};
