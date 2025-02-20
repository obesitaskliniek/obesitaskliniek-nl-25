<?php

define('NOK_MAINTENANCE',   false);
define('NOK_SITE_LIVE',     false);
define('NOK_BASE_URI',      'https://dev.obesitaskliniek.nl');
define('NOK_TEXT_DOMAIN',   'nok-2022-v1');
define('NOK_COPYRIGHT',     '©'.Date('Y').' Nederlandse Obesitas Kliniek B.V. - Alle rechten voorbehouden.');

//stop editing!
require('_helpers.php');
define('NOK_THEME_ROOT',    get_template_directory_uri());
define('NOK_THEME_ROOT_ABS',get_template_directory());
define('NOK_LOGGED_IN',     function_exists( 'is_user_logged_in' ) && is_user_logged_in() );
define('NOK_WP_ROOT',       function_exists('get_home_path') ? get_home_path() : dirname(dirname(dirname(dirname(dirname(__FILE__))))));
define('NOK_NONCE',         hash('sha256', makeRandomString()));
define('NOK_CACHENONCE',    (NOK_SITE_LIVE ? '' : '?cache=' . hash('sha256', makeRandomString())));

define('BS_VER',            'bs-5.2.0');
define('FA_VER',            'fa5.15.4');

define('NOK_FAQ_CATEGORIES_SLUG', 'faq_categories');
define('NOK_FAQ_POST_TYPE', 'faq_items');