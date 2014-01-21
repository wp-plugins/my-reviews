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
 * Truncate a string on a word
 *
 * @param string $str
 * @param int $maxlen
 * @since 0.1
 * @return string
 */
function mr_truncate_str( $str, $maxlen ) {
    if ( strlen( $str ) <= $maxlen ) return $str;

    $newstr = substr( $str, 0, $maxlen );
    if ( substr( $newstr, -1, 1 ) != ' ' )
        $newstr = substr( $newstr, 0, strrpos( $newstr, " " ) );

    return $newstr;
}

/**
 * Check if email has a gravatar
 *
 * @param string $email
 * @since 0.2
 * @return boolean
 */
function mr_has_gravatar( $email ) {
    $url = 'https://www.gravatar.com/avatar/' . md5( strtolower( trim ( $email ) ) );
    $headers = @get_headers( $url );
    return preg_match( '|200|', $headers[0] ) ? true : false;
}

/**
 * Check if email has a gplus profile image
 *
 * @param string $email
 * @since 1.1
 * @return boolean|string
 */
function mr_has_gplus( $email ) {
    $user_id = preg_replace( '/@(gmail|googlemail)\.com$/i', '', trim( $email ) );

    $request = wp_remote_request( 'https://plus.google.com/s2/photos/profile/' . $user_id );

    if ( ! empty( $request['response'] ) ) {
        if ( ! empty( $request['response']['code'] ) ) {
            if ( (int) $request['response']['code'] == 200 ) {
                return 'https://plus.google.com/s2/photos/profile/' . $user_id;
            }
        }
    }

    return false;
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

/**
 * Return timestamp for a post, uses mr_reviewed if it exists
 *
 * @param int $post_id
 * @uses get_post, get_the_time, get_post_meta
 * @return int
 */
function mr_get_the_timestamp( $post_id = 0 ) {
	if ( ! $post_id ) {
		global $post;
	} else {
		$post = get_post( $post_id );
	}

	if ( $post ) {
		if ( $reviewed_time = get_post_meta( $post->ID, 'mr_reviewed', true ) ) {
			return (int) $reviewed_time;
		}

		return get_the_time( 'U', $post_id );
	}

	return 0;
}
