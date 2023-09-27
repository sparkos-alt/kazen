<?php

/**
 * Dashboard widget class.
 *
 * @see UserFeedback_Notification_Event
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author  David Paternina
 */
class UserFeedback_Dashboard_Widget {

	const WIDGET_KEY = 'userfeedback_surveys_widget';

	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
	}

	/**
	 * Register dashboard widget
	 *
	 * @return void
	 */
	public function register_dashboard_widget() {
		global $wp_meta_boxes;

		wp_add_dashboard_widget(
			self::WIDGET_KEY,
			esc_html__( 'UserFeedback', 'userfeedback' ),
			array( $this, 'dashboard_widget_content' )
		);

		// Attempt to place the widget at the top.
		$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
		$widget_instance  = array( self::WIDGET_KEY => $normal_dashboard[ self::WIDGET_KEY ] );
		unset( $normal_dashboard[ self::WIDGET_KEY ] );
		$sorted_dashboard                             = array_merge( $widget_instance, $normal_dashboard );
		$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
	}

	/**
	 * Render widget container div
	 *
	 * @return void
	 */
	public function dashboard_widget_content() {
		echo '<div id="userfeedback-dashboard-widget"></div>';
	}
}

new UserFeedback_Dashboard_Widget();
