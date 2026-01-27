<?php
/**
 * Block Part: General Section
 * Description: Generic content section with configurable styling
 * Slug: general-section
 * Icon: layout
 * Keywords: section, content, layout
 * Custom Fields:
 * - background_color:select(Wit::white|Lichtblauw::lightblue|Blauw::blue|Donkerblauw::darkblue|Donkerder blauw::darkerblue|Donkerste blauw::darkestblue|Transparant::transparent)!default(white)
 * - text_color:select(Donkerder blauw::darkerblue|Wit::white|Contrast::contrast|Lichtblauw::lightblue)!default(darkerblue)
 * - layout_width:select(1 kolom::1-column|2 kolommen::2-column|3 kolommen::3-column)!default(1-column)
 * - narrow_section:checkbox!default(false)
 * - enable_pull_up:checkbox!default(false)
 * - enable_no_aos:checkbox!default(false)
 *
 * @var \NOK2025\V1\PageParts\FieldContext $context
 * @var string $content Inner blocks content
 * @var array $attributes Block attributes (includes anchor, align)
 */

$c = $context;

// Build section classes
$section_classes = [
	'nok-general-content',
	'nok-bg-' . $c->background_color->raw(),
	'nok-dark-bg-' . $c->background_color->raw(),
];

if ( $c->enable_no_aos->isTrue() ) {
	$section_classes[] = 'no-aos';
}

if ( ! empty( $attributes['align'] ) ) {
	$section_classes[] = 'align' . $attributes['align'];
}

// Build inner classes
$inner_classes = [
	'nok-section__inner',
	'nok-text-' . $c->text_color->raw(),
	'nok-dark-text-' . ( $c->text_color->is( 'darkerblue' ) ? 'contrast' : $c->text_color->raw() ),
];

if ( $c->narrow_section->isTrue() ) {
	$inner_classes[] = 'nok-section-narrow';
}

if ( $c->enable_pull_up->isTrue() ) {
	$inner_classes[] = 'nok-pull-up-2';
}

// Build layout classes
$layout_classes = [
	'nok-layout-grid',
	'nok-layout-grid__' . $c->layout_width->raw(),
];

$anchor_attr = ! empty( $attributes['anchor'] )
	? ' id="' . esc_attr( $attributes['anchor'] ) . '"'
	: '';
?>

<nok-section class="<?= esc_attr( implode( ' ', $section_classes ) ) ?>"<?= $anchor_attr ?>>
	<div class="<?= esc_attr( implode( ' ', $inner_classes ) ) ?>">
		<article class="<?= esc_attr( implode( ' ', $layout_classes ) ) ?>">
			<?= $content ?>
		</article>
	</div>
</nok-section>
