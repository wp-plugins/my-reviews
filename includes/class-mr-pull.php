<?php

/**
 * Pull from review servers
 */

class MR_Pull {

	private static $_instance;

	const GR_REVIEWS_ENDPOINT = 'https://test-guestretain.herokuapp.com/api/reviews/?format=json';

	/**
	 * Setup actions and filters. This is a singleton.
	 *
	 * @since 0.1
	 * @uses add_action, add_filter
	 * @return MR_GuestRetain_Pull
	 */
	private function __construct() {
		add_action( 'mr_sync_reviews', array( $this, 'sync_reviews' ) );
		add_action( 'mr_single_sync_reviews', array( $this, 'sync_reviews' ) );
		add_action( 'init', array( $this, 'schedule_events' ) );
		add_filter( 'cron_schedules', array( $this, 'filter_cron_schedules' ) );
	}

	/**
	 * Add custom cron schedule
	 *
	 * @param array $schedules
	 * @uses __
	 * @since 0.1
	 * @return array
	 */
	public function filter_cron_schedules( $schedules ) {
		$option = mr_get_option();

		$schedules['my_reviews'] = array(
			'interval' => ( MINUTE_IN_SECONDS * $option['sync_window'] ),
			'display' => __( 'Custom My Reviews Interval', 'my-reviews' )
		);
		return $schedules;
	}

	/**
	 * Setup cron jobs
	 *
	 * @since 0.1
	 * @uses add_action, wp_next_scheduled, wp_schedule_event
	 * @return void
	 */
	public function schedule_events() {
		// if nothing is scheduled, schedule cache update event
		$timestamp = wp_next_scheduled( 'mr_sync_reviews' );
		if ( ! $timestamp ) {
			wp_schedule_event( time(), 'my_reviews', 'mr_sync_reviews' );
		}
	}

	/**
	 * Sync reviews
	 *
	 * @since 0.1
	 * @uses wp_insert_post, have_posts, esc_attr, sanitize_text_field, update_post_meta, update_option
	 *		 wp_parse_args, get_option
	 * @return void
	 */
	public function sync_reviews() {
		$reviews = $this->pull();

		if ( $reviews == false || ! empty( $reviews['detail'] ) )
			return;

		$option = mr_get_option();

		unset( $reviews['detail'] );

		foreach ( $reviews as $review ) {

			if ( ! is_object( $review ) )
				continue;

			// Required review information
			if ( empty( $review->id ) || empty( $review->first_name ) || empty( $review->last_name ) || empty( $review->comment ) )
				continue;

			$postarr = array(
				'post_status' => $option['sync_review_status'],
				'post_type' => 'mr_review',
				'post_content' => $review->comment, 
			);

			$args = array(
				'posts_per_page' => 1,
				'meta_key' => 'mr_post_id',
				'meta_value' => (int) $review->id,
				'post_type' => 'mr_review',
			);

			$reviews_query = new WP_Query( $args );
			
			if ( $reviews_query->have_posts() ) {
				$postarr['ID'] = $reviews_query->posts[0]->ID;
				$postarr['post_status'] = $reviews_query->posts[0]->post_status;
			}

			$postarr['post_title'] = esc_attr( $review->first_name . ' ' . $review->last_name );
			
			$post_id = wp_insert_post( $postarr );

			if ( $post_id ) {
				update_post_meta( $post_id, 'mr_post_id', (int) $review->id );

				if ( ! empty( $review->checkin_date ) )
					update_post_meta( $post_id, 'mr_checkin_date', sanitize_text_field( strtotime( $review->checkin_date ) ) );
				
				if ( ! empty( $review->checkout_date ) )
					update_post_meta( $post_id, 'mr_checkout_date', sanitize_text_field( strtotime( $review->checkout_date ) ) );

				if ( ! empty( $review->created ) )
					update_post_meta( $post_id, 'mr_reviewed', sanitize_text_field( strtotime( $review->created ) ) );
			}
		}
	}

	/**
	 * Initialize class and return an instance of it
	 *
	 * @since 0.1
	 * @return MR_GuestRetain_Pull
	 */
	public function init() {
		if ( ! isset( self::$_instance ) ) {

			self::$_instance = new MR_Pull;
		}

		return self::$_instance;
	}

	/**
	 * Pull reviews from server
	 *
	 * @uses wp_remote_post, esc_attr
	 * @since 0.1
	 * @return array|bool
	 */
	public function pull() {
		$option = mr_get_option();

		if ( empty( $option['gr_api_key'] ) )
			return false;

		$response = wp_remote_get( MR_Pull::GR_REVIEWS_ENDPOINT, array(
			'method' => 'GET',
			'headers' => array( 'Authorization' => 'Token ' . esc_attr( $option['gr_api_key'] ) ),
			'timeout' => 30,
	    ) );
	    
	    return mr_parse_response( $response, array( 'detail' => '' ) );
	}

	/**
	 * Check if api key is valid
	 *
	 * @param string $api_key
	 * @since 0.1
	 * @uses wp_remote_get, esc_attr, is_wp_error
	 * @return bool
	 */
	public function valid_api_key( $api_key ) {
		if ( empty( $api_key ) )
			return false;

		$response = wp_remote_get( MR_Pull::GR_REVIEWS_ENDPOINT, array(
			'method' => 'GET',
			'headers' => array( 'Authorization' => 'Token ' . esc_attr( $api_key ) ),
			'timeout' => 10,
	    ) );

	    if ( is_wp_error( $response ) )
	    	return false;
	    
	    $response_array = mr_parse_response( $response, array( 'detail' => '' ) );

	    return empty( $response_array['detail'] );
	}
}

global $mr_pull;
$mr_pull = MR_Pull::init();
