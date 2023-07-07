<?php

/*
  Plugin Name: WP Glossary Synonyms
  Version: 1.0
  Text Domain: wp-glossary-synonyms
  Description: A custom plugin extension for WP Glossary plugin
  Author: Bogdan Arizancu
  Author URI: https://github.com/bogdanarizancu
  License: GPL-2.0+
  License URI: https://www.gnu.org/licenses/gpl-2.0
*/

namespace WPGlossarySynonyms;

if (!defined('ABSPATH')) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    exit;
}

/**
 * Loads PSR-4-style plugin classes.
 */
function classloader($class)
{
    static $ns_offset;
    if (strpos($class, __NAMESPACE__ . '\\') === 0) {
        if ($ns_offset === null) {
            $ns_offset = strlen(__NAMESPACE__) + 1;
        }
        include __DIR__ . '/src/' . strtr(substr($class, $ns_offset), '\\', '/') . '.php';
    }
}
spl_autoload_register(__NAMESPACE__ . '\classloader');

register_activation_hook(__FILE__, __NAMESPACE__ . '\Schema::activate');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\Schema::deactivate');
register_uninstall_hook(__FILE__, __NAMESPACE__ . '\Schema::uninstall');

$plugin = new Plugin();
add_action('plugins_loaded', [$plugin, 'loadTextdomain']);
add_action('init', [$plugin, 'init'], 20);
add_action('admin_init', [new Admin(), 'init']);

include_once('/Users/b-t.arizancu/Personal/wp-glossary/wp-content/plugins/wp_glossary/includes/class-wpg-linkify.php');
include_once('src/class-wpg-linkify.php');

require __DIR__ . '/vendor/autoload.php';

add_filter('wpg_settings', function ($optionSections) {
    $option = [
        'wpg_glossary_linkify_synonym_limit' => [
            'name' => 'wpg_glossary_linkify_synonym_limit',
            'label' => 'Linkify Limit per Synonym',
            'type' => 'number',
            'desc' => 'Same as linkify limit for terms, but applied to synonyms.',
        ]
    ];
    $linkifyOptions = $optionSections['section_linkify']['options'];

    $optionSections['section_linkify']['options'] = push_at_to_associative_array($optionSections['section_linkify']['options'], 'wpg_glossary_linkify_term_limit', $option);
    return $optionSections;
});

function push_at_to_associative_array($array, $key, $new)
{
    $keys =   array_keys($array);
    $index = array_search($key, $keys, true);
    $pos = false === $index ? count($array) : $index + 1;

    $array = array_slice($array, 0, $pos, true) + $new + array_slice($array, $pos, count($array) - 1, true);
    return $array;
}
