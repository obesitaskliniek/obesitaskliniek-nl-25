<?php
/**
 * Template Name: Top Level Header
 * Slug: header-top-level
 * CSS: header-top-level
 */

/** @var \WP_Post|null $post */
$post = $args['post'] ?? null;

if ( ! ( $post instanceof \WP_Post ) ) {
    echo '<p><em>Ongeldig Page Part</em></p>';
    return;
}
?>
<div class="page-part page-part--header-top-level">
    <?php echo wpautop( $post->post_content ); ?>
</div>
