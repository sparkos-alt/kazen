<?php

/**
 * External Notification class.
 *
 * Used to map external feed notifications into a standardized notification
 * object/structure.
 *
 * This way all notifications (local and remote) have the same behavior
 *
 * @see UserFeedback_Notification_Event
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage Notifications
 * @author  David Paternina
 */
class UserFeedback_Notification_External extends UserFeedback_Notification_Event {

	/**
	 * Create notification from external data
	 *
	 * @param $data
	 * @return UserFeedback_Notification_External
	 */
	public static function make( $data ) {
		$notification                = new UserFeedback_Notification_External();
		$notification->id            = $data['id'];
		$notification->active_from   = $notification->get_formatted_date( $data['start'] );
		$notification->active_until  = $notification->get_formatted_date( $data['end'] );
		$notification->is_remote     = true;
		$notification->license_types = $data['type'];
		$notification->title         = $data['title'];
		$notification->content       = $data['content'];
		$notification->buttons       = array_map(
			function ( $button ) {
				return array_merge(
					$button,
					array(
						'is_external' => true,
					)
				);
			},
			array_values( $data['btns'] )
		);

		return $notification;
	}

	public function should_display() {
		$now = $this->get_formatted_date( 'now' );
		return ( $now >= $this->active_from && $now <= $this->active_until ) && $this->license_type_check();
	}
}
