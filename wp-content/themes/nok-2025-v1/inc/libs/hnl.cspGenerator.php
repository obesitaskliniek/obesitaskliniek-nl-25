<?php
/**
 * CSP Generator v0.2 (C) 2020-10-12 hnldesign.nl / Klaas Leussink
 * @param array $rules
 * Rules, specified as an array with rulesets (array) or rules (string). Note that 'hosts' are specified as an array inside the ruleset:
 * note: it's easier to define hosts in the $domains array (see second param)
 * array(
 *  'default-src' => array (
 *      'self',
 *      'data',
 *      'unsafe-eval',
 *      'hosts' =>  array (
 *          'https:'
 *      )
 *  ),
 * 'script-src' => array (
 *      'self',
 *      'unsafe-inline',
 *      'unsafe-eval',
 *      'hosts' =>  array (
 *          'scripts.com', 'scripts2.com'
 *      )
 *  ),
 *  'upgrade-insecure-requests',
 *  'report-to /some-report-uri',
 *  'base-uri' => array (
 *      '*'
 *  )
 * )
 * default values can be specified in $defaultRules, and will be overruled if (re)specified in $rules
 *
 * @param array $domains
 * Rules, based on domain (easiest), specified as an array with domains:
 * array(
 *  'domain.com' => array (
 *      'img-src',
 *      'script-src',
 *      'font-src'
 *  )
 * )
 * default values can be specified in $defaultDomains, and will be overruled if (re)specified in $domains
 *
 * @return string the complete CSP ruleset
 */
function constructCSP($rules = array(), $domains = array()) {
    $cspRules = array();
    $defaultRules = array(
        'default-src' => array (
            'self',
            'data',
            'unsafe-eval'
        ),
        'object-src' => array(
            'none'
        )
    );
    $defaultDomains = array(
        'https:'    =>  array(
            'default-src'
        )
    );

    $ruleSet = array_merge($defaultRules, $rules);
    $domainSet = array_merge($defaultDomains, $domains);

    if(isset($domainSet) && count($domainSet) > 0) {
        foreach ($domainSet as $domain => $set) {
            foreach ($set as $ruleName) {
                if (!isset($ruleSet[$ruleName]) || !is_array($ruleSet[$ruleName])) {
                    $ruleSet[$ruleName] = array(
                        'hosts' => array($domain)
                    );
                } else {
                    if (!isset($ruleSet[$ruleName]['hosts']) || !is_array($ruleSet[$ruleName]['hosts'])) {
                        $ruleSet[$ruleName]['hosts'] = array($domain);
                    } else {
                        $ruleSet[$ruleName]['hosts'][] = $domain;
                    }
                }
            }
        }
    }

    foreach ($ruleSet as $rule => $set) {
        $hosts = ''; $_unquoted_set = array(); $x = null;

        if (is_array($set)) {

            if (array_key_exists('hosts', $set) || $x = array_search('hosts', $set)) {
                //this also empties wrongly defined (non-array), or empty 'hosts'.
                if(is_array($set['hosts']) && count($set['hosts']) > 0) {
                    $hosts = implode(' ', array_unique($set['hosts']));
                }
                unset($set[($x) ? $x : 'hosts']);
            }

            //don't quote the data: option
            if ($x = array_search('data', $set) !== false) {
                $_unquoted_set[] = 'data:';
                unset($set[$x]);
            }
            //don't quote the * option
            if (($x = array_search('*', $set)) !== false) {
                $_unquoted_set[] = '*';
                unset($set[$x]);
            }

            $ruleName   =   $rule;
            $rulePart1  =   ((count($set) > 0) ? '\'' . implode('\' \'', $set) . '\'' : '');
            $rulePart2  =   ((count($_unquoted_set) > 0) ? implode(' ', $_unquoted_set) : '');
            $ruleHosts  =   (isset($hosts) ? $hosts : '');
            $cspRules[] =   trim(preg_replace('!\s+!', ' ', $ruleName . ' ' . $rulePart1 . ' ' . $rulePart2 . ' ' . $ruleHosts));

        } else {

            $cspRules[] = trim(preg_replace('!\s+!', ' ', $set));

        }

    }

    return implode('; ', $cspRules);

}