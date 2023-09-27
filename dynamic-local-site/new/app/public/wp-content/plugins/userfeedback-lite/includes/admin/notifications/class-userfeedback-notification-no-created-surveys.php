<?php

/**
 * No Created Surveys Notification class.
 *
 * Notification shown when the user hasn't created any Surveys
 * after a few days of the plugin being installed
 *
 * @see UserFeedback_Notification_Event
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage Notifications
 * @author  David Paternina
 */
class UserFeedback_Notification_No_Created_Surveys extends UserFeedback_Notification_Event {

	public $id            = 'userfeedback_no_created_surveys';
	public $license_types = array( 'lite', 'plus', 'pro' );
	public $interval      = 5;

	public function prepare() {
		$this->title   = __( 'See What Your Visitors REALLY Think!', 'userfeedback' );
		$this->content = __( 'See what your website visitors are thinking by creating a new UserFeedback survey. ', 'userfeedback' );

		$this->buttons[] = array(
			'text' => __( 'Create Survey', 'userfeedback' ),
			'url'  => userfeedback_get_screen_url( 'userfeedback_surveys', 'new' ),
		);

		return parent::prepare();
	}

	public function should_display() {
		$surveys_count = UserFeedback_Survey::count();
		return parent::should_display() && $surveys_count === 0;
	}
}

new UserFeedback_Notification_No_Created_Surveys();
