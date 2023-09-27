<?php

/**
 * Install MI Notification class.
 *
 * Notification shown when MonsterInsights isn't installed
 *
 * @see UserFeedback_Notification_Event
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage Notifications
 * @author  David Paternina
 */
class UserFeedback_Notification_MonsterInsights extends UserFeedback_Notification_Event {

	public $id            = 'userfeedback_monsterinsights_cross_sell';
	public $license_types = array( 'lite', 'plus', 'pro' );

	public function prepare() {
		$this->title   = __( 'See The Stats That Matter', 'userfeedback' );
		$this->content = __( 'Install MonsterInsights to see the website stats that matter and learn more about who is providing user feedback on your website.', 'userfeedback' );

		$this->add_action( __( 'Install MonsterInsights' ), 'install_monsterinsights' );

		return parent::prepare();
	}

	public function should_display() {
		return parent::should_display() && ! UserFeedback_MonsterInsights_Integration::is_active();
	}

	/**
	 * Action triggered when user clicks on the "Install MonsterInsights" button.
	 *
	 * If MI plugin is installed but not active, we try to activate it.
	 * Otherwise, redirect to our installation URL with the slug for the Lite version
	 *
	 * @return array|bool[]|void
	 */
	public function perform_action_install_monsterinsights() {
		if ( ! UserFeedback_MonsterInsights_Integration::is_installed() ) {
			// Dismiss this notification
			UserFeedback()->notifications->dismiss_notification_by_id( $this->id );

			// Redirect to install screen
			return array(
				'redirect_to' => userfeedback_get_plugin_install_url( 'google-analytics-for-wordpress' ),
			);

		} elseif ( ! UserFeedback_MonsterInsights_Integration::is_active() ) {
			$slug = UserFeedback_MonsterInsights_Integration::is_pro()
				? 'google-analytics-premium/googleanalytics-premium.php'
				: 'google-analytics-for-wordpress/googleanalytics.php';

			$result = activate_plugin( $slug, false, false, true );

			// Dismiss this notification
			UserFeedback()->notifications->dismiss_notification_by_id( $this->id );

			return array(
				'success' => $result === null,
				'reload'  => true,
			);
		}
	}
}

new UserFeedback_Notification_MonsterInsights();
