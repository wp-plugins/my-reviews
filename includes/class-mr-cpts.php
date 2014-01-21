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
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'action_register_review' ) );
		add_filter( 'post_updated_messages', array( $this, 'filter_post_updated_messages' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );
		add_filter( 'manage_mr_review_posts_columns' , array( $this, 'filter_columns' ) );
		add_action( 'manage_mr_review_posts_custom_column' , array( $this, 'action_custom_columns' ), 10, 2 );
		add_filter( 'enter_title_here', array( $this, 'filter_enter_title_here' ), 10, 2 );
		add_action( 'admin_print_footer_scripts', array( $this, 'action_admin_print_footer_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_styles' ) );
		add_action( 'init', array( $this, 'action_register_image_sizes' ) );
		add_filter( 'post_thumbnail_html', array( $this, 'filter_post_thumbnail_html' ), 10, 5 );
        add_action( 'add_meta_boxes', array( $this, 'action_add_meta_boxes' ) );
        $check = apply_filters( "get_{$meta_type}_metadata", null, $object_id, $meta_key, $single );
        add_filter( 'get_post_metadata', array( $this, 'filter_get_post_metadata' ), 10, 4 );
	}

    /**
     * @param $default
     * @param $object_id
     * @param $meta_key
     * @param $single
     * @since 1.3
     * @return int
     */
    public function filter_get_post_metadata( $default, $object_id, $meta_key, $single ) {
        if ( '_thumbnail_id' == $meta_key ) {
            if ( 'mr_review' == get_post_type( $object_id ) ) {
                $reviewer_image = get_post_meta( $object_id, 'mr_reviewer_image', true );
                if ( ! empty( $reviewer_image ) ) {
                    return -1;
                }
            }
        }

        return $default;
    }

    /**
     *  Register meta boxes
     *
     * @since 1.0
     * @return void
     */
    public function action_add_meta_boxes() {
        if ( 'mr_review' == get_post_type() ) {
            $mr_post_id = get_post_meta( get_the_ID(), 'mr_post_id', true );

            if ( ! empty( $mr_post_id ) )
                add_meta_box( 'mr_wavereview_details', __( 'WaveReview Details', 'my-reviews' ), array( $this, 'wavereview_details_meta_box' ), 'mr_review', 'side' );
        }
    }

    /**
     * Output WaveReview details meta box
     *
     * @param object $post
     * @return void
     */
    public function wavereview_details_meta_box( $post ) {
        $arrival_time = get_post_meta( $post->ID, 'mr_checkin_date', true );
        $departure_time = get_post_meta( $post->ID, 'mr_checkout_date', true );
        $review_time = get_post_meta( $post->ID, 'mr_reviewed', true );
    ?>
        <p><strong><?php _e( 'Arrival Date:', 'my-reviews' ); ?></strong> <?php echo date( 'j/n/Y g:i A', (int) $arrival_time ); ?></p>
        <p><strong><?php _e( 'Departure Date:', 'my-reviews' ); ?></strong> <?php echo date( 'j/n/Y g:i A', (int) $departure_time ); ?></p>
        <p><strong><?php _e( 'Review Date:', 'my-reviews' ); ?></strong> <?php echo date( 'j/n/Y g:i A', (int) $review_time ); ?></p>
    <?php
    }

	/**
	 * Create image sizes
	 *
	 * @uses add_image_size, apply_filters
	 * @since 0.2
	 * @return void
	 */
	public function action_register_image_sizes() {

		$image_size_mappings = array(
			'mr-widget-thumb' => array( 'width' => 45, 'height' => 100, 'crop' => false ),
		);

		$image_size_mappings = apply_filters( 'mr_image_sizes', $image_size_mappings );

		foreach ( $image_size_mappings as $size_name => $size_array ) {
			add_image_size( $size_name, $size_array['width'], $size_array['height'], $size_array['crop'] );
		}
	}

	/**
	 * Setup admin scripts
	 *
	 * @param string $hook
	 * @since 0.2
	 * @uses wp_enqueue_script, wp_localize_script, wp_create_nonce, get_post_type, get_post_meta
	 * @return void
	 */
	public function action_admin_enqueue_scripts( $hook ) {
		if ( ( 'post.php' != $hook || 'post-new.php' != $hook ) && 'mr_review' == get_post_type() ) {
			global $post;

            if ( defined( WP_DEBUG ) && WP_DEBUG )
                $js_path = '/js/admin.js';
            else
                $js_path = '/build/js/admin-concat.min.js';

			wp_enqueue_script( 'mr-admin', plugins_url( $js_path, dirname( __FILE__ ) ), array( 'jquery' ), '1.0', true );
		
			$thumbnail_id = get_post_meta( $post->ID, '_thumbnail_id', true );
			if ( empty( $thumbnail_id ) )
				$thumbnail_id = 0;

			$local_array = array(
				'get_images_nonce' => wp_create_nonce( 'get_images_nonce' ),
				'set_thumbnail_nonce' => wp_create_nonce( 'set_post_thumbnail-' . $post->ID ),
				'thumbnail_id' => (int) $thumbnail_id,
			);
			wp_localize_script( 'mr-admin', 'mr_data', $local_array );
		}
	}

	/**
	 * Setup admin styles
	 *
	 * @param string $hook
	 * @since 0.2
	 * @uses wp_enqueue_style, get_post_type, plugins_url
	 * @return void
	 */
	public function action_admin_enqueue_styles( $hook ) {
		if ( ( 'post.php' != $hook || 'post-new.php' != $hook ) && 'mr_review' == get_post_type() ) {
            if ( defined( WP_DEBUG ) && WP_DEBUG )
                $css_path = '/build/css/admin.css';
            else
                $css_path = '/build/css/admin.min.css';
			wp_enqueue_style( 'mr-admin', plugins_url( $css_path, dirname( __FILE__ ) ) );
		}
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
     * @param int $post
	 * @since 0.1
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
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' )
		); 

		register_post_type( 'mr_review', $args );

        $args = array(
            'hierarchical' => false,
            'show_ui' => false,
            'show_admin_column' => false,
            'query_var' => false,
            'rewrite' => false,
        );

        register_taxonomy( 'mr_review_format', array( 'mr_review' ), $args );
	}

	/**
	 * Save information associated with CPT
	 *
	 * @param int $post_id
	 * @since 0.1
	 * @return void
	 */
	public function action_save_post( $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'revision' == get_post_type( $post_id ) )
			return;

		if ( ! empty( $_POST['mr_additional_options'] ) && wp_verify_nonce( $_POST['mr_additional_options'], 'mr_additional_options_action' ) ) {
			if ( ! empty( $_POST['mr_featured'] ) ) {
                $featured = term_exists( 'featured', 'mr_review_format' );
                if ( empty( $featured ) ) {
                    $featured = wp_insert_term( 'featured', 'mr_review_format' );
                }

                wp_set_post_terms( $post_id, array( (int) $featured['term_id'] ), 'mr_review_format' );
			} else {
                wp_set_post_terms( $post_id, array(), 'mr_review_format' );
			}
		}

		if ( ! empty( $_POST['mr_review_details'] ) && wp_verify_nonce( $_POST['mr_review_details'], 'mr_review_details_action' ) ) {
			
			if ( ! empty( $_POST['mr_email'] ) ) {
				update_post_meta( $post_id, 'mr_email', sanitize_text_field( $_POST['mr_email'] ) );
			} else {
				delete_post_meta( $post_id, 'mr_email' );
			}

            if ( ! empty( $_POST['mr_reviewer_image'] ) ) {
                update_post_meta( $post_id, 'mr_reviewer_image', esc_url_raw( $_POST['mr_reviewer_image'] ) );
            } else {
                delete_post_meta( $post_id, 'mr_reviewer_image' );
            }

            if ( ! empty( $_POST['mr_reviewer_image_type'] ) ) {
                update_post_meta( $post_id, 'mr_reviewer_image_type', sanitize_text_field( $_POST['mr_reviewer_image_type'] ) );
            } else {
                delete_post_meta( $post_id, 'mr_reviewer_image_type' );
            }
		}
		
	}

	/**
	 * Displays custom columns
	 *
	 * @param string $column
	 * @param int $post_id
	 * @since 0.1
	 * @return void
	 */
	public function action_custom_columns( $column, $post_id ) {
		if ( 'mr_featured' == $column ) {
            $featured = has_term( 'featured', 'mr_review_format', $post );
			echo ( ! empty( $featured ) ) ? __( 'Yes', 'my-reviews' ) : __( 'No', 'my-reviews' );
		} else if ( 'mr_review' == $column ) {
            echo mr_truncate_str( get_the_content( $post_id ), 100 );
        } else if ( 'mr_featured_image' == $column ) {
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, 'mr-widget-thumb' );
            } else {
                echo 'None';
            }
        }
	}
	
	/**
	 * Add new columns
	 *
	 * @param array $columns
	 * @since 0.1
	 * @return array
	 */
	public function filter_columns( $columns ) {
        $columns['mr_featured'] = __( 'Featured', 'my-reviews' );
        $columns['mr_review'] = __( 'Review', 'my-reviews' );
		$columns['title'] = __( 'Reviewer', 'my-reviews' );
        $columns['mr_featured_image'] = __( 'Reviewer Image', 'my-reviews' );

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
	 * @uses wp_nonce_field, checked, get_post_meta
	 * @return void
	 */
	public function meta_box_additional_options( $post ) {
		wp_nonce_field( 'mr_additional_options_action', 'mr_additional_options' );

		$featured = has_term( 'featured', 'mr_review_format', $post );
	?>
		<p>
			<label for="mr_featured"><?php _e( 'Featured Review:', 'my-reviews' ); ?></label> <input type="checkbox" id="mr_featured" name="mr_featured" value="1" <?php checked( true, (bool) $featured ); ?> />
		</p>
	<?php
	}

	/**
	 * Filter post thumbnails
	 *
	 * @param string $html
	 * @param int $post_id
	 * @param int $post_thumbnail_id
	 * @param string $size
	 * @param array $attr
	 * @since 0.2
	 * @return string
	 */
	public function filter_post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		if ( 'mr_review' != get_post_type( $post_id ) ) {
			return $html;
		}

        if ( is_admin() ) {
            global $pagenow;
            if ( 'edit.php' != $pagenow ) {
                return $html;
            }
        }

		if ( ! empty( $post_thumbnail_id ) && $post_thumbnail_id != -1 ) {
			return $html;
		}

		global $_wp_additional_image_sizes;

		$reviewer_image = get_post_meta( $post_id, 'mr_reviewer_image', true );

		if ( ! empty( $reviewer_image ) ) {
			if ( ! empty( $_wp_additional_image_sizes[$size] ) && ! empty( $_wp_additional_image_sizes[$size]['width'] ) ) {
				$width = $_wp_additional_image_sizes[$size]['width'];
				if ( apply_filters( 'mr_max_review_image_width', 200, $post_id ) < $width )
					$width = apply_filters( 'mr_max_review_image_width', 200, $post_id );
				return '<a href="' . esc_url( $reviewer_image ) . '?s=' . (int) $width . '&sz=' . (int) $width . '"><img width="' . (int) $width . '" src="' . esc_url( $reviewer_image ) . '?s=' . (int) $width . '&sz=' . (int) $width . '" /></a>';
			}
		}

		return $html;
	}

	/**
	* Output review details meta box
	*
	* @param object $post
	* @since 0.1
	* @uses wp_nonce_field, esc_attr, get_post_meta
	* @return void
	*/
	public function meta_box_review_details( $post ) {
		wp_nonce_field( 'mr_review_details_action', 'mr_review_details' );

		$email = get_post_meta( $post->ID, 'mr_email', true );
        $reviewer_image = get_post_meta( $post->ID, 'mr_reviewer_image', true );
        $reviewer_image_type = get_post_meta( $post->ID, 'mr_reviewer_image_type', true );

        $use_gravatar = ( ! empty( $reviewer_image ) && $reviewer_image_type == 'gravatar' ) ? true : false;
        $use_gplus = ( ! empty( $reviewer_image ) && $reviewer_image_type == 'gplus' ) ? true : false;
	?>
	    <p>
            <label for="mr_email"><?php _e( 'Reviewer Email:', 'my-reviews' ); ?></label> <input class="regular-text" type="text" id="mr_email" name="mr_email" value="<?php echo esc_attr( $email ); ?>" /> (This will be kept private)
	    </p>

		<div class="mr-external-images">
            <input type="hidden" name="mr_reviewer_image" value="<?php echo esc_url( $reviewer_image ); ?>" />
            <input type="hidden" name="mr_reviewer_image_type" value="<?php echo esc_attr( $reviewer_image_type ); ?>" />
            <p><?php _e( 'My Reviews allows you to insert a featured image into each of your reviews. Featured images can be anything you want: business image, reviewer photo, etc. If a reviewers email is associated with a gravatar (globally recoginized avatar) or a Google Plus account, you can use that avatar as the featured image.', 'my-reviews' ); ?></p>
            <div class="row gravatar-row">
                <div class="gravatar"><img src="" width="80"></div>
                <div class="options">
                    <input <?php if ( $use_gravatar ) echo 'checked="checked"'; ?> type="checkbox" value="1" name="mr_use_gravatar" /> <?php _e( 'Use Gravatar as featured image', 'my-review' ); ?>
                </div>
            </div>
            <div class="row gplus-row">
                <div class="gplus"><img src="" width="80"></div>
                <div class="options">
                    <input <?php if ( $use_gplus ) echo 'checked="checked"'; ?> type="checkbox" value="1" name="mr_use_gplus" /> <?php _e( 'Use Google Plus profile picture as featured image', 'my-review' ); ?>
                </div>
            </div>
		</div>
	<?php
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
	 */
	public function init() {
		if ( ! isset( self::$_instance ) ) {

			self::$_instance = new MR_CPTS;
		}

		return self::$_instance;
	}
}

global $mr_cpts;
$mr_cpts = MR_CPTS::init();
