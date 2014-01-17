<?php
/**
 * Register widget
 * 
 * @uses register_widget
 * @return void
 */
function mr_register_latest_reviews_widget() {
	register_widget( 'MR_Latest_Reviews' );
}
add_action( 'widgets_init', 'mr_register_latest_reviews_widget' );

/**
 * Twitter feeds widget
 */
class MR_Latest_Reviews extends WP_Widget {
	
	const CACHE_KEY = 'mr_latest_reviews';
	
	/**
	 * Widget details
	 *
	 * @since 0.1
	 * @uses add_action
	 * @return WP_Widget
	 */
	public function MR_Latest_Reviews() {
		add_action( 'save_post', array( $this, 'action_save_post' ) );
		$this->WP_Widget( false, __( 'My Reviews - Latest Reviews', 'my-reviews' ), array( 'description' => __( 'Displays latest reviews', 'my-reviews' ) ) );
	}
	
	/**
	 * Update widget settings
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @uses delete_transient, esc_attr, absint
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		delete_transient( MR_Latest_Reviews::CACHE_KEY );

		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? esc_attr( $new_instance['title'] ) : '';
		$instance['num_reviews'] = ( ! empty( $new_instance['num_reviews'] ) ) ? absint( $new_instance['num_reviews'] ) : 0;
		$instance['show_review_image'] = ( ! empty( $new_instance['show_review_image'] ) ) ? 1 : 0;

		return $instance;
	}

	/**
	 * Clear cache on post type save
	 *
	 * @param int $post_id
	 * @since 0.1
	 * @uses current_user_can, get_post_type, delete_transient
	 * @return void
	 */
	public function action_save_post( $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'mr_review' != get_post_type( $post_id ) )
			return;

		delete_transient( MR_Latest_Reviews::CACHE_KEY );
		
	}
	
	/**
	 * Render widget
	 * 
	 * @param array $args
	 * @param array $instance
	 * @uses esc_html, wp_parse_args, have_posts, the_post, wp_reset_postdata, the_title, the_permalink,
	 *		 get_the_excerpt, get_transient, set_transient, add_filter, remove_filter, home_url
	 * @return void
	 */
	public function widget( $args, $instance ) {

		$cached_html = get_transient( MR_Latest_Reviews::CACHE_KEY );

		if ( empty( $cached_html ) ) {
			extract( $args );

			$defaults = array(
				'title' => '',
				'num_reviews' => 5,
				'show_review_image' => 0,
			);

			$instance = wp_parse_args( $instance, $defaults );
			$args = array(
				'post_type' => 'mr_review',
				'post_per_page' => $instance['num_reviews'],
			);

			$reviews = new WP_Query( $args );
			if ( ! $reviews->have_posts() )
				return;

			ob_start();
			
			echo $before_widget;
			
			if ( ! empty( $instance['title'] ) ) {
				echo $before_title . esc_html( $instance['title'] ) . $after_title;
			}

			add_filter( 'excerpt_length', 'mr_filter_excerpt_length' );

			echo '<ul class="mr-latest-reviews-list">';
			while ( $reviews->have_posts() ) {
				$reviews->the_post();
				$has_image = false;
				$thumbnail = get_the_post_thumbnail( get_the_ID(), 'mr-widget-thumb' );
				if ( $instance['show_review_image'] && ! empty( $thumbnail ) )
					$has_image = true;
			?>
				<li <?php if ( $has_image ) : ?>class="has-image"<?php endif; ?>>

					<?php if ( $has_image ) : ?>
						<div class="review-image">
							<?php echo $thumbnail; ?>
						</div>
					<?php endif; ?>

					<div class="reviewer">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</div>
					<?php if ( $reviewed = get_post_meta( get_the_ID(), 'mr_reviewed', true ) ) : ?>
						<div class="date"><?php echo date( 'M j - Y', sanitize_text_field( $reviewed ) ); ?></div>
					<?php endif; ?>
					<blockquote>
						<?php echo get_the_excerpt(); ?>
					</blockquote>
				</li>
			<?php
			}
			echo '</ul>';
			echo '<p><a href="' . home_url( 'reviews' ) . '">' . __( '&raquo; View All Reviews', 'my-reviews' ) . '</a></p>';

			remove_filter( 'excerpt_length', 'mr_filter_excerpt_length' );

			wp_reset_postdata();
			
			echo $after_widget;

			$cached_html = ob_get_clean();

			set_transient( MR_Latest_Reviews::CACHE_KEY, $cached_html, HOUR_IN_SECONDS );
		}

		echo $cached_html;
	}
	
	/**
	 * Back-end widget form.
	 *
	 * @param array $instance
	 * @uses get_field_id, get_field_name, esc_attr, absint, wp_parse_args
	 * @return void
	 */
	public function form( $instance ) {

		$defaults = array(
			'title' => '',
			'num_reviews' => 5,
			'show_review_image' => 0,
		);

		$instance = wp_parse_args( $instance, $defaults );
			
	?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'my-reviews' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'num_reviews' ); ?>"><?php _e( 'Number of Reviews to Show:', 'my-reviews' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'num_reviews' ); ?>" name="<?php echo $this->get_field_name( 'num_reviews' ); ?>" type="text" value="<?php echo absint( $instance['num_reviews'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_review_image' ); ?>"><?php _e( 'Show Review Featured Image:', 'my-reviews' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'show_review_image' ); ?>" name="<?php echo $this->get_field_name( 'show_review_image' ); ?>" type="checkbox" value="1" <?php checked( $instance['show_review_image'], 1 ); ?> />
		</p>
	<?php 
	}
}