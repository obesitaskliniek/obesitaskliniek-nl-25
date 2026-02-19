<?php
/**
 * Block Part: Attachment Downloads
 * Description: Download list for non-image attachments uploaded to the current page
 * Slug: attachment-downloads
 * Icon: download
 * Keywords: download, attachment, pdf, document
 * Custom Fields:
 * - title:text!default(Downloads)
 * - description:textarea!default()!descr[Optionele beschrijving, ondersteunt links]
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 * @var array $attachments Non-image attachment data from Helpers::get_non_image_attachments()
 * @var array $attributes Block attributes
 */

use NOK2025\V1\Assets;

$c = $context;

$title       = $c->has( 'title' ) ? $c->title->raw() : '';
$description = $c->has( 'description' ) ? $c->description->raw() : '';

$allowed_html = [
	'a'      => [
		'href'   => [],
		'target' => [],
		'rel'    => [],
		'title'  => [],
	],
	'strong' => [],
	'em'     => [],
	'br'     => [],
];
?>

<nok-section class="nok-bg-darkerblue nok-text-contrast">
	<div class="nok-section__inner">
		<div class="nok-layout-grid nok-layout-grid__1-column">
			<?php if ( $title ) : ?>
				<h2 class="nok-fs-6"><?= wp_kses( $title, 'post' ) ?></h2>
			<?php endif; ?>
			<?php if ( $description ) : ?>
				<p><?= wp_kses( $description, $allowed_html ) ?></p>
			<?php endif; ?>
			<div class="nok-downloads-list">
				<?php foreach ( $attachments as $file ) : ?>
					<a href="<?= esc_url( $file['url'] ) ?>"
					   class="nok-download-item"
					   download
					   title="<?= esc_attr( sprintf( '%s downloaden', $file['title'] ) ) ?>">
						<span class="nok-download-item__icon">
							<?= Assets::getIcon( 'ui_file', 'nok-text-yellow' ) ?>
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
	</div>
</nok-section>
