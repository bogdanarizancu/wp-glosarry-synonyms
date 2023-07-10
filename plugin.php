<?php

/*
  Plugin Name: WP Glossary Synonyms
  Version: 1.0
  Text Domain: wp-glossary-synonyms
  Description: A custom plugin extension for WP Glossary plugin
  Domain Path: /languages
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

// Activation hooks
register_activation_hook(__FILE__, __NAMESPACE__ . '\Schema::activate');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\Schema::deactivate');
register_uninstall_hook(__FILE__, __NAMESPACE__ . '\Schema::uninstall');

// Initialise plugin classes
add_action('plugins_loaded', function () {
    load_plugin_textdomain('wp-glossary-synonyms', false, basename(__DIR__) . '/languages/');
});
add_action('init', [(new PLugin()), 'init'], 20);
add_action('admin_init', [new Admin(), 'init']);

// Load composer dependencies, only for dev environment for formatting the code.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}
