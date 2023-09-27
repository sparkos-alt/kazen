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
class UserFeedback_Notification_Upgrade_After_14_Days extends UserFeedback_Notification_Event {

	public $id            = 'userfeedback_upgrade_after_14_days';
	public $license_types = array( 'lite' );
	public $interval      = 14;

	public function prepare() {
		$this->title   = __( 'Upgrade to Unlock Additional Features', 'userfeedback' );
		$this->content =
			__( 'Upgrade to UserFeedback Pro to target your UserFeedback surveys by device type, pages, and engagement.', 'userfeedback' );

		$this->buttons[] = array(
			'text'        => __( 'Upgrade to Pro', 'userfeedback' ),
			'url'         => userfeedback_get_upgrade_link(),
			'is_external' => true,
		);

		return parent::prepare();
	}
}

new UserFeedback_Notification_Upgrade_After_14_Days();
