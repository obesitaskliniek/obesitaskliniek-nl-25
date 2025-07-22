<!doctype html>
<html lang="nl" class="no-js <?= (is_user_logged_in() ? 'logged-in' : ''); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">

        <title>Temp header</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
              rel="stylesheet">
        <link href="<?= THEME_ROOT ;?>/assets/fonts/realist.css" rel="stylesheet" crossorigin="anonymous">
        <link rel="modulepreload" href="<?= THEME_ROOT ;?>/assets/js/entrypoint.min.mjs?cache=<?= time(); ?>">
        <script type="module" src="<?= THEME_ROOT ;?>/assets/js/entrypoint.min.mjs?cache=<?= time(); ?>" defer></script>

        <!-- head data -->
        <?php wp_head(); ?>


    </head>

    <body>