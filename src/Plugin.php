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
	const PREFIX = 'wp-glossary-synonym';

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
	 * Plugin initialization method.
	 */
	public function init()
	{
		$this->includeSynonymPostType();
		$this->registerSynonymPostType();

		if (is_admin()) {
			return;
		}

		add_filter('wpg_tooltip_term_title_end', function ($title) {
			$postID = get_the_ID();
			if (get_post_type($postID) === self::POST_TYPE) {
				$associatedGlossary = get_post(8);
				return ' <br/><small>(<a style="color: inherit;" href="' . get_permalink(8) . '">' . $associatedGlossary->post_title . '</a>)</small>'. $title; 
			}
			return $title;
		}, 10, 2);
	}

	/**
	 * Loads the plugin textdomain.
	 */
	public function loadTextdomain()
	{
		load_plugin_textdomain(static::L10N, FALSE, static::L10N . '/languages/');
	}

	public function registerSynonymPostType()
	{
		$labels = [
			'name' => _x('Glosary synonym', 'post type general name', self::PREFIX),
			'singular_name' => _x('Glosary synonym', 'post type singular name', self::PREFIX),
			'menu_name' => _x('Glosary synonym', 'admin menu', self::PREFIX),
			'name_admin_bar' => _x('Glosary synonym', 'add new on admin bar', self::PREFIX),
			'add_new' => _x('Add New Synonym', 'glossary', self::PREFIX),
			'add_new_item' => __('Add New Synonym', self::PREFIX),
			'new_item' => __('New Synonym', self::PREFIX),
			'edit_item' => __('Edit Synonym', self::PREFIX),
			'view_item' => __('View Synonym', self::PREFIX),
			'all_items' => __('All Synonyms', self::PREFIX),
			'search_items' => __('Search Synonyms', self::PREFIX),
			'parent_item_colon' => __('Parent Synonyms:', self::PREFIX),
			'not_found' => __('No synonyms found.', self::PREFIX),
			'not_found_in_trash' => __('No synonyms found in Trash.', self::PREFIX)
		];

		$args = apply_filters(
			'wpg_post_type_glossary_args',
			[
				'labels' => $labels,
				'description' => __('Description.', self::PREFIX),
				'menu_icon' => 'dashicons-editor-spellcheck',
				'capability_type' => 'post',
				'rewrite' => [
					'slug' => self::POST_TYPE,
					'with_front' => false,
				],
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_nav_menus' => false,
				'show_in_menu' => 'edit.php?post_type=glossary',
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
			__('Custom Attributes', WPG_TEXT_DOMAIN),
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
				<!-- <tr>
					<th scope="row"><label for="custom_post_title">
							<?php _e('Post Title', self::PREFIX); ?>
						</label></th>
					<td>
						<input type="text" class="large-text" id="custom_post_title" name="custom_post_title"
							value="<?php echo esc_attr(get_post_meta($post->ID, 'custom_post_title', true)); ?>" />
						<p class="description">
							<?php _e('This option allows you to use custom post title for current glossary term. This option works with glossary details page and tooltip only.', WPG_TEXT_DOMAIN); ?>
						</p>
					</td>
				</tr> -->

				<tr>
					<th scope="row"><label for="associated_glossary_term">
							<?php _e('Associated glossary term', self::PREFIX); ?>
						</label></th>
					<td>
						<?php
						/**
						 * Since dropdown pages only works with hierarchical defined custom post types,
						 * and `glossary` is defined as non hierachical in the parent plugin,
						 * fake it to be hierarchical and revert after dropdown output.
						 */
						global $wp_post_types;
						$save_hierarchical = $wp_post_types[self::PARENT_POST_TYPE]->hierarchical;
						$wp_post_types[self::PARENT_POST_TYPE]->hierarchical = true;
						wp_dropdown_pages(
							array(
								'id' => 'test-id',
								'name' => 'tyest-naem',
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
							<?php _e('Spelings', self::PREFIX); ?>
						</label></th>
					<td>
						<input type="text" class="large-text" name="synonym-spellings"
							value="<?php echo esc_attr(get_post_meta($post->ID, 'custom_post_permalink', true)); ?>" />
						<p class="description">
							<?php _e('You can define multiple comma separated spelings here.', self::PREFIX); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public function includeSynonymPostType()
	{
		add_filter('wpg_list_query_args', function ($args) {
			$args['post_type'] = [$args['post_type'], self::POST_TYPE];
			return $args;
		});
	}
}