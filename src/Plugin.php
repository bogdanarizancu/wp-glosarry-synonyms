<?php

/**
 * @file
 * Contains \WPGlossarySynonyms\Plugin.
 */

namespace WPGlossarySynonyms;

class Plugin
{
    /**
     * Prefix for naming.
     *
     * @var string
     */
    const PREFIX = 'wp-glossary-synonyms';

    /**
     * Custom post type slug.
     *
     * @var string
     */
    const POST_TYPE = 'glossary-synonym';

    /**
     * Glossary term custom post type slug.
     *
     * @var string
     */
    const PARENT_POST_TYPE = 'glossary';

    /**
     * Gettext localization domain.
     *
     * @var string
     */
    const L10N = self::PREFIX;

    /**
     * Alternative spellings post meta key.
     *
     * @var string
     */
    const ALTERNATIVE_SPELLINGS = 'alternative_spellings';

    /**
     * Plugin initialization method.
     */
    public function init()
    {
        $this->registerSynonymPostType();

        add_filter('wpg_glossary_terms_query_args', array($this, 'querySynonyms'));

        new PostTypes();

        add_action('admin_menu', array($this, 'hideTaxonomies'));

        if (is_admin()) {
            return;
        }

        // Instantiate class only for frontend, since that is where the limit counter is used.
        new Linkify();

        // Replace synonyms permalink with parent glossary term permalink, as requested
        add_filter('post_type_link', array($this, 'replacePermalink'), 10, 2);

        // Get parent post excerpt if synonym excerpt is empty
        add_filter('wpg_tooltip_excerpt', array($this, 'maybeReplaceExcerpt'));

        //  Custom functionality for sysnonyms to show the parent term title in brackets.
        add_filter('wpg_tooltip_term_title_end', function ($title) {
            $postID = get_the_ID();
            if (get_post_type($postID) === self::POST_TYPE && $associatedTerm = $this->getAssociatedTerm($postID)) {
                return ' <br/><a style="color: inherit;" class="main-term" href="' . get_permalink($associatedTerm) . '">(' . $associatedTerm->post_title . ')</a>' . $title;
            }
            return $title;
        }, 10, 2);

        // Include sysnonym post type in frontend query.
        add_filter('wpg_list_query_args', function ($args) {
            $args['post_type'] = [$args['post_type'], self::POST_TYPE];
            return $args;
        });

        // Add shortcode functionliaty to list all synonyms, comma separated.
        add_shortcode('wpgs_list', array($this, 'setShortcode'));
    }

    private function getAssociatedTerm($synonymID)
    {
        $associatedTermID = get_post_meta($synonymID, 'associated_term', true);
        return get_post($associatedTermID);
    }

    /**
     * Loads the plugin textdomain.
     */
    public function loadTextdomain()
    {
        load_plugin_textdomain(self::PREFIX, false, basename(__DIR__) . '/languages/');
    }

    public function registerSynonymPostType()
    {
        $labels = [
            'name' => _x('Glosary synonym', 'post type general name', 'wp-glossary-synonyms'),
            'singular_name' => _x('Glosary synonym', 'post type singular name', 'wp-glossary-synonyms'),
            'menu_name' => _x('Glosary synonym', 'admin menu', 'wp-glossary-synonyms'),
            'name_admin_bar' => _x('Glosary synonym', 'add new on admin bar', 'wp-glossary-synonyms'),
            'add_new' => _x('Add New Synonym', 'glossary', 'wp-glossary-synonyms'),
            'add_new_item' => __('Add New Synonym', 'wp-glossary-synonyms'),
            'new_item' => __('New Synonym', 'wp-glossary-synonyms'),
            'edit_item' => __('Edit Synonym', 'wp-glossary-synonyms'),
            'view_item' => __('View Synonym', 'wp-glossary-synonyms'),
            'all_items' => __('All Synonyms', 'wp-glossary-synonyms'),
            'search_items' => __('Search Synonyms', 'wp-glossary-synonyms'),
            'parent_item_colon' => __('Parent Synonyms:', 'wp-glossary-synonyms'),
            'not_found' => __('No synonyms found.', 'wp-glossary-synonyms'),
            'not_found_in_trash' => __('No synonyms found in Trash.', 'wp-glossary-synonyms')
        ];

        $args = apply_filters(
            'wpg_post_type_glossary_args',
            [
                'labels' => $labels,
                'description' => __('Description.', 'wp-glossary-synonyms'),
                'menu_icon' => 'dashicons-editor-spellcheck',
                'capability_type' => 'post',
                'rewrite' => false,
                'public' => false,
                'publicly_queryable' => false,
                'show_ui' => true,
                'show_in_nav_menus' => false,
                'show_in_menu' => 'edit.php?post_type=glossary',
                // 'show_in_menu' => true,
                'query_var' => true,
                'has_archive' => false,
                'hierarchical' => false,
                'menu_position' => 59,
                'supports' => array('title', 'excerpt', 'author'),
                'register_meta_box_cb' => [__CLASS__, 'add_meta_boxes']
            ]
        );

        register_post_type(self::POST_TYPE, $args);
    }

    public static function add_meta_boxes()
    {
        add_meta_box(
            'meta-box-glossary-attributes',
            __('Custom Attributes', 'wp-glossary-synonyms'),
            [__CLASS__, 'meta_box_glossary_synonym_attributes'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function meta_box_glossary_synonym_attributes($post)
    {
        wp_nonce_field('wpgs_meta_box', 'wpgs_meta_box_nonce');

        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="associated_glossary_term">
                            <?php _e('Associated glossary term', 'wp-glossary-synonyms'); ?>
                        </label></th>
                    <td>
                        <?php
                        /**
                         * Since dropdown pages only works with hierarchical defined custom post types,
                         * and `glossary` is defined as non hierachical in the parent plugin,
                         * fake it to be hierarchical and revert after dropdown output.
                         */
                        global $wp_post_types;
                        $selected_post_id = get_post_meta($post->ID, 'associated_term', true);
                        $save_hierarchical = $wp_post_types[self::PARENT_POST_TYPE]->hierarchical;
                        $wp_post_types[self::PARENT_POST_TYPE]->hierarchical = true;
                        wp_dropdown_pages(
                            array(
                                'name' => 'associated_term',
                                'selected' => empty($selected_post_id) ? 0 : $selected_post_id,
                                'post_type' => self::PARENT_POST_TYPE,
                                'show_option_none' => 'None selected',
                            )
                        );
                        $wp_post_types[self::PARENT_POST_TYPE]->hierarchical = $save_hierarchical;
                        ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="spellings">
                            <?php _e('Alternative spellings', 'wp-glossary-synonyms'); ?>
                        </label></th>
                    <td>
                        <input type="text" class="large-text" name="<?php echo Plugin::ALTERNATIVE_SPELLINGS; ?>" value="<?php echo esc_attr(get_post_meta($post->ID, Plugin::ALTERNATIVE_SPELLINGS, true)); ?>" />
                        <p class="description">
                            <?php _e('You can define multiple comma separated spellings here', 'wp-glossary-synonyms'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function setShortcode($args)
    {
        $query_args = array(
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'fields' => ['post_title'],
            'orderby' => 'title',
            'order' => 'ASC'
        );
        if (!empty($args['term_id'])) {
            $query_args['meta_key'] = 'associated_term';
            $query_args['meta_value'] = $args['term_id'];
        }
        $synonyms = wp_list_pluck(get_posts($query_args), 'post_title');
        return '<p>' . implode(', ', $synonyms) . '</p>';
    }

    public function replacePermalink($permalink, $post)
    {
        if ($post->post_type !== self::POST_TYPE) {
            return $permalink;
        }
        $associatedTerm = $this->getAssociatedTerm($post->ID);

        return $associatedTerm ? get_permalink($associatedTerm) : $permalink;
    }

    public function maybeReplaceExcerpt($content)
    {
        global $post;
        if ($post->post_type === self::POST_TYPE && empty($post->post_excerpt)) {
            $associatedTerm = $this->getAssociatedTerm($post->ID);
            return $associatedTerm->post_excerpt;
        }
        return $content;
    }

    public function querySynonyms($args)
    {
        $args['post_type'] = ['glossary', 'glossary-synonym'];
        return $args;
    }

    public function hideTaxonomies()
    {
        remove_submenu_page('edit.php?post_type=glossary', 'edit-tags.php?taxonomy=glossary_cat&amp;post_type=glossary');
        remove_submenu_page('edit.php?post_type=glossary', 'edit-tags.php?taxonomy=glossary_tag&amp;post_type=glossary');
    }
}
