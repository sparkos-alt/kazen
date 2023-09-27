<?php

/**
 *
 * @see UserFeedback_Notification_Event
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage Notifications
 * @author  David Paternina
 */
class UserFeedback_Notification_Upgrade_After_10_Entries extends UserFeedback_Notification_Event {

	public $id            = 'userfeedback_upgrade_after_time';
	public $license_types = array( 'lite' );

	public function prepare() {
		$this->title   = __( 'Upgrade to Unlock Additional Features', 'userfeedback' );
		$this->content =
			__( 'Export all of your UserFeedback responses and entire history with with UserFeedback Pro.', 'userfeedback' );

		$this->buttons[] = array(
			'text'        => __( 'Upgrade to Pro', 'userfeedback' ),
			'url'         => userfeedback_get_upgrade_link(),
			'is_external' => true,
		);

		return parent::prepare();
	}

	public function should_display() {
		$query = UserFeedback_Response::query();
		$query->select( array( 'survey_id', 'count', 'survey' ) )
			->with( array( 'survey' ) )
			->group_by( 'survey_id' )
			->sort( 'count', 'desc' );

		$largest_survey_count = $query->single();
		$should_display       = ! empty( $largest_survey_count ) && $largest_survey_count->count > 10;

		return $should_display && parent::should_display();
	}
}

new UserFeedback_Notification_Upgrade_After_10_Entries();
