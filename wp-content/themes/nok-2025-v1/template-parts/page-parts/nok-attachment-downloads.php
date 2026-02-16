<?php
/**
 * Template Name: Attachment Downloads
 * Description: Lists non-image attachments (PDFs, documents, etc.) for download
 * Slug: nok-attachment-downloads
 * Icon: ui_download
 * Custom Fields:
 * - colors:color-selector(section-colors)!page-editable!default(nok-bg-darkerblue nok-text-contrast)
 * - narrow_section:checkbox!default(false)!descr[Smalle sectie?]!page-editable
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 */

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$c = $context;

$attachments = Helpers::get_non_image_attachments();

if ( empty( $attachments ) ) {
	return;
}
?>

<nok-section class="<?= $c->colors ?>">
	<div class="nok-section__inner <?= $c->narrow_section->isTrue( 'nok-section-narrow' ); ?>">
		<article class="nok-layout-grid nok-layout-grid__1-column nok-align-items-start">
			<h2 class="nok-fs-6"><?= $c->title() ?></h2>

			<?= $c->content(); ?>

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
		</article>
	</div>
</nok-section>
