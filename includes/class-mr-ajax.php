<?php

/**
 * Register AJAX actions
 *
 * @since 0.2
 */
class MR_AJAX {
	private static $_instance;

	/**
	 * Setup actions and filters. This is a singleton.
	 *
	 * @since 0.2
	 * @uses add_action, add_filter
	 * @return MR_AJAX
	 */
	private function __construct() {
		add_action( 'wp_ajax_get_images', array( $this, 'action_get_images' ) );
	}

	/**
	 * Check if email has gravatar
	 *
	 * @since 1.1
	 * @return void
	 */
	public function action_get_images() {
		$output = array();
		$output['success'] = false;

		if ( check_ajax_referer( 'get_images_nonce', 'nonce', false ) && ! empty( $_POST['email'] ) ) {

            if ( mr_has_gravatar( $_POST['email'] ) )
                $output['gravatar'] = 'https://www.gravatar.com/avatar/' . md5( $_POST['email'] );
            else
                $output['gravatar'] = false;

            $output['gplus'] = mr_has_gplus( $_POST['email'] );

			$output['success'] = true;
		}

		echo json_encode( $output );
		
		die();
	}

	/**
	 * Initialize class and return an instance of it
	 *
	 * @since 0.2
	 * @return MR_AJAX
	 */
	public static function init() {
		if ( ! isset( self::$_instance ) ) {

			self::$_instance = new MR_AJAX;
		}

		return self::$_instance;
	}
}

MR_AJAX::init();
