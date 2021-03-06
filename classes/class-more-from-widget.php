<?php
/**
 * More From Widget
 *
 * @package gb-more-from-widget
 */

namespace GB\Widget;

/**
 * Related Posts Block
 */
class More_From {

	/**
	 * Initialization
	 */
	public function __construct() {

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );

		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );

		add_action( 'init', [ $this, 'register_block' ] );

	}

	/**
	 * Enqueue block editor assets
	 *
	 * @since 1.0
	 */
	public function enqueue_block_editor_assets() {

		global $post;

		wp_enqueue_script(
			'gbmf-js',
			plugins_url( 'assets/js/block.build.js', __DIR__ ),
			[ 'wp-blocks', 'wp-i18n', 'wp-element', 'moment' ],
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/block.build.js' )
		);

		wp_localize_script( 'gbmf-js', 'gbmfObject', [
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( 'gbmf_nonce' ),
			'post_id'    => $post->ID,
		] );

		wp_enqueue_style(
			'gbmf-editor-style',
			plugins_url( 'assets/css/editor.css', __DIR__ ),
			[ 'wp-edit-blocks' ],
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/css/editor.css' )
		);

	}

	/**
	 * Enqueue block assets
	 *
	 * @since 1.0
	 */
	public function enqueue_block_assets() {

		wp_enqueue_style(
			'gbmf-style',
			plugins_url( 'assets/css/style.css', __DIR__ ),
			[ 'wp-blocks' ],
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/css/style.css' )
		);

	}

	/**
	 * Get posts by category
	 *
	 * @since 1.0
	 *
	 * @param int $cat_id       A Category ID.
	 * @param int $num_of_posts A number of posts needed. Default is 3.
	 *
	 * @return bool|array
	 */
	protected function _get_more_from( $cat_id, $num_of_posts = 3 ) {

		if ( empty( $cat_id ) || ! is_numeric( $cat_id ) ) {
			return false;
		}

		$posts = get_posts( [
			'category'            => intval( $cat_id ),
			'posts_per_page'      => $num_of_posts,
			'ignore_sticky_posts' => true,
			'surpress_filters'    => false,
		] );

		if ( empty( $posts ) || ! is_array( $posts ) ) {
			return false;
		}

		return $posts;

	}

	/**
	 * Register related posts block
	 *
	 * @since 1.0
	 */
	public function register_block() {

		register_block_type(
			'gb/more-from-widget', [
				'attributes' => [
					'title'                => [
						'type'    => 'string',
						'default' => __( 'More From', 'gb-more-from-widget' ),
					],
					'category'             => [
						'type'    => 'string',
						'default' => '',
					],
					'postsToShow'          => [
						'type'    => 'number',
						'default' => 3,
					],
					'displayPostDate'      => [
						'type'    => 'boolean',
						'default' => false,
					],
					'layout'               => [
						'type'    => 'string',
						'default' => 'list',
					],
					'columns'              => [
						'type'    => 'number',
						'default' => 3,
					],
					'displayPostThumbnail' => [
						'type'    => 'boolean',
						'default' => false,
					],
				],

				'render_callback' => [ $this, 'render_block' ],
			]
		);

	}

	/**
	 * Renders the 'gb/more-from-widget' block on front-end.
	 *
	 * @since 1.0
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @return string Returns related posts content.
	 */
	function render_block( $attributes ) {

		if ( empty( $attributes['category'] ) || ! is_numeric( $attributes['category'] ) ) {
			return;
		}

		$more_posts = $this->_get_more_from( $attributes['category'], $attributes['postsToShow'] );

		$list_items_markup = '';

		foreach ( $more_posts as $post ) {

			$list_items_markup .= "<li>\n";

			if ( isset( $attributes['displayPostThumbnail'] ) && $attributes['displayPostThumbnail'] ) {

				$thumbnail = get_the_post_thumbnail_url( $post->ID );

				if ( ! empty( $thumbnail ) ) {
					$list_items_markup .= sprintf(
						'<img src="%s" />',
						esc_url( $thumbnail )
					);
				}
			}

			$title = get_the_title( $post->ID );

			$list_items_markup .= sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( get_permalink( $post->ID ) ),
				esc_html( $title )
			);

			if ( isset( $attributes['displayPostDate'] ) && $attributes['displayPostDate'] ) {

				$list_items_markup .= sprintf(
					'<time datetime="%1$s" class="post-date">%2$s</time>',
					esc_attr( get_the_date( 'c', $post->ID ) ),
					esc_html( get_the_date( '', $post->ID ) )
				);

			}

			$list_items_markup .= "</li>\n";

		}

		$class = '';

		if ( isset( $attributes['layout'] ) && 'grid' === $attributes['layout'] ) {
			$class .= 'is-grid';
		} else {
			$class .= 'is-list';
		}

		if ( isset( $attributes['columns'] ) ) {
			$class .= ' columns-' . $attributes['columns'];
		} else {
			$class .= ' columns-3';
		}

		$title_markup = '';

		if ( ! empty( $attributes['title'] ) ) {
			$title_markup = '<h3 class="more-from-title">' . $attributes['title'] . '</h3>';
		}

		$title_markup .= '</h3>';

		$block_content = sprintf(
			'<div class="wp-block-gb-more-from-widget">%1$s<ul class="%2$s">%3$s</ul></div>',
			$title_markup,
			esc_attr( $class ),
			$list_items_markup
		);

		return $block_content;

	}

}

// Initialize class.
new More_From();
