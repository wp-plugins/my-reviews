<?php

/**
 * A wrapper for get_option that returns the plugins option
 *
 * @since 0.1
 * @uses get_option, wp_parse_args
 * @return array
 */
function mr_get_option() {
	global $option_defaults;

	$option = get_option( MR_OPTION_NAME, $option_defaults );
	$option = wp_parse_args( $option, $option_defaults );
	return $option;
}

/**
 * Parse MR API response and merge it with optional default arg array
 *
 * @param array $response
 * @param array $defaults
 * @uses wp_parse_args, is_wp_error
 * @return array
 */
function mr_parse_response( $response, $defaults = array() ) {
	if ( ! is_wp_error( $response ) && is_array( $response ) && ! empty( $response['body'] ) ) {
		return wp_parse_args( json_decode( $response['body'] ), $defaults );
	}

	return $defaults;
}


/**
 * Shorten excerpt length
 *
 * @param int $length
 * @return int
 */
function mr_filter_excerpt_length( $length ) {
	return 20;
}

