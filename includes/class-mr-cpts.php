<?php

/**
 * Register new CPT's
 */
class MR_CPTS {
	private static $_instance;

	/**
	 * Setup actions and filters. This is a singleton.
	 *
	 * @since 0.1
	 * @uses add_action, add_filter
	 * @return MR_CPTS
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'action_register_review' ) );
		add_filter( 'post_updated_messages', array( $this, 'filter_post_updated_messages' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );
		add_filter( 'manage_mr_review_posts_columns' , array( $this, 'filter_columns' ) );
		add_action( 'manage_mr_review_posts_custom_column' , array( $this, 'action_custom_columns' ), 10, 2 );
		add_filter( 'enter_title_here', array( $this, 'filter_enter_title_here' ), 10, 2 );
		add_action( 'admin_print_footer_scripts', array( $this, 'action_admin_print_footer_scripts' ) );
	}

	/**
	 * Change excerpt title text
	 *
	 * @since 0.1
	 * @uses get_post_type, _e
	 * @return void
	 */
	public function action_admin_print_footer_scripts() {
		global $post, $pagenow;

		if ( ( 'post.php' == $pagenow || 'post-new.php' == $pagenow ) && 'mr_review' == get_post_type( $post->ID ) ) {
		?>
			<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$( '#postexcerpt h3 span' ).html( "<?php _e( 'Review Excerpt', 'my-reviews' ); ?>" );
			} );
			</script>
		<?php
		}
	}

	/**
	 * Change title text box label
	 *
	 * @param string $label
	 * @since 0.1
	 * @uses get_post_type
	 * @return string
	 */
	public function filter_enter_title_here( $label, $post = 0 ) {
		if ( 'mr_review' != get_post_type( $post->ID ) )
			return $label;

		return __( 'Enter reviewer name here', 'my-reviews' );
	}

	/**
	 * Registers post type for review
	 *
	 * @since 0.1
	 * @uses register_post_type, __, plugins_url
	 * @return void
	 */
	public function action_register_review() {
		$labels = array(
			'name' => __( 'Reviews', 'my-reviews' ),
			'singular_name' => __( 'Review', 'my-reviews' ),
			'add_new' => __( 'Add New', 'my-reviews' ),
			'add_new_item' => __( 'Add New Review', 'my-reviews' ),
			'edit_item' => __( 'Edit Review', 'my-reviews' ),
			'new_item' => __( 'New Review', 'my-reviews' ),
			'all_items' => __( 'All Reviews', 'my-reviews' ),
			'view_item' => __( 'View Review', 'my-reviews' ),
			'search_items' => __( 'Search Reviews', 'my-reviews' ),
			'not_found' => __( 'No reviews found', 'my-reviews' ),
			'not_found_in_trash' => __( 'No reviews found in trash', 'my-reviews' ),
			'parent_item_colon' => '',
			'menu_name' => __( 'Reviews', 'my-reviews' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => true, 
			'query_var' => true,
			'rewrite' => array( 'slug' => 'reviews' ),
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => false,
			'register_meta_box_cb' => array( $this, 'add_metaboxes' ),
			'menu_position' => null,
			'menu_icon' => plugins_url( 'img/menu-icon.png' , dirname( __FILE__ ) ),
			'supports' => array( 'title', 'editor', 'excerpt' )
		); 

		register_post_type( 'mr_review', $args );
	}

	/**
	 * Save information associated with CPT
	 *
	 * @param int $post_id
	 * @since 0.1
	 * @uses current_user_can, get_post_type, wp_verify_nonce, update_post_meta, deleta_post_meta
	 * @return void
	 */
	public function action_save_post( $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'revision' == get_post_type( $post_id ) )
			return;

		if ( ! empty( $_POST['mr_additional_options'] ) && wp_verify_nonce( $_POST['mr_additional_options'], 'mr_additional_options_action' ) ) {
			if ( ! empty( $_POST['mr_featured'] ) ) {
				update_post_meta( $post_id, 'mr_featured', 1 );
			} else {
				delete_post_meta( $post_id, 'mr_featured' );
			}
		}

		if ( ! empty( $_POST['mr_review_details'] ) && wp_verify_nonce( $_POST['mr_review_details'], 'mr_review_details_action' ) ) {

		}
		
	}

	/**
	 * Displays custom columns
	 *
	 * @param string $column
	 * @param int $post_id
	 * @uses get_post_meta, esc_html
	 * @return void
	 */
	public function action_custom_columns( $column, $post_id ) {
		if ( 'mr_featured' == $column ) {
			$featured = get_post_meta( $post_id, 'mr_featured', true );
			echo ( ! empty( $featured ) ) ? __( 'Yes', 'my-reviews' ) : __( 'No', 'my-reviews' );
		}
	}
	
	/**
	 * Add new columns
	 *
	 * @param array $columns
	 * @return array
	 */
	public function filter_columns( $columns ) {
		$columns['mr_featured'] = __( 'Featured', 'my-reviews' );
		$columns['title'] = __( 'Reviewer', 'my-reviews' );

		unset($columns['author']);
		
		// Move date column to the back
		unset($columns['date']);
		$columns['date'] = __( 'Date', 'my-reviews' );
		
		return $columns;
	}

	/**
	 * Register metaboxes
	 *
	 * @since 0.1
	 * @uses add_meta_box, __
	 * @return void
	 */
	public function add_metaboxes() {
		add_meta_box( 'mr_additional_options', __( 'Additional Options', 'my-reviews' ), array( $this, 'meta_box_additional_options' ), 'mr_review', 'side', 'default' );
		add_meta_box( 'mr_review_details', __( 'Review Details', 'my-reviews' ), array( $this, 'meta_box_review_details' ), 'mr_review', 'normal', 'core' );
	}

	/**
	 * Output additional options metabox
	 *
	 * @param object $post
	 * @since 0.1
	 * @uses wp_nonce_field, checked
	 * @return void
	 */
	public function meta_box_additional_options( $post ) {
		wp_nonce_field( 'mr_additional_options_action', 'mr_additional_options' );

		$featured = get_post_meta( $post->ID, 'mr_featured', true );
	?>
		<p>
			<label for="mr_featured">Featured Review:</label> <input type="checkbox" id="mr_featured" name="mr_featured" value="1" <?php checked( 1, (int) $featured ); ?> />
		</p>
	<?php
	}

	/**
	* Output review details meta box
	*
	* @param object $post
	* @since 0.1
	* @uses wp_nonce_field, esc_attr
	* @return void
	*/
	public function meta_box_review_details( $post ) {
		wp_nonce_field( 'mr_review_details_action', 'mr_review_details' );

	}

	/**
	 * Filter CPT messages
	 *
	 * @param array $messages
	 * @since 0.1
	 * @uses get_permalink, esc_url, wp_post_revision_title, __, add_query_arg
	 * @return array
	 */
	public function filter_post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['mr_review'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( 'Review updated. <a href="%s">View review</a>', 'my-reviews' ), esc_url( get_permalink( $post_ID ) ) ),
			2 => __( 'Custom field updated.', 'my-reviews' ),
			3 => __( 'Custom field deleted.', 'my-reviews' ),
			4 => __( 'Review updated.', 'my-reviews' ),
			5 => isset( $_GET['revision']) ? sprintf( __(' Review restored to revision from %s', 'my-reviews' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( 'Review published. <a href="%s">View review</a>', 'my-reviews' ), esc_url( get_permalink( $post_ID) ) ),
			7 => __( 'Review saved.', 'my-reviews' ),
			8 => sprintf( __( 'Review submitted. <a target="_blank" href="%s">Preview review</a>', 'my-reviews' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9 => sprintf( __( 'Review scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview review</a>', 'my-reviews' ),
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( 'Review draft updated. <a target="_blank" href="%s">Preview review</a>', 'my-reviews'), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}

	/**
	 * Initialize class and return an instance of it
	 *
	 * @since 0.1
	 * @return MR_CPTS
	 */
	public function init() {
		if ( ! isset( self::$_instance ) ) {

			self::$_instance = new MR_CPTS;
		}

		return self::$_instance;
	}
}

MR_CPTS::init();
