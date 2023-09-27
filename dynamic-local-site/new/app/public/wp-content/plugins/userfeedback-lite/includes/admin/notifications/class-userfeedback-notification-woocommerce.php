<?php

/**
 * WooCommerce Notification class.
 *
 * Notification shown when WooCommmerce has been installed for a few days.
 *
 * @see UserFeedback_Notification_Event
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage Notifications
 * @author  David Paternina
 */
class UserFeedback_Notification_WooCommerce extends UserFeedback_Notification_Event {

	public $id            = 'userfeedback_woocommerce';
	public $license_types = array( 'lite', 'plus', 'pro' );
	public $interval      = 5;

	public function prepare() {
		$this->title   = __( 'What\'s Stopping You From Making More Money?', 'userfeedback' );
		$this->content =
			__( 'Add a UserFeedback survey on your product pages and ask what is preventing your customer from purchasing.', 'userfeedback' );

		$this->buttons[] = array(
			'text' => __( 'Create Survey', 'userfeedback' ),
			'url'  => userfeedback_get_screen_url( 'userfeedback_surveys', 'new' ),
		);

		return parent::prepare();
	}

	public function should_display() {
		return parent::should_display() && class_exists( 'WooCommerce' );
	}

}

new UserFeedback_Notification_WooCommerce();
