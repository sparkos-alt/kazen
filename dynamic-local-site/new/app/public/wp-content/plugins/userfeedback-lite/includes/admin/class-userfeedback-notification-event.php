<?php

/**
 * Notification Event class.
 *
 * Base Notification class extended by all notifications.
 *
 * Aims to provide a standardized way of creating and managing notifications.
 *
 * Notifications can have two types of buttons:
 * - Links: Simple links (internal or external). Can be added by just appending them to the $buttons array
 * - Actions: These provide a way of executing more complex logic (such as installing other plugins).
 *            These are added using the `add_action` function present in the class.
 *            This can be done from the `prepare` function.
 *
 *            Each action must have a label for the button and a name. The name will be used to trigger the action.
 *            A function with the name `perform_action_{action-name}` must exist in the Notification registering the action
 *
 * @see UserFeedback_Notification_Event
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author  David Paternina
 */
abstract class UserFeedback_Notification_Event {

	/**
	 * Unique notification id
	 *
	 * @var string
	 *
	 * @since 1.0.0
	 */
	public $id;

	/**
	 * Notification title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Notification content
	 *
	 * @var string
	 */
	public $content;

	/**
	 * When the notification will repeat (e.g: 7) here `7` to repeat the notification after each 7 days
	 * Only accept numeric value
	 *
	 * @var number
	 *
	 * @since 1.0.0
	 */
	public $interval = 0;

	/**
	 * When the notification will be active from, default: now
	 *
	 * @var string
	 *
	 * @since 1.0.0
	 */
	public $active_from;

	/**
	 * For how many days the notification will be active
	 *
	 * @var string
	 *
	 * @since 1.0.0
	 */
	public $active_until;

	/**
	 * Which types of license is allowed to view this notification
	 *
	 * @var array
	 *
	 * @since 1.0.0
	 */
	public $license_types;

	/**
	 * Category of this notification: alert or insight
	 *
	 * @var string
	 *
	 * @since 1.0.0
	 */
	public $category = 'alert';

	/**
	 * Priority of this notification: 1-3
	 *
	 * @var int
	 *
	 * @since 1.0.0
	 */
	public $priority = 2;

	/**
	 * Notification icon to display with content
	 *
	 * @var string
	 *
	 * @since 1.0.0
	 */
	public $icon = 'default';

	/**
	 * Whether the notification comes from the plugin's site
	 *
	 * @var bool
	 */
	public $is_remote = false;

	/**
	 * Notification actions
	 *
	 * @var array
	 */
	public $buttons = array();

	/**
	 * @since 1.0.0
	 */
	public function __construct( $id = null ) {
		if ( $id !== null ) {
			$this->id = $id;
		}

		$this->active_from = $this->get_formatted_date( 'now' );

		if ( $this->interval > 0 ) {
			$this->active_until = $this->get_formatted_date( '+' . $this->interval . ' day' );
		} else {
			$this->active_until = $this->get_formatted_date( '+1 day' );
		}

		if ( ! empty( $this->id ) ) {
			// Register notification in event runner
			userfeedback_notification_event_runner()->register_notification( $this );
		}
	}

	/**
	 * Helper function for dates
	 *
	 * @param $readable_time
	 * @return false|string
	 */
	public function get_formatted_date( $readable_time ) {
		return date( 'm/d/Y g:i a', strtotime( $readable_time ) );
	}

	/**
	 * Check notification types against installation type
	 *
	 * @return bool
	 */
	public function license_type_check() {
		$active_type = UserFeedback()->license->get_license_type() ?: 'lite';
		return in_array( $active_type, $this->license_types );
	}

	/**
	 * Check if notification should be displayed based on its dates range
	 *
	 * @return bool
	 */
	public function date_range_check() {
		$now = $this->get_formatted_date( 'now' );

		if ( ! empty( $this->active_from ) && $now < $this->active_from ) {
			return false;
		}

		// Ignore if expired.
		if ( ! empty( $this->active_until ) && $now > $this->active_until ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine if notification should appear or not
	 * Child class can (and probably should) extend this function
	 *
	 * @return boolean
	 */
	public function should_display() {
		return $this->date_range_check() && $this->license_type_check();
	}

	/**
	 * Add notification action
	 *
	 * @param $text
	 * @param $name
	 * @return void
	 */
	protected function add_action( $text, $name ) {
		$this->buttons[] = array(
			'type'   => 'action',
			'class'  => get_called_class(),
			'action' => $name,
			'text'   => $text,
		);
	}

	/**
	 * Prepare notification data
	 *
	 * @return null|UserFeedback_Notification_Event
	 */
	public function prepare() {
		return $this;
	}
}
