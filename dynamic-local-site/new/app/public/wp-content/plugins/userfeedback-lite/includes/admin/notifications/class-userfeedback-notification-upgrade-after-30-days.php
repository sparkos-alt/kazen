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
class UserFeedback_Notification_Upgrade_After_30_Days extends UserFeedback_Notification_Event {

	public $id            = 'userfeedback_upgrade_after_30_days';
	public $license_types = array( 'lite' );
	public $interval      = 30;

	public function prepare() {
		$this->title   = __( 'Upgrade to Unlock Additional Features', 'userfeedback' );
		$this->content =
			__( 'Upgrade to UserFeedback Pro and customize who gets notified about a new notification.', 'userfeedback' );

		$this->buttons[] = array(
			'text'        => __( 'Upgrade to Pro', 'userfeedback' ),
			'url'         => userfeedback_get_upgrade_link(),
			'is_external' => true,
		);

		return parent::prepare();
	}
}

new UserFeedback_Notification_Upgrade_After_30_Days();
