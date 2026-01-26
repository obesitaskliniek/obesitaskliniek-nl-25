<?php
/**
 * Server-side render callback for Embed NOK Video block
 *
 * Renders video embeds with play button overlay, optional title and description.
 * Supports YouTube, Vimeo (via oEmbed), and self-hosted videos.
 *
 * Block attributes:
 * - videoUrl (string): Video URL, file path, or YouTube video ID (11-char alphanumeric)
 * - videoType (string): 'youtube', 'vimeo', or 'self'
 * - title (string): Optional heading above/below video
 * - description (string): Optional text content
 *
 * Features:
 * - Accepts YouTube video IDs (e.g., 'dQw4w9WgXcQ') and converts to full URL
 * - Automatic oEmbed for YouTube/Vimeo
 * - Play button overlay (hidden via JavaScript when playing)
 * - Lazy-loads nok-video-block.mjs module
 * - 16:9 aspect ratio wrapper
 * - Fallback for missing video URL
 *
 * @param array $attributes Block attributes from block.json
 * @param string $content Block inner content (unused for this block)
 * @param WP_Block $block Block instance
 * @return string Rendered HTML output
 */
return function( array $attributes, string $content, WP_Block $block ): string {
	$video_url = $attributes['videoUrl'] ?? '';
	$video_type = $attributes['videoType'] ?? 'youtube';
	$title = $attributes['title'] ?? '';
	$description = $attributes['description'] ?? '';

	// Convert YouTube ID to full URL if needed
	if ( $video_type === 'youtube' && ! empty( $video_url ) ) {
		// Check if it's just a video ID (no slashes, typical YT ID pattern)
		if ( preg_match( '/^[a-zA-Z0-9_-]{11}$/', $video_url ) ) {
			$video_url = 'https://www.youtube.com/watch?v=' . $video_url;
		}
	}

	// Get video embed HTML
	$video_html = '';
	if ( ! empty( $video_url ) ) {
		if ( $video_type === 'self' ) {
			// Self-hosted video
			$video_html = sprintf(
				'<video controls src="%s" preload="metadata"></video>',
				esc_url( $video_url )
			);
		} else {
			// YouTube or Vimeo via oEmbed
			$video_html = wp_oembed_get( $video_url, [ 'width' => 1280 ] );

			// Fallback if oEmbed fails
			if ( ! $video_html ) {
				$video_html = sprintf(
					'<iframe src="%s" frameborder="0" allowfullscreen></iframe>',
					esc_url( $video_url )
				);
			}
		}
	}

	// Build wrapper classes
	$wrapper_attributes = get_block_wrapper_attributes( [
		'class' => 'nok-video-block nok-my-1',
	] );

	ob_start();
	?>
	<div <?php echo $wrapper_attributes; ?> data-requires="./nok-video-block.mjs" data-require-lazy="true">
		<div class="nok-video-block__content">

			<?php if ( $video_html ): ?>
				<div class="nok-video-block__video-wrapper">
					<?php echo $video_html; ?>
				</div>
			<?php else: ?>
				<div class="nok-video-block__video-wrapper nok-video-block__empty">
					<p>Geen video URL opgegeven</p>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $title ) || ! empty( $description ) ): ?>
				<div class="nok-video-block__text">
					<?php if ( ! empty( $title ) ): ?>
						<h2 class="nok-fs-giant"><?php echo esc_html( $title ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $description ) ): ?>
						<div class="nok-fs-body">
							<?php echo wp_kses_post( wpautop( $description ) ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
	<?php
	return ob_get_clean();
};
