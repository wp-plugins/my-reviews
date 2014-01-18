/**
 * Backend plugin JS
 */
( function( $, undefined ) {

    var MyReviews = ( function() {

        var instance;

        var $use_gravatar = $( 'input[name=mr_use_gravatar]' );
        var $use_gplus = $( 'input[name=mr_use_gplus]' );
        var $mr_email = $( '#mr_email' );
        var $external_images = $( '.mr-external-images' );
        var $reviewer_image = $external_images.find( 'input[name=mr_reviewer_image]' );
        var $reviewer_image_type = $external_images.find( 'input[name=mr_reviewer_image_type]' );
        var $gravatar_row = $external_images.find( '.gravatar-row' );
        var $gplus_row = $external_images.find( '.gplus-row' );
        var $new_image = null;
        var $checkboxes = null;
        var imagesXHR = false;
        var images = {
            gravatar : false,
            gplus : false
        };
        var imagesTimeout = false;

        /**
         * Returns true if an email is valid
         */
        function valid_email( email ) {
            var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test( email );
        }

        /**
         * Check if email has images
         */
        function get_images( email ) {
            if ( imagesXHR != false ) {
                imagesXHR.abort();
            }

            var params = {
                action: 'get_images',
                email: email,
                nonce: mr_data.get_images_nonce
            };

            // Grab commenter information
            imagesXHR = $.ajax( {
                type: 'POST',
                url: ajaxurl,
                data: params,
                async: false,
                success: function( data, textStatus, jqXHR ) {
                    if ( data.gravatar ) {
                        images.gravatar = data.gravatar;
                    }

                    if ( data.gplus ) {
                        images.gplus = data.gplus;
                    }

                    setup_images();
                },
                dataType: "json"
            } );

            return imagesXHR;
        }

        function setup_images() {

            if ( ! images.gravatar && ! images.gplus ) {
                $external_images.fadeOut();
                $( '.mr-featured-image' ).remove();
                $use_gravatar.prop( 'checked', false );

                $use_gplus.prop( 'checked', false );
                return;
            }

            if ( images.gravatar ) {
                $external_images
                    .show()
                    .find( '.gravatar' )
                    .css( { 'background' : 'url(' + images.gravatar + ') no-repeat top left' } )
                    .show();

                $use_gravatar.val( images.gravatar );

                $gravatar_row.fadeIn();
            }

            if ( images.gplus ) {
                $external_images
                    .show()
                    .find( '.gplus' )
                    .css( { 'background' : 'url(' + images.gplus + ') no-repeat top left' } )
                    .show();

                $use_gplus.val( images.gplus );

                $gplus_row.fadeIn();
            }
        }

        function feature_image( image ) {
            if ( ! image ) {
                return;
            }

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

            if ( $new_image === null ) {
                $new_image = $( '<p class="hide-if-no-js mr-featured-image"><img src="' + image + '" /></p>' );

                $( '#postimagediv .inside' ).prepend( $new_image );
            } else {
                $new_image
                    .find( 'img' )
                    .attr( 'src', image );
            }
        }

        function init() {

            /**
             *  Extend built in featured image set
             */
            var set_featured_image = wp.media.featuredImage.set;
            wp.media.featuredImage.set = function( id ) {
                set_featured_image( id );

                $use_gravatar.prop( 'checked', false );
                $use_gplus.prop( 'checked', false );
            };

            get_images( $mr_email.val() );

            $mr_email.on( 'keyup', function() {
                clearTimeout( imagesTimeout );
                if ( valid_email( $( this).val() ) ) {
                    imagesTimeout = setTimeout( function() {
                        $reviewer_image.val( '' );
                        $reviewer_image_type.val( '' );
                        get_images( $mr_email.val() );
                    }, 1000 );
                } else {
                    $use_gravatar.prop( 'checked', false );
                    $use_gplus.prop( 'checked', false );
                    $reviewer_image.val( '' );
                    $reviewer_image_type.val( '' );
                    $( '.mr-featured-image' ).remove();
                    $external_images.hide();
                }
            } );

            if ( mr_data.thumbnail_id == '0' ) {
                if ( $use_gravatar.is( ':checked' ) ) {
                    feature_image( $use_gravatar.val() );
                    $reviewer_image_type.val( 'gravatar' );
                    $reviewer_image.val( $use_gravatar.val() );
                } else if ( $use_gplus.is( ':checked' ) ) {
                    feature_image( $use_gplus.val() );
                    $reviewer_image_type.val( 'gravatar' );
                    $reviewer_image.val( $use_gravatar.val() );
                } else {
                    $( '.mr-featured-image' ).remove();
                }
            } else {
                $use_gravatar.prop( 'checked', false );
                $use_gplus.prop( 'checked', false );
            }

            $checkboxes = $external_images.find( 'input[type=checkbox]' );

            $external_images.on( 'change', 'input[type=checkbox]', function() {

                var $target = $( this );

                $checkboxes.each( function() {
                    if ( $target[0] != $( this )[0] ) {
                        $( this).prop( 'checked', false );
                    }
                } );

                if ( $target.is( ':checked' ) ) {
                    feature_image( $target.val() );
                    if ( $target.attr( 'name' ) == 'mr_use_gravatar' ) {
                        $reviewer_image_type.val( 'gravatar' );
                    } else if ( $target.attr( 'name' ) == 'mr_use_gplus' ) {
                        $reviewer_image_type.val( 'gplus' );
                    }

                    $reviewer_image.val( $target.val() );
                } else {
                    $reviewer_image.val( '' );
                    $reviewer_image_type.val( '' );
                    $( '.mr-featured-image' ).remove();
                }
            } );
        }

        function get_instance() {
            if ( ! instance ) {
                instance = new init();
            }

            return instance;
        }

        return {
            get_instance : get_instance
        };
    } )();

    MyReviews.get_instance();

} )( jQuery );