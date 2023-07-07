<?php

/**
 * @file
 *
 * Contains \WPGlossarySynonyms\Admin
 */

 namespace WPGlossarySynonyms;

class Admin
{
    public function init()
    {
        // Save Meta box
        add_action('save_post', array($this, 'save_meta_box'));
    }

    public function save_meta_box($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        if (! isset($_POST['wpg_action']) || ! wp_verify_nonce($_POST['wpg_action'], 'post_settings')) {
            return;
        }

        // Save Fields
        $fields = array ('associated_term', 'synonym_spellings', 'wpg_disable_tooltip', 'wpg_disable_linkify', 'wpg_exclude_from_glossary_index', 'wpg_exclude_from_linkify' );

        foreach ($fields as $field) {
            $value = isset($_REQUEST[ $field ]) ? $_REQUEST[ $field ] : '';

            if ($value) {
                update_post_meta($post_id, $field, $value);
            } else {
                delete_post_meta($post_id, $field);
            }
        }
    }
}
