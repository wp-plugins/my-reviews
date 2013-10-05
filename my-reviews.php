<?php
/*
Plugin Name: My Reviews
Plugin URI: http://www.wavereview.com
Description: Create and display reviews in WordPress. Syncs with WaveReview
Author: Gem
Version: 0.2
Author URI: http://www.wavereview.com
*/

/**
 * Define plugin constants
 */
define( 'MR_OPTION_NAME', 'my_reviews' );

global $option_defaults;
$option_defaults = array( 'gr_api_key' => '', 'sync_window' => 60, 'sync_review_status' => 'publish', 'service_name' => '' );

/**
 * Include dependencies
 */
require_once( 'includes/mr-functions.php' );
require_once( 'includes/class-mr-cpts.php' );
require_once( 'includes/class-mr-pull.php' );
require_once( 'includes/class-mr-ajax.php' );

/**
 * Include widgets
 */
require_once( 'widgets/class-mr-latest-reviews.php' );
require_once( 'widgets/class-mr-featured-reviews.php' );

class MR_My_Reviews {

	private static $_instance;
	private $admin_page;

	/**
	 * Setup actions and filters. This is a singleton.
	 *
	 * @uses add_action, add_filter
	 * @since 0.1
	 * @return MR_My_Reviews
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'action_enqueue_styles' ) );
		add_filter( 'plugin_action_links_wp-my-reviews/wp-my-reviews.php', array( $this, 'filter_plugin_action_links'), 10, 1 );
		add_filter( 'the_content', array( $this, 'filter_content' ), 10, 2 );
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );
	}

	/**
	 * Surround content with block quotes
	 *
	 * @param string $content
	 * @param int $post_id
	 * @since 0.1
	 * @uses apply_filters, is_single, get_post_type, get_post_meta, get_the_ID, esc_html, is_archive, esc_html
	 * @return string
	 */
	public function filter_content( $content, $post_id = 0 ) {
		if ( ! is_single() && ! is_archive() )
			return $content;

		if ( 'mr_review' != get_post_type( $post_id ) )
			return $content;

		if ( apply_filters( 'mr_blockquote_content', true ) )
			$content = '<blockquote class="mr-review-content">' . $content . '</blockquote>';

		if ( apply_filters( 'mr_add_date_content', true ) && is_single() ) {
			$dates = '<div class="mr-review-date">';
			$option = mr_get_option();
			$reviewed = mr_get_the_timestamp( $post_id );

			if ( ! empty( $option['service_name'] ) )
				$dates .= sprintf( __( 'Reviewed <em>%s</em> on %s', 'my-reviews' ), esc_html( $option['service_name'] ), esc_html( date( 'M j - Y', $reviewed ) ) );
			else
				$dates .= sprintf( __( 'Reviewed on %s', 'my-reviews' ), esc_html( date( 'M j - Y', $reviewed ) ) );
			
			$dates .= '</div>';
			$content = $content . apply_filters( 'mr_date_html', $dates, $reviewed );
		}

		return $content;
	}

	/**
	 * Localize plugin
	 *
	 * @since 0.1
	 * @uses load_plugin_textdomain
	 * @return void
	 */
	public function action_plugins_loaded() {
		load_plugin_textdomain( 'my-reviews', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Add settings and help link to plugins table
	 *
	 * @param array $actions
	 * @since 0.1
	 * @uses admin_url, __
	 * @return array
	 */
	public function filter_plugin_action_links( $actions ) {
		$actions['settings'] = '<a href="' . admin_url( 'options-general.php?page=my-reviews.php' ) . '">' . __( 'Settings and Help', 'my-reviews' ) . '</a>';
		return $actions;
	}

	/**
	 * Enqueue front end styles
	 *
	 * @since 0.1
	 * @uses get_stylesheet_directory_uri, plugins_url, apply_filters, wp_enqueue_style
	 *		 get_template_directory_uri
	 * @return void
	 */
	public function action_enqueue_styles() {
		if ( file_exists( STYLESHEETPATH . '/my-reviews/css/my-reviews.css' ) )
			$css_url = get_stylesheet_directory_uri() . '/my-reviews/css/my-reviews.css';
		elseif ( file_exists( STYLESHEETPATH . '/my-reviews/my-reviews.css' ) )
			$css_url = get_stylesheet_directory_uri(). '/my-reviews/my-reviews.css';
		elseif ( file_exists( TEMPLATEPATH . '/my-reviews/css/my-reviews.css' ) )
			$css_url = get_template_directory_uri() . '/my-reviews/css/my-reviews.css';
		elseif ( file_exists( TEMPLATEPATH . '/my-reviews/my-reviews.css' ) )
			$css_url = get_template_directory_uri(). '/my-reviews/my-reviews.css';
		else
			$css_url = plugins_url( 'includes/template/css/my-reviews.css', __FILE__ );

		$css_url = apply_filters( 'mr_stylesheet_url', $css_url );

		wp_enqueue_style( 'my-reviews', $css_url, '1.0' );
	}

	/**
	 * Add options page
	 *
	 * @uses add_submenu_page, __
	 * @since 0.1
	 * @return void
	 */
	public function action_admin_menu() {
		$this->admin_page = add_submenu_page( 'options-general.php', __( 'My Reviews', 'my-reviews' ), __( 'My Reviews', 'my-reviews' ), 'manage_options', 'my-reviews.php', array( $this, 'screen_options' ) );
		add_action( 'load-' . $this->admin_page, array( $this, 'action_help_screen' ) );
	}

	/**
	 * Output help tab
	 * 
	 * @since 0.1
	 * @uses add_help_tab, _e, __, get_current_screen
	 * @return void
	 */
	public function action_help_screen() {
    	$screen = get_current_screen();

		if ( $screen->id != $this->admin_page )
			return;

		ob_start();
	?>
		<p><?php _e( 'My Reviews lets you manage reviews. You can even sync reviews from your GuestRetain account. Add, edit, delete, and feature reviews <a href="' . admin_url( 'edit.php?post_type=mr_review' ) . '">here</a>. You can show off your reviews to readers in a variety of ways:', 'my-reviews' ); ?></p>
		<ul>
			<li><?php _e( 'Latest Reviews Widget and Featured Reviews Widget - Add these widgets to any sidebar on your site.', 'my-reviews' ); ?></li>
			<li><?php _e( 'Review archive - <a href="' . home_url( 'reviews' ) . '">Here</a> is your review archive. Use this link to show your readers all your reviews in list format.', 'my-reviews' ); ?></li>
			<li><?php _e( 'Single review - You can link readers to individual reviews. Links follow this basic format: <em>http://yoursitename.com/reviews/reviewer-name</em>', 'my-reviews' ); ?></li>
		</ul>
	<?php
		$help_content = ob_get_clean();

		$screen->add_help_tab( array(
			'id' => 'my_reviews',
			'title'	=> __( 'General Usage', 'my-reviews' ),
			'content' => $help_content
		) );
	}

	/**
	 * Register setting and sanitization callback
	 * 
	 * @uses register_setting
	 * @since 0.1
	 * @return void
	 */
	public function action_admin_init() {
		register_setting( MR_OPTION_NAME, MR_OPTION_NAME, array( $this, 'sanitize_options' ) );
	}

	/**
     * Sanitize options
     * 
     * @param array $options
     * @since 0.1
     * @uses sanitize_text_field, flush_rewrite_rules, wp_schedule_single_event
     * @return array
     */
	public function sanitize_options( $options ) {
		global $option_defaults;

		$current_options = mr_get_option();

		if ( ! empty( $_POST['mr_manual_sync'] ) ) {
			wp_schedule_single_event( ( time() - 1 ), 'mr_single_sync_reviews' );
			return $current_options;
		}

		flush_rewrite_rules();

		$new_options = array();

		foreach ( $option_defaults as $option_key => $option_default_value ) {
			$new_options[$option_key] = sanitize_text_field( $options[$option_key] );
		}

		return $new_options;
	}

	/**
	 * Initialize class and return an instance of it
	 *
	 * @since 0.1
	 * @return MR_My_Reviews
	 */
	public function init() {
		if ( ! isset( self::$_instance ) ) {

			self::$_instance = new MR_My_Reviews;
		}

		return self::$_instance;
	}

	/**
	 * Output settings for plugin
	 *
	 * @since 0.1
	 * @uses settings_fields, _e, submit_button, esc_attr, absint, home_url, admin_url, plugins_url, selected
	 * @return void
	 */
	public function screen_options() {
		global $mr_pull;

		$option = mr_get_option();

		$valid_api_key = $mr_pull->valid_api_key( $option['gr_api_key'] );
    ?>
        <div class="wrap">
			<h2><?php _e( 'My Reviews', 'my-reviews' ); ?></h2>
			
			<form action="options.php" method="post">
				<?php settings_fields( MR_OPTION_NAME ); ?>
				<h3><?php _e( 'General Settings', 'my-reviews' ); ?></h3>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><label for="mr_service_name"><?php _e( 'Service Name:', 'my-reviews' ); ?></label></th>
							<td>
								<input type="text" id="mr_service_name" name="<?php echo MR_OPTION_NAME; ?>[service_name]" value="<?php echo esc_attr( $option['service_name'] ); ?>" /> 
								<?php _e( 'i.e. The Marriot or Red Door Spa' ); ?>
							</td>
						</tr>
					</tbody>
				</table>

				<h3><?php _e( 'WaveReview Settings', 'my-reviews' ); ?></h3>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><label for="mr_gr_api_key"><?php _e( 'API Key:', 'my-reviews' ); ?></label></th>
							<td>
								<input type="text" id="mr_gr_api_key" name="<?php echo MR_OPTION_NAME; ?>[gr_api_key]" value="<?php echo esc_attr( $option['gr_api_key'] ); ?>" /> 
								<?php if ( $valid_api_key ) : ?><?php endif; ?>
								<style type="text/css">
								.api-image {
									width: 20px;
									height: 17px;
									margin-bottom: -.1em;
									display: inline-block;
									background: url(<?php echo plugins_url( 'img/api-icons.png', __FILE__ ); ?>) no-repeat top left;
									<?php if ( $valid_api_key ) : ?>
									background-position: 100% 0%;
									<?php endif; ?>
								}
								</style>
								<div class="api-image"></div>
							</td>
						</tr>
					</tbody>
				</table>

				<h3><?php _e( 'Advanced', 'my-reviews' ); ?></h3>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><label for="mr_sync_window"><?php _e( 'Sync Window (minutes):', 'my-reviews' ); ?></label></th>
							<td>
								<input type="text" id="mr_sync_window" size="10" name="<?php echo MR_OPTION_NAME; ?>[sync_window]" value="<?php echo absint( $option['sync_window'] ); ?>" /> 
								<?php _e( 'Commenter info will be synced from the Guest Retain server on this interval' ); ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mr_sync_review_status"><?php _e( 'Sync Review Status:', 'my-reviews' ); ?></label></th>
							<td>
								<select id="mr_sync_review_status" name="<?php echo MR_OPTION_NAME; ?>[sync_review_status]">
									<option value="publish"><?php _e( 'Publish', 'my-reviews' ); ?></option>
									<option <?php selected( 'draft', $option['sync_review_status'] ); ?> value="draft"><?php _e( 'Draft', 'my-reviews' ); ?></option>
								</select>
								<?php _e( 'Syncronized reviews can either be automatically published or set as drafts' ); ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mr_manual_sync"><?php _e( 'Manual Review Sync:', 'my-reviews' ); ?></label></th>
							<td>
								<input class="button" type="submit" name="mr_manual_sync" id="mr_manual_sync" value="<?php _e( 'Do Sync', 'my-reviews' ); ?>" />
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
	<?php
	}

}
global $mr_my_reviews;
$mr_my_reviews = MR_My_Reviews::init();