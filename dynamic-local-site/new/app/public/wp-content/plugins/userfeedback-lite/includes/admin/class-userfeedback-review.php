<?php

/**
 * Ask for some love.
 *
 * @package    UserFeedback
 * @author     UserFeedback Team
 * @since      1.0.1
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2018, UserFeedback LLC
 */
class UserFeedback_Review {
	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.1
	 */
	public function __construct() {
		// Admin notice requesting review.
		add_action( 'admin_notices', array( $this, 'review_request' ) );
		add_action( 'wp_ajax_userfeedback_review_dismiss', array( $this, 'review_dismiss' ) );
	}

	/**
	 * Add admin notices as needed for reviews.
	 *
	 * @since 1.0.1
	 */
	public function review_request() {

		// Only consider showing the review request to admin users.
		if ( ! is_super_admin() ) {
			return;
		}

		// If the user has opted out of product annoucement notifications, don't
		// display the review request.
		if ( userfeedback_get_option( 'hide_am_notices', false ) || userfeedback_get_option( 'network_hide_am_notices', false ) ) {
			return;
		}

		// Verify that UF installed 14 days ago
		$activated = get_option( 'userfeedback_over_time', array() );

		if ( ! empty( $activated['installed_date'] ) ) {
			$days      = 14;
			$show_show = (int) $activated['installed_date'] + ( DAY_IN_SECONDS * $days );
			if ( time() > $show_show ) {
				$this->review();
			}
		} else {
			$data = array(
				'installed_version' => USERFEEDBACK_VERSION,
				'installed_date'    => time(),
				'installed_pro'     => userfeedback_is_pro_version(),
			);

			update_option( 'userfeedback_over_time', $data, false );
		}
	}

	/**
	 * Maybe show review request.
	 *
	 * @since 1.0.1
	 */
	private function review() {
		// Verify that we can do a check for reviews.
		$review = get_option( 'userfeedback_review' );

		if ( $review ) {
			// Check user already reviewed.
			if ( isset( $review['dismissed'] ) && $review['dismissed'] ) {
				return;
			}

			// Check user choose to review later.
			if ( isset( $review['time'] ) && ( $review['time'] > time() ) ) {
				return;
			}
		}
		?>
		<div class="notice notice-info is-dismissible userfeedback-review-notice">
			<div class="userfeedback-review-step">
				<p>
					<?php
					echo wp_kses(
						sprintf(
							__( 'Hey, I noticed you have been using %1$s for some time - that’s awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'userfeedback' ),
							'<strong>' . USERFEEDBACK_PLUGIN_NAME . '</strong>'
						),
						array( 'strong' => array() )
					);
					?>
				</p>
				<p>
					<strong><?php echo wp_kses( __( '~ Syed Balkhi<br>Founder of UserFeedback', 'userfeedback' ), array( 'br' => array() ) ); ?></strong>
				</p>
				<p>
					<a href="https://wordpress.org/support/plugin/userfeedback-lite/reviews/?filter=5#new-post"
					   class="userfeedback-dismiss-review-notice userfeedback-review-out" target="_blank"
					   rel="noopener noreferrer"><?php esc_html_e( 'Ok, you deserve it', 'userfeedback' ); ?></a><br>
					<a href="#" class="userfeedback-dismiss-review-notice userfeedback-review-later"
					   rel="noopener noreferrer"><?php esc_html_e( 'Nope, maybe later', 'userfeedback' ); ?></a><br>
					<a href="#" class="userfeedback-dismiss-review-notice"
					   rel="noopener noreferrer"><?php esc_html_e( 'I already did', 'userfeedback' ); ?></a>
				</p>
			</div>
		</div>
		<script type="text/javascript">
            jQuery(document).ready(function ($) {
                $(document).on('click', '.userfeedback-dismiss-review-notice', function (event) {
                    if (!$(this).hasClass('userfeedback-review-out')) {
                        event.preventDefault();
                    }
                    $.post(ajaxurl, {
                        action: 'userfeedback_review_dismiss',
                        review_later: $(this).hasClass('userfeedback-review-later')
                    });
                    $('.userfeedback-review-notice').remove();
                });
            });
		</script>
		<?php
	}

	/**
	 * Dismiss the review admin notice
	 *
	 * @since 1.0.1
	 */
	public function review_dismiss() {
		$review = get_option( 'userfeedback_review', array() );

		if ( isset( $_POST['review_later'] ) && "true" === $_POST['review_later'] ) {
			$review['time']      = time() + ( DAY_IN_SECONDS * 7 ); // Add 7 days.
			$review['dismissed'] = false;
		} else {
			$review['time']      = time();
			$review['dismissed'] = true;
		}

		update_option( 'userfeedback_review', $review );

		if ( is_super_admin() && is_multisite() ) {
			$site_list = get_sites();
			foreach ( (array) $site_list as $site ) {
				switch_to_blog( $site->blog_id );

				update_option( 'userfeedback_review', $review );

				restore_current_blog();
			}
		}

		wp_die( 'success' );
	}
}

new UserFeedback_Review();
