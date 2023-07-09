<?php

/**
 * This overwrites the parent's main class, adding the spellings fields
 * to glossary terms.
 *
 * @class WPG_Linkify
 */

namespace WPGlossarySynonyms;

if (!class_exists('WPG_Post_Types')) {
    die('Could not find or load class WPG_Post_Types from parent plugin.');
}

use WPG_Post_Types;

class PostTypes extends WPG_Post_Types
{
    public function __construct()
    {
        parent::__construct();

        // Custom Meta Boxes
        add_action('add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ));
        add_action('save_post', array( __CLASS__, 'save_meta_boxes' ));
    }

    public static function add_meta_boxes()
    {
        add_meta_box('meta-box-glossary-attributes', __('Custom Attributes', WPG_TEXT_DOMAIN), array(__CLASS__, 'meta_box_glossary_attributes'), 'glossary', 'normal', 'high');
    }

    /**
     * Custom Meta Box Callback - Glossary Custom Attributes
     */
    public static function meta_box_glossary_attributes($post)
    {
        wp_nonce_field('wpg_meta_box', 'wpg_meta_box_nonce');

        ?><table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="custom_post_title"><?php _e('Post Title', WPG_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="text" class="large-text" id="custom_post_title" name="custom_post_title" value="<?php echo esc_attr(get_post_meta($post->ID, 'custom_post_title', true)); ?>" />
                        <p class="description"><?php _e('This option allows you to use custom post title for current glossary term. This option works with glossary details page and tooltip only.', WPG_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="custom_post_permalink"><?php _e('Custom URL', WPG_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="text" class="large-text" id="custom_post_permalink" name="custom_post_permalink" value="<?php echo esc_attr(get_post_meta($post->ID, 'custom_post_permalink', true)); ?>" />
                        <p class="description"><?php _e('This option allows you to use external URL for current glossary term. This option is usefull when you want user to redirect on wikipedia or some other dictionary URL for this particular term rather than having complete description on your website.', WPG_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="<?php echo Plugin::ALTERNATIVE_SPELLINGS; ?>"><?php _e('Alternative spellings', WPG_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="text" class="large-text" id="<?php echo Plugin::ALTERNATIVE_SPELLINGS; ?>" name="<?php echo Plugin::ALTERNATIVE_SPELLINGS; ?>" value="<?php echo get_post_meta($post->ID, Plugin::ALTERNATIVE_SPELLINGS, true); ?>" />
                        <p class="description"><?php _e('You can define multiple comma separated spellings here.', WPG_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
            </tbody>
        </table><?php
    }
}