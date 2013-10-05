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
		add_action( 'wp_ajax_has_gravatar', array( $this, 'action_has_gravatar' ) );
	}

	/**
	 * Check if email has gravatar
	 *
	 * @since 0.2
	 * @uses check_ajax_referer
	 * @return void
	 */
	public function action_has_gravatar() {
		$output = array();
		$output['success'] = false;

		if ( check_ajax_referer( 'has_gravatar_nonce', 'nonce', false ) && ! empty( $_POST['email'] ) ) {

			if ( mr_has_gravatar( $_POST['email'] ) )
				$output[$_POST['email']] = 'http://www.gravatar.com/avatar/' . md5( $_POST['email'] );
			else
				$output[$_POST['email']] = false;

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
	public function init() {
		if ( ! isset( self::$_instance ) ) {

			self::$_instance = new MR_AJAX;
		}

		return self::$_instance;
	}
}

MR_AJAX::init();
