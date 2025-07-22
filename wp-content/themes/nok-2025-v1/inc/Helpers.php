<?php
// inc/Helpers.php
namespace NOK2025\V1;


class Helpers {
    public static function makeRandomString($bits = 256): string {
        #generates nonce (for Google Tag Manager etc)
        $bytes = ceil($bits / 8);
        $return = '';
        for ($i = 0; $i < $bytes; $i++) {
            $return .= chr(mt_rand(0, 255));
        }
        return $return;
    }

}

/**
 * Join an array of items into a comma‑separated list,
 * using a localized “and” before the last item.
 *
 * @param string[] $items
 * @return string
 */
function oxford_list( array $items ): string {
    $count = count( $items );
    if ( $count === 0 ) {
        return '';
    }
    if ( $count === 1 ) {
        return esc_html( $items[0] );
    }
    $last = array_pop( $items );
    return esc_html( implode( ', ', $items ) )
        . ' ' . __( 'en', 'mytheme' ) . ' '
        . esc_html( $last );
}

function get_csp($nonce): string
{
    require_once ('libs/hnl.cspGenerator.php');

    return constructCSP(
        array(
            'default-src' => array(
                'self',
                'data',
                'unsafe-eval',
                'hosts' => array (
                    'https:'
                )
            ),
            'script-src' => array(
                'self',
                'unsafe-inline',
                'unsafe-eval',
                'unsafe-hashes',
                //'strict-dynamic'
            ),
            'style-src' => array(
                'self',
                'data',
                'unsafe-inline',
                'unsafe-hashes'
            ),
            'img-src' => array(
                'self',
                'data'
            ),
            'font-src' => array(
                'self',
                'data'
            ),
            'connect-src' => array(
                'self'
            ),
            'frame-src' => array(
                'self'
            ),
            'base-uri'  => array(
                'self'
            )
        ),
        array(
            //'\'nonce-'.$nonce.'\''  =>  array ( 'script-src' ),
            '*.obesitaskliniek.nl'  =>  array ( 'script-src', 'style-src', 'img-src', 'font-src', 'frame-src' ),
            'code.hnldesign.nl'     =>  array ( 'script-src', 'style-src' ),
            'cdn.jsdelivr.net'      =>  array ( 'script-src', 'style-src' ),
            'connect.facebook.net'  =>  array ( 'script-src' ),
            '*.facebook.com'        =>  array ( 'img-src', 'frame-src', 'connect-src' ),
            '*.youtube.com'         =>  array ( 'script-src', 'frame-src', 'connect-src' ),
            '*.googleapis.com'      =>  array ( 'script-src', 'style-src', 'img-src', 'font-src', 'frame-src' ),
            '*.googleoptimize.com'  =>  array ( 'script-src' ),
            '*.google.com'          =>  array ( 'script-src', 'img-src', 'font-src', 'frame-src' ),
            '*.google.nl'           =>  array ( 'img-src' ),
            '*.gstatic.com'         =>  array ( 'script-src', 'img-src', 'font-src', 'frame-src' ),
            '*.google-analytics.com'=>  array ( 'script-src', 'img-src', 'connect-src' ),
            '*.googletagmanager.com'=>  array ( 'script-src', 'img-src', 'frame-src' ),
            '*.googleadservices.com'=>  array ( 'script-src'),
            '*.ubembed.com'         =>  array ( 'script-src', 'frame-src'),
            '*.g.doubleclick.net'   =>  array ( 'script-src', 'img-src', 'connect-src'),
            '*.hotjar.com'          =>  array ( 'script-src', 'connect-src', 'frame-src'),
            '*.omnivr.nl'           =>  array ( 'frame-src'),
            'sentry.io'             =>  array ( 'connect-src'),
            //hubspot
            '*.hs-scripts.com'      =>  array ( 'script-src'),
            '*.hs-banner.com'       =>  array ( 'script-src'),
            '*.hs-analytics.net'    =>  array ( 'script-src'),
            '*.hscollectedforms.net'=>  array ( 'script-src'),
            '*.hubspot.com'         =>  array ( 'connect-src', 'img-src'),
            '*.hsforms.com'         =>  array ( 'img-src'),
        )
    );
}

function do_extra_header_data($nonce = ''): void
{
    if (defined('is_admin') && !is_admin()) header( 'Content-Security-Policy:' . get_csp($nonce) );
    header( 'X-Frame-Options: Allow' );
    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-XSS-Protection: 1; mode=block' );
    header( 'Strict-Transport-Security: max-age=631138519; includeSubDomains' );
    header( 'Connection: keep-alive' );
}

function makeRandomString($bits = 256): string
{
#generate nonce (for Google Tag Manager etc)
    $bytes = ceil($bits / 8);
    $return = '';
    for ($i = 0; $i < $bytes; $i++) {
        $return .= chr(mt_rand(0, 255));
    }
    return $return;
}

//format a phonenumber
function format_phone($phone, $landcode = '31'): string
{
    $phone = str_replace(' ', '', $phone);
    $kentallen = array('06', '0909','0906','0900','0842','0800','0676','010','046','0111','0475','0113','0478','0114','0481','0115','0485','0117','0486','0118','0487','013','0488','015','0492','0161','0493','0162','0495','0164','0497','0165','0499','0166','050','0167','0511','0168','0512','0172','0513','0174','0514','0180','0515','0181','0516','0182','0517','0183','0518','0184','0519','0186','0521','0187','0522','020','0523','0222','0524','0223','0525','0224','0527','0226','0528','0227','0529','0228','053','0229','0541','023','0543','024','0544','0251','0545','0252','0546','0255','0547','026','0548','0294','055','0297','0561','0299','0562','030','0566','0313','0570','0314','0571','0315','0572','0316','0573','0317','0575','0318','0577','0320','0578','0321','058','033','0591','0341','0592','0342','0593','0343','0594','0344','0595','0345','0596','0346','0597','0347','0598','0348','0599','035','070','036','071','038','072','040','073','0411','074','0412','075','0413','076','0416','077','0418','078','043','079','045');
    if (substr($phone, 0, 3) == '+31' || substr($phone, 0, 2) == '31') {
        $phone = str_replace('+31', '0', $phone);
    }
    $netnummer = '0'; //def
    for($i=4; $i>=0; $i--) {
        $netnummer = substr($phone,0,$i);
        if(in_array($netnummer, $kentallen)) {
            break;
        } else {
            $netnummer = substr($phone, 0, 3); //def eerste 3 cijfers.
        }
    }
    $search = '/'.preg_quote($netnummer, '/').'/';
    $nummer = preg_replace($search, '', $phone, 1); //haal netnummer van oorspronkelijke nummer af
    if (strlen($nummer) < 8) {
        preg_match('/(\d{2,3})(\d{2}+)(\d{2}+)/', $nummer, $matches); //maakt groepjes: XXX XX XX of XX XX XX in het geval van een 4 cijferig netnummer
    } else {
        preg_match('/(\d{2})(\d{2})(\d{2}+)(\d{2}+)/', $nummer, $matches); //maakt groepjes: XXX XX XX of XX XX XX in het geval van een 4 cijferig netnummer
    }
    array_shift($matches); //remove first item (original string)
    if ($landcode) {
        $landcode = (substr($landcode, 0, 1) == '+') ? $landcode : '+' . $landcode;
        $search = '/'.preg_quote('0', '/').'/';
        return preg_replace($search, $landcode . ' ', $netnummer, 1) . ' ' . implode(' ', $matches);
    } else {
        return $netnummer . ' ' . implode(' ', $matches);
    }
}

//print an element from a multidimensional array
function array_to_element($type, $attributes, $innerHTML = '', $pre = ''): string
{
    $mapper = function ($v, $k) {
        if (is_bool($v) && $v) {
            return $k;
        } else {
            return $k.'="'.$v.'"';
        }
    };
    return $pre . '<' . $type . ' ' . implode(' ', array_map( $mapper, $attributes, array_keys($attributes) )) . '>' . $innerHTML . ($innerHTML ? "\n" . $pre : '') . '</' . $type . '>' . "\n";
}