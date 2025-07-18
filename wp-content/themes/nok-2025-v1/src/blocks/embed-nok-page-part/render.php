<?php
/**
 * Render template for Embed Page Part block.
 *
 * @var array $attributes
 */
$post_id = isset( $attributes['postId'] ) ? absint( $attributes['postId'] ) : 0;
if ( ! $post_id || get_post_type( $post_id ) !== 'page_part' ) {
    print '<p>' . esc_html__( 'Geen blok geselecteerd.', THEME_TEXT_DOMAIN ) . '</p>';
}

$post = get_post( $post_id );
if ( ! $post ) {
    print '<p>' . esc_html__( 'Part not found.', THEME_TEXT_DOMAIN ) . '</p>';
}

print apply_filters( 'the_content', $post->post_content );
