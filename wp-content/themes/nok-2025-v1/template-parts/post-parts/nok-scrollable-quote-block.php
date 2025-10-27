<?php

use NOK2025\V1\Assets;
use NOK2025\V1\Helpers;

$scroller_id              = $args['scroller_id'] ?? 'scroller-' . time() . rand( 0, 1000 );
$quote_data               = $args['quotes'] ?? [];
$class                    = $args['class'] ?? '';
$quote_block_style        = $args['block_color'] ?? '';
$quote_block_button_style = $args['block_button_color'] ?? 'nok-bg-darkestblue nok-text-contrast'; ?>
<div class="<?= $class ?> nok-align-self-to-lg-stretch nok-column-last-3">
    <div class="nok-scrollable__horizontal nok-subtle-shadow-compensation"
         data-scroll-snapping="true" data-draggable="true"
         id="<?= $scroller_id; ?>" data-autoscroll="false">
        <?php foreach ( $quote_data as $quote ): ?>
            <nok-square-block class="<?= $quote_block_style ?> nok-alpha-10 nok-p-3" data-shadow="true">
                <div class="nok-square-block__heading">
                    <h2>"<?= esc_html( Helpers::strip_all_quotes( $quote['quote'] ) ) ?>"</h2>
                </div>
                <?php if ( ! empty( $quote['excerpt'] ) ) : ?>
                    <div class="nok-square-block__text nok-fs-2"><?= esc_html( $quote['excerpt'] ) ?></div>
                <?php endif; ?>
                <?php if ( ! empty( $quote['name'] ) && empty( $quote['excerpt'] ) ) : ?>
                    <div>
                        <div class="nok-fs-2"><?= esc_html( $quote['name'] ) ?></div>
                        <?php if ( ! empty( $quote['subname'] ) ) : ?>
                            <p><?= esc_html( $quote['subname'] ) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ( ! empty( $quote['link_url'] ) ) : ?>
                    <div class="nok-layout-flex-row space-between">
                        <a role="button" href="<?= esc_url( $quote['link_url'] ) ?>"
                           class="nok-button nok-justify-self-start <?= $quote_block_button_style ?> fill-mobile"
                           title="Lees het hele verhaal van <?= esc_attr( $quote['name'] ?? 'deze patiënt' ) ?>"
                           tabindex="0">
                            Lees het verhaal <?= Assets::getIcon( 'ui_arrow-right-long', 'nok-text-yellow' ); ?>
                        </a>
                        <?php if ( ! empty( $quote['image_url'] ) ): ?>
                        <div class="nok-square-block__thumbnail">
                            <img src="<?= esc_url( $quote['image_url'] ) ?>" loading="lazy">
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </nok-square-block>
        <?php endforeach; ?>
    </div>
</div>
