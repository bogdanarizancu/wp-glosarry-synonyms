<?php

/**
 * @file
 *
 * Contains \WPGlossarySynonyms\Admin
 */

 namespace WPGlossarySynonyms;

class Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'extendMenu'));
    }

    public function init()
    {
        // Save Meta box
        add_action('save_post', array($this, 'save_meta_box'));
        add_filter('wpg_settings', array($this, 'extendSettings'));
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
        $fields = array ('associated_term', Plugin::ALTERNATIVE_SPELLINGS, 'wpg_disable_tooltip', 'wpg_disable_linkify', 'wpg_exclude_from_glossary_index', 'wpg_exclude_from_linkify' );

        foreach ($fields as $field) {
            $value = isset($_REQUEST[ $field ]) ? $_REQUEST[ $field ] : '';

            if ($value) {
                update_post_meta($post_id, $field, $value);
            } else {
                delete_post_meta($post_id, $field);
            }
        }
    }

    public function extendSettings($optionSections)
    {
        $option = [
            'wpg_glossary_linkify_synonym_limit' => [
                'name' => 'wpg_glossary_linkify_synonym_limit',
                'label' => 'Linkify Limit per Synonym',
                'type' => 'number',
                'desc' => 'Same as linkify limit for terms, but applied to synonyms.',
            ]
        ];

        $optionSections['section_linkify']['options'] = push_at_to_associative_array($optionSections['section_linkify']['options'], 'wpg_glossary_linkify_term_limit', $option);
        return $optionSections;
    }

    public function extendMenu()
    {
        add_submenu_page(
            'edit.php?post_type=glossary',
            __('Add New Synonym', 'wp-glossary-synonyms'),
            __('Add New Synonym', 'wp-glossary-synonyms'),
            'manage_options',
            'post-new.php?post_type=' . PLugin::POST_TYPE
        );
    }
}
