<?php

/**
 * No Live Surveys Notification class.
 *
 * Notification shown when there are surveys created but none of them is published
 *
 * @see UserFeedback_Notification_Event
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage Notifications
 * @author  David Paternina
 */
class UserFeedback_Notification_No_Live_Surveys extends UserFeedback_Notification_Event {

	public $id            = 'userfeedback_no_live_surveys';
	public $license_types = array( 'lite', 'plus', 'pro' );
	public $interval      = 5;

	public function prepare() {
		$this->title   = __( 'Collect User Feedback Now', 'userfeedback' );
		$this->content = __( 'See what your website visitors are thinking by launching a UserFeedback survey.', 'userfeedback' );

		$this->buttons[] = array(
			'text' => __( 'Launch Survey', 'userfeedback' ),
			'url'  => userfeedback_get_screen_url( 'userfeedback_surveys' ),
		);

		return parent::prepare();
	}

	public function should_display() {
		$live_surveys = UserFeedback_Survey::where(
			array(
				'status' => 'publish',
			)
		)->get_count();

		return parent::should_display() && $live_surveys === 0;
	}
}

new UserFeedback_Notification_No_Live_Surveys();
