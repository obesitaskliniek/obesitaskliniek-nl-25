<?php
/**
 * Server-side render callback for General NOK Section block
 *
 * Wraps inner block content in a nok-section element with configurable styling.
 * This provides consistent section styling for regular WordPress content
 * that appears alongside page parts.
 *
 * Block attributes:
 * - backgroundColor: Background color class (white, lightblue, darkblue, etc.)
 * - textColor: Text color class (darkerblue, white, contrast)
 * - layoutWidth: Grid layout width (1-column, 2-column, 3-column)
 * - enablePullUp: Whether to add pull-up effect
 * - enableNoAos: Whether to disable animations
 * - align: Block alignment (wide, full)
 * - anchor: HTML anchor ID
 *
 * @param array $attributes Block attributes from block.json
 * @param string $content Inner blocks content
 * @return string Rendered HTML output
 */

return function( array $attributes, string $content ): string {
    // Return empty if no content
    if ( empty( trim( strip_tags( $content ) ) ) ) {
        return '';
    }

    // Extract attributes with defaults
    $background_color = $attributes['backgroundColor'] ?? 'white';
    $text_color       = $attributes['textColor'] ?? 'darkerblue';
    $layout_width     = $attributes['layoutWidth'] ?? '1-column';
    $narrow_section   = $attributes['narrowSection'] ?? false;
    $enable_pull_up   = $attributes['enablePullUp'] ?? false;
    $enable_no_aos    = $attributes['enableNoAos'] ?? false;

    // Build nok-section classes
    $section_classes = [
		'nok-general-content',
        'nok-bg-' . $background_color,
        'nok-dark-bg-' . $background_color,
	    ];

    // Add no-aos class if enabled
    if ( $enable_no_aos ) {
        $section_classes[] = 'no-aos';
    }

    // Add alignment class if set
    if ( ! empty( $attributes['align'] ) ) {
        $section_classes[] = 'align' . $attributes['align'];
    }

    // Build nok-section__inner classes
    $inner_classes = [
        'nok-section__inner',
        'nok-text-' . $text_color,
        'nok-dark-text-' . ( $text_color === 'darkerblue' ? 'contrast' : $text_color ),
    ];

    // Add narrow section class if enabled
    if ( $narrow_section ) {
        $inner_classes[] = 'nok-section-narrow';
    }

    // Add pull-up class if enabled
    if ( $enable_pull_up ) {
        $inner_classes[] = 'nok-pull-up-2';
    }

    // Build article/layout classes
    $layout_classes = [
        'nok-layout-grid',
        'nok-layout-grid__' . $layout_width,
    ];

    // Build anchor attribute if set
    $anchor_attr = ! empty( $attributes['anchor'] )
        ? ' id="' . esc_attr( $attributes['anchor'] ) . '"'
        : '';

    // Return wrapped content
    return sprintf(
        '<nok-section class="%s"%s><div class="%s"><article class="%s">%s</article></div></nok-section>',
        esc_attr( implode( ' ', $section_classes ) ),
        $anchor_attr,
        esc_attr( implode( ' ', $inner_classes ) ),
        esc_attr( implode( ' ', $layout_classes ) ),
        $content
    );
};
