<?php

use NOK2025\V1\Assets;
use NOK2025\V1\Theme;

$theme = Theme::get_instance();
$theme->embed_page_part_template('nok-footer', []); ?>

<button class="nok-scroll-progress scroll-to-top"
        aria-label="<?php esc_attr_e('Klik om terug naar de bovenkant van de pagina te gaan', 'nok'); ?>"
        title="<?php esc_attr_e('Klik om terug naar de bovenkant van de pagina te gaan', 'nok'); ?>"
        data-visible="false"
>
    <svg class="nok-scroll-progress__svg" viewBox="0 0 48 48" aria-hidden="true">
        <!-- Background track circle -->
        <circle class="nok-scroll-progress__track" />
        <!-- Progress indicator circle -->
        <circle class="nok-scroll-progress__progress" />
    </svg>

    <!-- Arrow up icon -->
    <?= Assets::getIcon('ui_arrow-up-long', 'nok-scroll-progress__icon') ?>
</button>

<?php wp_footer(); ?>

	</body>
</html>