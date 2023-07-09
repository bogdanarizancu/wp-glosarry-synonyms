<?php

/**
 * This overwrites the parent's main class, adding the synonym custom post type
 * to the filter query and alternative spellings functionality.
 *
 * @class WPG_Linkify
 */

namespace WPGlossarySynonyms;

if (!class_exists('WPG_Linkify')) {
    die('Could not find or load class WPG_Linkify from parent plugin.');
}

use WPG_Linkify;

class Linkify extends WPG_Linkify
{
    /**
     * @vars
     */
    public $is_active = '';
    public $is_linkify_tags = '';
    public $exclude_html_tags = '';
    public $is_disable_link = '';
    public $is_new_tab = '';
    public $linkify_sections = '';
    public $linkify_post_types = '';
    public $is_on_front_page = '';
    public $term_limit = '';
    public $is_term_limit_for_full_page = '';
    public $is_case_sensitive = '';
    public $is_tooltip = '';
    public $is_tooltip_content_shortcode = '';
    public $is_tooltip_content_read_more = '';
    public $disabled_linkify_on_posts = array();
    public $disabled_tooltip_on_posts = array();
    public $glossary_terms = '';
    public $glossary_term_titles = '';
    public $replaced_terms = array();

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        // Setup Linkify Vars
        add_action('wp', array($this, 'setup_vars'));
        // Initiate Linkify
        add_action('wp', array($this, 'init_linkify'));
    }

    /**
     * Setup Linkify Vars
     */
    public function setup_vars()
    {
        $this->is_active = wpg_glossary_is_linkify();
        if (!$this->is_active) {
            return;
        }

        $this->is_on_front_page = wpg_glossary_is_linkify_on_front_page();
        if (is_front_page() && !$this->is_on_front_page) {
            return;
        }

        $this->linkify_sections = wpg_glossary_get_linkify_sections();
        if (empty($this->linkify_sections)) {
            return;
        }

        $this->is_case_sensitive = wpg_glossary_is_linkify_case_sensitive();
        $this->is_linkify_tags = wpg_glossary_is_linkify_tags();
        $this->exclude_html_tags = wpg_glossary_get_linkify_exclude_html_tags();
        if ($this->exclude_html_tags != '') {
            $this->exclude_html_tags = explode(',', $this->exclude_html_tags);
            foreach ($this->exclude_html_tags as $key => $html_tag) {
                $html_tag = trim($html_tag);
                if ($html_tag != '') {
                    $this->exclude_html_tags[$key] = '[not(ancestor::' . $html_tag . ')]';
                }
            }

            $this->exclude_html_tags = implode('', $this->exclude_html_tags);
        }

        $post_types = get_post_types(array('public' => true));
        // Disabled Linkify on Posts
        $this->disabled_linkify_on_posts = get_posts(
            array(
                'fields' => 'ids',
                'post_type' => $post_types,
                'numberposts' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'wpg_disable_linkify',
                        'value' => '1',
                    ),
                ),
            )
        );
        // Disabled Tooltip on Posts
        $this->disabled_tooltip_on_posts = get_posts(
            array(
                'fields' => 'ids',
                'post_type' => $post_types,
                'numberposts' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'wpg_disable_tooltip',
                        'value' => '1',
                    ),
                ),
            )
        );
        add_filter('wpg_glossary_terms_query_args', array($this, 'querySynonyms'));
        $this->glossary_terms = wpg_glossary_terms('linkify');
        remove_filter('wpg_glossary_terms_query_args', array($this, 'querySynonyms'));
        if (empty($this->glossary_terms)) {
            return;
        }

        usort($this->glossary_terms, array($this, 'sort_glossary_terms'));
        $this->format_glossary_terms();
        if (empty($this->glossary_terms)) {
            return;
        }

        $this->glossary_term_titles = array_keys($this->glossary_terms);
        $this->is_tooltip = wpg_glossary_is_tooltip();
        $this->is_tooltip_content_shortcode = wpg_glossary_is_tooltip_content_shortcode();
        $this->is_tooltip_content_read_more = wpg_glossary_is_tooltip_content_read_more();
        $this->is_new_tab = wpg_glossary_is_linkify_new_tab();
        $this->is_disable_link = wpg_glossary_is_linkify_disable_link();
        $this->linkify_post_types = wpg_glossary_get_linkify_post_types();
        $this->term_limit = get_option('wpg_glossary_linkify_synonym_limit') ?: -1;
        $this->is_term_limit_for_full_page = wpg_glossary_is_linkify_limit_for_full_page();
    }

    public function querySynonyms($args)
    {
        $args['post_type'] = ['glossary', 'glossary-synonym'];
        return $args;
    }

    /**
     * Format Glossary Terms Array
     *
     * Overwrites parent's method to allow linkifying spellings using the separate
     *  post meta field called 'synonym_spellings'.
     */
    public function format_glossary_terms()
    {
        $wpg_glossary_terms = array();
        global $post;
        foreach ($this->glossary_terms as $glossary_term) {

            /**
             * This remains commented out.
             */
            //if ($post->ID === $glossary_term->ID) {
                // continue;
            //}

            $wpg_glossary_terms_key = array();

            // Term Title
            $wpg_glossary_terms_key[] = $this->format_glossary_term_string($glossary_term->post_title);

            /**
             * Taking out the tags functionality since we are using spellings instead.
             */
            // // Term Tags
            // if ($this->is_linkify_tags && !empty($glossary_term->terms)) {
            //  foreach ($glossary_term->terms as $key => $term) {
            //      $wpg_glossary_terms_key[] = $this->format_glossary_term_string($term);
            //  }
            // }

            // Spellings post meta.
            $spellings = explode(',', get_post_meta($glossary_term->ID, 'synonym_spellings', true));
            foreach ($spellings as $spelling) {
                $wpg_glossary_terms_key[] = $this->format_glossary_term_string($spelling);
            }

            $wpg_glossary_terms_key = implode("|", $wpg_glossary_terms_key);

            if (!isset($wpg_glossary_terms[$wpg_glossary_terms_key])) {
                $wpg_glossary_terms[$wpg_glossary_terms_key] = $glossary_term;
            }
        }

        $this->glossary_terms = $wpg_glossary_terms;
    }
}
