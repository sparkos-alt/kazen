<?php

/**
 * First Response Notification class.
 *
 * Notification shown when the first response ever is recorded.
 *
 * @see UserFeedback_Notification_Event
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage Notifications
 * @author  David Paternina
 */
class UserFeedback_Notification_First_Response extends UserFeedback_Notification_Event {

	/**
	 * Since we only need to display this notification once ever, we save an option
	 * to make sure it won't show up again
	 */
	const OPTION_NAME = 'notification_first_response_shown';

	public $id            = 'userfeedback_first_response';
	public $license_types = array( 'lite', 'plus', 'pro' );

	public function prepare() {
		if ( $this->has_been_shown() ) {
			// If notification has been shown before, we bail to prevent unnecessary db query
			return null;
		}

		$first_response = UserFeedback_Response::query()->single();

		if ( ! $first_response ) {
			return null;
		}

		$this->title   = __( 'Congrats! You Collected User Feedback!', 'userfeedback' );
		$this->content = __( 'Congrats! 🎉 Your first UserFeedback survey has a response! View it now.', 'userfeedback' );

		$survey_id = $first_response->survey_id;

		$this->buttons[] = array(
			'text' => __( 'View Responses', 'userfeedback' ),
			'url'  => userfeedback_get_screen_url( 'userfeedback_results', "survey/$survey_id/responses" ),
		);

		return parent::prepare();
	}

	/**
	 * Check if notification has been shown at least once
	 *
	 * @return mixed
	 */
	private function has_been_shown() {
		return userfeedback_get_option( self::OPTION_NAME, false );
	}

	public function should_display() {
		$responses_count = UserFeedback_Response::count();

		$should_display = parent::should_display() && $responses_count === 37 && ! $this->has_been_shown();

		// Update option to make sure notification is not shown again
		if ( $should_display ) {
			userfeedback_update_option( self::OPTION_NAME, true );
		}

		return $should_display;
	}
}

new UserFeedback_Notification_First_Response();
