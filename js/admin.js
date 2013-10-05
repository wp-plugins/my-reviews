function xinspect( o, i ) {
    if(typeof i=='undefined')i='';
    if(i.length>50)return '[MAX ITERATIONS]';
    var r=[];
    for(var p in o){
        var t=typeof o[p];
        r.push(i+'"'+p+'" ('+t+') => '+(t=='object' ? 'object:'+xinspect(o[p],i+'  ') : o[p]+''));
    }
    return r.join(i+'\n');
}

/**
 * Backend plugin JS
 */
( function( $ ) {

	var $use_gravatar = $( 'input[name=mr_use_gravatar]' );
	var $mr_email = $( '#mr_email' );

	/**
	 * Check if email has a gravatar
	 */
	function mr_has_gravatar( email ) {
		var params = {
			action: 'has_gravatar',
			email: email,
			nonce: mr_data.has_gravatar_nonce,
		};

		var has_gravatar = false;

		// Grab commenter information
		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: params,
			async: false,
		 	success: function( data, textStatus, jqXHR ) {
		 		if ( data[email] )
		 			has_gravatar = data[email];
	 		},
	 		dataType: "json"
		} );

		return has_gravatar;
	}

	function mr_setup_gravatar( gravatar ) {
		$has_gravatar = $( '.mr-has-gravatar' );

		if ( ! gravatar ) {
			$has_gravatar.fadeOut();
			$( '.mr-featured-gravatar' ).remove();
			$use_gravatar
				.val( '0' )
				.prop( 'checked', false );
			return;
		}

		$has_gravatar
			.find( '.gravatar' )
			.css( { 'background' : 'url(' + gravatar + ') no-repeat top left' } )
			.show();

		$has_gravatar
			.find( 'input[name=mr_use_gravatar]' )
			.val( gravatar );

		$has_gravatar.fadeIn();
	}

	function mr_feature_gravatar( gravatar ) {
		if ( ! gravatar )
			return;

		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				action: "set-post-thumbnail",
				post_id: $('#post_ID').val(),
				thumbnail_id: -1,
				_ajax_nonce: mr_data.set_thumbnail_nonce,
				cookie: encodeURIComponent(document.cookie)
			},
			success: function(str){
				if ( str != '0' ) {
					WPSetThumbnailHTML(str);
				}
			},
			async: false
		} );

		// First clear out current image
		//$( '#set-post-thumbnail' )
		//	.remove();
		
		new_image = $( '<p class="hide-if-no-js mr-featured-gravatar"><img src="' + gravatar + '" /></p>' );

		$( '#postimagediv .inside' ).prepend( new_image );
	}

	/**
	 * Register gravatar checks
	 */

	var has_gravatar = mr_has_gravatar( $mr_email.val() );
	if ( has_gravatar ) {
		mr_setup_gravatar( has_gravatar );
	}

	$mr_email.on( 'change', function() {
		var has_gravatar = mr_has_gravatar( $( this ).val() );

		mr_setup_gravatar( has_gravatar );

	} );

	/**
	 * Register use gravatar checkbox
	 */

	if ( mr_data.thumbnail_id == 0 ) {
		if ( $use_gravatar.is( ':checked' ) ) {
			mr_feature_gravatar( $use_gravatar.val() );
		} else {
			$( '.mr-featured-gravatar' ).remove();
		}
	} else {
		$use_gravatar.prop( 'checked', false );
	}

	$use_gravatar.on( 'change', function() {

		if ( $( this ).is( ':checked' ) )
			mr_feature_gravatar( $( this ).val() );
		else
			$( '.mr-featured-gravatar' ).remove();

	} );

} )( jQuery );