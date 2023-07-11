<?php

/**
 * This overwrites the parent's main class, adding the synonym custom post type
 * to the filter query and alternative spellings functionality.
 *
 * @class WPG_Linkify
 */

namespace WPGlossarySynonyms;

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
    public $term_limit = 0;
    public $synonym_limit = 0;
    public $is_term_limit_for_full_page = '';
    public $is_case_sensitive = '';
    public $is_tooltip = '';
    public $is_tooltip_content_shortcode = '';
    public $is_tooltip_content_read_more = '';
    public $disabled_linkify_on_posts = array();
    public $disabled_tooltip_on_posts = array();
    public $glossary_terms = array();
    public $glossary_term_titles = array();
    public $replaced_terms = array();
    public $replaced_synonyms = array();
    public $term_spellings = array();
    public $synonym_spellings = array();

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        add_action('wp', array($this, 'setup_vars'));
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
        $this->glossary_terms = wpg_glossary_terms('linkify');
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
        $this->term_limit = get_option('wpg_glossary_linkify_term_limit') ?: -1;
        $this->synonym_limit = get_option('wpg_glossary_linkify_synonym_limit') ?: -1;
        $this->is_term_limit_for_full_page = wpg_glossary_is_linkify_limit_for_full_page();
    }

    /**
     * Format Glossary Terms Array
     *
     * Overwrites parent's method to allow linkifying spellings using the separate
     * post meta field.
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
            $spellings = array_filter(explode(',', get_post_meta($glossary_term->ID, Plugin::ALTERNATIVE_SPELLINGS, true)));
            foreach ($spellings as $spelling) {
                $wpg_glossary_terms_key[] = $this->format_glossary_term_string($spelling);
            }

            $wpg_glossary_terms_key = implode("|", $wpg_glossary_terms_key);

            if (!isset($wpg_glossary_terms[$wpg_glossary_terms_key])) {
                $wpg_glossary_terms[$wpg_glossary_terms_key] = $glossary_term;
            }

            // Collect available spellings
            if ($glossary_term->post_type === Plugin::PARENT_POST_TYPE) {
                $this->term_spellings = array_merge($this->term_spellings, $spellings);
            }
            if ($glossary_term->post_type === Plugin::POST_TYPE) {
                $this->synonym_spellings = array_merge($this->synonym_spellings, $spellings);
            }
        }

        $this->glossary_terms = $wpg_glossary_terms;
    }

    /**
     * Init Linkify
     */
    public function init_linkify()
    {
        // Check if Linkify is enabled or not
        if (!$this->is_active) {
            return;
        }

        if (empty($this->linkify_sections)) {
            return;
        }

        if (empty($this->glossary_terms)) {
            return;
        }

        // Linkify Full Description
        if (in_array('post_content', $this->linkify_sections)) {
            if (!wpg_glossary_is_bp_page()) {
                remove_filter('the_content', array('WPG_Linkify', 'linkify_content'), 13, 2);
                add_filter('the_content', array($this, 'linkify_content'), 13, 2);
            }
        }

        // Linkify Short Description
        if (in_array('post_excerpt', $this->linkify_sections)) {
            add_filter('the_excerpt', array($this, 'linkify_content'), 13, 2);
        }

        // Linkify Categories / Terms Description
        if (in_array('term_content', $this->linkify_sections)) {
            add_filter('term_description', array($this, 'linkify_term_content'), 13, 2);
        }

        // Linkify Widget
        if (in_array('widget', $this->linkify_sections)) {
            add_filter('widget_text', array($this, 'linkify_widget'), 13, 2);
        }

        // Linkify Comment
        if (in_array('comment', $this->linkify_sections)) {
            add_filter('get_comment_text', array($this, 'linkify_comment'), 13, 2);
            add_filter('get_comment_excerpt', array($this, 'linkify_comment'), 13, 2);
        }
    }

    /**
     * Replace Matching Terms
     */
    public function preg_replace_matches($match)
    {
        if (!empty($match[0])) {
            $title = htmlspecialchars_decode($match[0], ENT_COMPAT);
            $glossary_term = array();
            if (!empty($this->glossary_terms)) {
                $title_index = $this->format_glossary_term_string($title);
                // First - look for exact keys
                if (array_key_exists($title_index, $this->glossary_terms)) {
                    $glossary_term = $this->glossary_terms[$title_index];
                } else {
                    // If not found - try the tags
                    foreach ($this->glossary_terms as $key => $value) {
                        if (strstr($key, '|') && strstr($key, $title_index)) {
                            $glossary_term_tags = explode('|', $key);
                            if (in_array($title_index, $glossary_term_tags)) {
                                $glossary_term = $value;
                                break;
                            }
                        }
                    }
                }
            }

            if (!empty($glossary_term)) {
                if (
                    ($glossary_term->post_type === Plugin::POST_TYPE && $this->synonym_limit > 0) ||
                    ($glossary_term->post_type === Plugin::PARENT_POST_TYPE && $this->term_limit > 0)
                ) {
                    if (
                        $glossary_term->post_type === Plugin::POST_TYPE ||
                        in_array($title, $this->synonym_spellings)
                    ) {
                        $this->replaced_synonyms[$glossary_term->ID] = (isset($this->replaced_synonyms[$glossary_term->ID]) && $this->replaced_synonyms[$glossary_term->ID] > 0) ? ($this->replaced_synonyms[$glossary_term->ID] + 1) : 1;
                    } elseif (
                        $glossary_term->post_type === Plugin::PARENT_POST_TYPE ||
                        in_array($title, $this->term_spellings)
                    ) {
                        $this->replaced_terms[$glossary_term->ID] = (isset($this->replaced_terms[$glossary_term->ID]) && $this->replaced_terms[$glossary_term->ID] > 0) ? ($this->replaced_terms[$glossary_term->ID] + 1) : 1;
                    }
                    if (
                        ($glossary_term->post_type === Plugin::POST_TYPE && $this->replaced_synonyms[$glossary_term->ID] > $this->synonym_limit) ||
                        ($glossary_term->post_type === Plugin::PARENT_POST_TYPE && $this->replaced_terms[$glossary_term->ID] > $this->term_limit)
                    ) {
                        return $title;
                    }
                }

                global $post;
                $current_post = $post;
                $post = $glossary_term;
                setup_postdata($post);

                $title_place_holder = '##TITLE_GOES_HERE##';

                if ($this->is_disable_link) {
                    $href = '';
                } else {
                    $href = 'href="' . esc_url(get_permalink()) . '"';
                }

                if ($this->is_tooltip && !(!empty($this->disabled_tooltip_on_posts) && isset($current_post->ID) && in_array($current_post->ID, $this->disabled_tooltip_on_posts))) {
                    $attr_title = wpg_glossary_get_tooltip_content($this->is_tooltip_content_shortcode, $this->is_tooltip_content_read_more);

                    $new_text = '<a class="wpg-linkify wpg-tooltip" title="' . $attr_title . '" ' . $href . ' ' . ($this->is_new_tab ? 'target="_blank"' : '') . '>' . $title_place_holder . '</a>';
                } else {
                    $new_text = '<a class="wpg-linkify" ' . $href . ' ' . ($this->is_new_tab ? 'target="_blank"' : '') . '>' . $title_place_holder . '</a>';
                }

                wp_reset_postdata();

                $new_text = str_replace($title_place_holder, $title, $new_text);
                return $new_text;
            }
        }
    }
}
