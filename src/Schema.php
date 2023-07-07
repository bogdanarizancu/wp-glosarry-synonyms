<?php

/**
 * @file
 * Contains \WPGlossarySynonyms\Schema.
 */

namespace WPGlossarySynonyms;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Plugin lifetime and maintenance.
 */
class Schema
{
    /**
     * register_activation_hook() callback.
     */
    public static function activate()
    {
        // Ensure parent plugin exists and is activated.
        if (! is_plugin_active('wp_glossary/index.php') and current_user_can('activate_plugins')) {
            // Stop activation redirect and show error
            wp_die('Sorry, but this plugin requires the WP Glossary Plugin to be installed and active. <br><a href="' . admin_url('plugins.php') . '">&laquo; Return to Plugins</a>');
        }
    }

    /**
     * register_deactivation_hook() callback.
     */
    public static function deactivate()
    {
    }

    /**
     * register_uninstall_hook() callback.
     */
    public static function uninstall()
    {
    }
}
