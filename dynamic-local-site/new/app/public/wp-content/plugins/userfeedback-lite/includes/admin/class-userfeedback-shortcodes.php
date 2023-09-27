<?php

/**
 * Shortcodes class.
 *
 * Registers and handles UserFeedback shortcodes
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author  David Paternina
 */
class UserFeedback_Shortcodes {

	static $USERFEEDBACK_SURVEY_SHORTCODE = 'userfeedback';

	public function __construct() {
		$this->init_shortcodes();
	}

	/**
	 * Init shortcodes
	 *
	 * @return void
	 */
	public function init_shortcodes() {
		add_shortcode( self::$USERFEEDBACK_SURVEY_SHORTCODE, array( $this, 'handle_survey_shortcode' ) );
	}

	/**
	 * Register [userfeedback id=X] shortcode
	 *
	 * @param $attrs
	 * @return string
	 */
	public function handle_survey_shortcode( $attrs ) {
		if ( ! isset( $attrs['id'] ) ) {
			return '';
		}
		$survey_id = esc_attr($attrs['id']);
		return "<div class='userfeedback-survey-container' data-id='{$survey_id}'></div>";
	}
}

new UserFeedback_Shortcodes();
