<?php

/**
 * Notifications class.
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author  David Paternina
 */
class UserFeedback_Notifications {


	/**
	 * Source of notifications content.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const REMOTE_SOURCE_URL = 'https://plugin.userfeedback.com/wp-content/notifications.json';

	/**
	 * Option value.
	 *
	 * @since 1.0.0
	 * @var bool|array
	 */
	public $option = false;

	/**
	 * The name of the option used to store the data.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $option_name = 'userfeedback_notifications';

	/**
	 *
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'userfeedback_admin_notifications_update', array( $this, 'update_notifications' ) );
	}

	/**
	 * Register Ajax routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'userfeedback/v1',
			'/notifications',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_notifications' ),
				'permission_callback' => array( $this, 'notifications_permissions_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/notifications/(?P<id>\w+)/dismiss',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'dismiss_notification' ),
				'permission_callback' => array( $this, 'notifications_permissions_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/notifications/action',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'perform_notification_action' ),
				'permission_callback' => array( $this, 'notifications_permissions_check' ),
			)
		);
	}

	/**
	 * @return bool
	 */
	public function notifications_permissions_check() {
		return current_user_can( 'userfeedback_create_edit_surveys' )
				|| current_user_can( 'userfeedback_save_settings' );
	}

	/**
	 * Get notifications
	 *
	 * @return WP_REST_Response
	 */
	public function get_notifications() {

		do_action( 'userfeedback_run_notifications' );

		return new WP_REST_Response(
			array(
				'notifications' => $this->get_active_notifications(),
				'dismissed'     => $this->get_dismissed_notifications(),
			)
		);
	}

	/**
	 * Dismiss a notification
	 *
	 * @return WP_REST_Response
	 */
	public function dismiss_notification( WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );

		if ( empty( $id ) ) {
			return new WP_REST_Response( null, 422 );
		}

		$option = $this->get_option();

		// Dismiss all notifications and add them to dismiss array.
		if ( 'all' === $id ) {

			$option['dismissed'] = array_merge(
				$option['events'],
				$option['feed'],
				$option['dismissed']
			);

			$option['events'] = array();
			$option['feed']   = array();

			update_option( $this->option_name, $option, false );

			return new WP_REST_Response( null, 204 );
		}

		$this->dismiss_notification_by_id( $id );
		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Dismiss notification by id
	 *
	 * @param $id
	 * @return void
	 */
	public function dismiss_notification_by_id( $id ) {
		$option = $this->get_option();

		$type = is_numeric( $id ) ? 'feed' : 'events';

		// Remove notification and add in dismissed array.
		if ( is_array( $option[ $type ] ) && ! empty( $option[ $type ] ) ) {
			foreach ( $option[ $type ] as $key => $notification ) {
				if ( $notification->id == $id ) { // phpcs:ignore WordPress.PHP.StrictComparisons
					// Add notification to dismissed array.
					array_unshift( $option['dismissed'], $notification );
					// Remove notification from feed or events.
					unset( $option[ $type ][ $key ] );
					break;
				}
			}
		}

		update_option( $this->option_name, $option, false );
	}

	/**
	 * Trigger an action from a notification
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function perform_notification_action( WP_REST_Request $request ) {
		$class  = $request->get_param( 'class' );
		$action = $request->get_param( 'action' );

		// Check if class exists
		if ( ! class_exists( $class ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => sprintf( __( 'Class %s does not exist.', 'userfeedback' ), $class ),
				),
				404
			);
		}

		$method_name = "perform_action_$action";

		// Check if action method exists
		if ( ! method_exists( $class, $method_name ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => sprintf( __( 'Method %1$s does not exist in class %2$s', 'userfeedback' ), $method_name, $class ),
				),
				404
			);
		}

		// Call action method in class
		$class_instance = new $class();
		$result         = (method_exists($class_instance, $method_name)) ? $class_instance->$method_name() : array();

		$result = array_merge(
			array(
				'success' => true,
			),
			$result
		);

		return new WP_REST_Response( $result, 200 );
	}

	// ----------------------

	/**
	 * Verify notifications
	 *
	 * @param $notifications
	 * @return array|UserFeedback_Notification_External[]
	 */
	public function process_feed_notifications( $notifications ) {
		$active_feed = array();

		foreach ( $notifications as $raw_notification ) {
			$notification = UserFeedback_Notification_External::make( $raw_notification );
			if ( $notification->should_display() ) {
				$active_feed[] = $notification;
			}
		}

		return $active_feed;
	}

	/**
	 * Fetch remote notifications feed
	 *
	 * @return array
	 */
	public function fetch_feed() {
		$res = wp_remote_get( self::REMOTE_SOURCE_URL );

		if ( is_wp_error( $res ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $res );

		if ( empty( $body ) ) {
			return array();
		}

		return $this->process_feed_notifications( json_decode( $body, true ) );
	}

	/**
	 * Update notifications with remote feeds
	 *
	 * @return void
	 */
	public function update_notifications() {
		$feed   = $this->fetch_feed();
		$option = $this->get_option();

		update_option(
			$this->option_name,
			array(
				'update'    => time(),
				'feed'      => $feed,
				'events'    => $option['events'],
				'dismissed' => array_slice( $option['dismissed'], 0, 30 ), // Limit dismissed notifications to last 30.
			),
			false
		);
	}

	/**
	 * Get option value.
	 *
	 * @param bool $cache Reference property cache if available.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_option( $cache = true ) {

		if ( $this->option && $cache ) {
			return $this->option;
		}

		$option = get_option( $this->option_name, array() );

		$this->option = array(
			'update'    => ! empty( $option['update'] ) ? $option['update'] : 0,
			'events'    => ! empty( $option['events'] ) ? $option['events'] : array(),
			'feed'      => ! empty( $option['feed'] ) ? $option['feed'] : array(),
			'dismissed' => ! empty( $option['dismissed'] ) ? $option['dismissed'] : array(),
		);

		return $this->option;
	}

	/**
	 * Get all notifications
	 *
	 * @return array
	 */
	public function get_all_notifications() {
		$option = $this->get_option();

		// Update notifications using async task.
		if ( empty( $option['update'] ) || time() > $option['update'] + DAY_IN_SECONDS ) {
			if ( false === wp_next_scheduled( 'userfeedback_admin_notifications_update' ) ) {
				wp_schedule_single_event( time(), 'userfeedback_admin_notifications_update' );
			}
		}

		$events = $option['events'];
		$feed   = $option['feed'];

		return array(
			'active'    => array_merge( $events, $feed ),
			'dismissed' => $option['dismissed'],
		);
	}

	/**
	 * Get active notifications.
	 * Active notifications are limited to 5 at a time.
	 *
	 * @return array
	 */
	public function get_active_notifications() {
		$notifications = $this->get_all_notifications();

		// Show only 5 active notifications plus any that has a priority of 1
		$all_active = isset($notifications['active']) ? $notifications['active'] : array();
		$displayed  = array();

		foreach ( $all_active as $notification ) {
			if ( count( $displayed ) < 5 ) {
				$displayed[] = $notification;
			}
		}

		return $displayed;
	}

	/**
	 * Get dismissed notifications
	 *
	 * @return array|mixed
	 */
	public function get_dismissed_notifications() {
		$notifications = $this->get_all_notifications();

		return isset($notifications['dismissed']) ? $notifications['dismissed'] :  array();
	}

	/**
	 * Get notifications count
	 *
	 * @return int
	 */
	public function get_notifications_count() {
		return sizeof( $this->get_active_notifications() );
	}

	/**
	 * Get notifications badge for admin sidebar
	 *
	 * @return string
	 */
	public function get_count_for_admin_sidebar() {
		$count = $this->get_notifications_count();

		if ( ! $count ) {
			return '';
		}

		return '<span class="userfeedback-notifications-indicator update-plugins">' . $count . '</span>';
	}

	/**
	 * Check if a notification has been dismissed before
	 *
	 * @param UserFeedback_Notification_Event $notification
	 *
	 * @return bool
	 */
	public function is_dismissed( UserFeedback_Notification_Event $notification ) {
		if ( empty( $notification->id ) ) {
			return true;
		}

		$option = $this->get_option();

		foreach ( $option['dismissed'] as $item ) {
			if ( $item->id === $notification->id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Perform last checks for a notification
	 *
	 * @return boolean
	 */
	public function verify_notification( UserFeedback_Notification_Event $notification ) {
		if ( empty( $notification->id ) || $this->is_dismissed( $notification ) ) {
			return false;
		}

		$option = $this->get_option();

		// Ignore if notification has already been dismissed.
		$notification_already_dismissed = false;
		if ( is_array( $option['dismissed'] ) && ! empty( $option['dismissed'] ) ) {
			foreach ( $option['dismissed'] as $dismiss_notification ) {
				if ( $notification->id === $dismiss_notification->id ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Add a manual notification event.
	 *
	 * @param UserFeedback_Notification_Event $notification Notification data.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function add( UserFeedback_Notification_Event $notification ) {

		if ( ! $this->verify_notification( $notification ) ) {
			return false;
		}

		$option = $this->get_option();

		$current_notifications = $option['events'];

		foreach ( $current_notifications as $item ) {
			if ( $item->id === $notification->id ) {
				return false;
			}
		}

		$notifications = array_merge( array( $notification ), $current_notifications );

		// Sort notifications by priority
		usort(
			$notifications,
			function( $a, $b ) {
				if ( ! isset( $a->priority ) || ! isset( $b->priority ) ) {
					return 0;
				}

				if ( $a->priority == $b->priority ) {
					return 0;
				}

				return $a->priority < $b->priority ? -1 : 1;
			}
		);

		update_option(
			$this->option_name,
			array(
				'update'    => $option['update'],
				'feed'      => $option['feed'],
				'events'    => $notifications,
				'dismissed' => $option['dismissed'],
			),
			false
		);

		return true;
	}

	/**
	 * Delete the notification options.
	 */
	public function delete_notifications_data() {
		delete_option( $this->option_name );
		userfeedback_notification_event_runner()->delete_data();
	}
}
