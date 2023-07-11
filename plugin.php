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
    // Prevent original plugin from init linkify, since this has its own rules,
    // taking into account spellings count limit.
    remove_class_action('wp', 'init_linkify', 'WPG_Linkify');
});
add_action('init', [(new PLugin()), 'init'], 20);
add_action('admin_init', [new Admin(), 'init']);

// Load composer dependencies, only for dev environment for formatting the code.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}


function remove_class_action($tag, $method, $class = '', $priority = null): bool
{
    global $wp_filter;
    if (isset($wp_filter[$tag])) {
        $len = strlen($method);

        foreach ($wp_filter[$tag] as $_priority => $actions) {
            if ($actions) {
                foreach ($actions as $function_key => $data) {
                    if ($data) {
                        if (substr($function_key, -$len) == $method) {
                            if ($class !== '') {
                                $_class = '';
                                if (is_string($data['function'][0])) {
                                    $_class = $data['function'][0];
                                } elseif (is_object($data['function'][0])) {
                                    $_class = get_class($data['function'][0]);
                                } else {
                                    return false;
                                }

                                if ($_class !== '' && $_class == $class) {
                                    if (is_numeric($priority)) {
                                        if ($_priority == $priority) {
                                            //if (isset( $wp_filter->callbacks[$_priority][$function_key])) {}
                                            return $wp_filter[$tag]->remove_filter($tag, $function_key, $_priority);
                                        }
                                    } else {
                                        return $wp_filter[$tag]->remove_filter($tag, $function_key, $_priority);
                                    }
                                }
                            } else {
                                if (is_numeric($priority)) {
                                    if ($_priority == $priority) {
                                        return $wp_filter[$tag]->remove_filter($tag, $function_key, $_priority);
                                    }
                                } else {
                                    return $wp_filter[$tag]->remove_filter($tag, $function_key, $_priority);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return false;
}
