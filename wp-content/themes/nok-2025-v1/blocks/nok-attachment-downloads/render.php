<?php
/**
 * Server-side render callback for NOK Attachment Downloads block
 *
 * Queries non-image attachments for the current post/page and renders
 * a download list. Returns empty string when no attachments exist.
 *
 * @param array $attributes Block attributes from block.json
 * @return string Rendered HTML output
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

return function( array $attributes ): string {
	$attachments = Helpers::get_non_image_attachments();

	if ( empty( $attachments ) ) {
		return '';
	}

	ob_start();
	?>
	<nok-section class="nok-bg-darkerblue nok-text-contrast">
		<div class="nok-section__inner">
			<div class="nok-downloads-list">
				<?php foreach ( $attachments as $file ) : ?>
					<a href="<?= esc_url( $file['url'] ) ?>"
					   class="nok-download-item"
					   download
					   title="<?= esc_attr( sprintf( '%s downloaden', $file['title'] ) ) ?>">
						<span class="nok-download-item__icon">
							<?= Assets::getIcon( 'ui_download' ) ?>
						</span>
						<span class="nok-download-item__info">
							<span class="nok-download-item__title"><?= esc_html( $file['title'] ) ?></span>
							<span class="nok-download-item__meta">
								<?= esc_html( $file['filetype'] ) ?>
								<?php if ( $file['filesize'] ) : ?>
									· <?= esc_html( $file['filesize'] ) ?>
								<?php endif; ?>
							</span>
						</span>
						<span class="nok-download-item__action">
							<?= Assets::getIcon( 'ui_arrow-down' ) ?>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</nok-section>
	<?php
	return ob_get_clean();
};
