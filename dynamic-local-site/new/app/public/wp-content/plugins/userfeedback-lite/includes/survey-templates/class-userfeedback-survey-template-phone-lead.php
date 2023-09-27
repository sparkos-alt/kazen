<?php

class UserFeedback_Survey_Template_Phone_Lead extends UserFeedback_Survey_Template {


	/**
	 * @inheritdoc
	 */
	protected $template_key = 'phone-lead';

	/**
	 * @inheritdoc
	 */
	protected $is_pro = false;

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Phone Lead Form', 'userfeedback' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Ask your customers for a phone number to receive a call back.', 'userfeedback' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public function get_config() {
		return array(
			'questions' => array(
				array(
					'type'  => 'text',
					'title' => __( "Have questions? Provide us your phone number and we'll give you a call!", 'userfeedback' ),
				),
				array(
					'type'  => 'text',
					'title' => __( "What's Your Name?", 'userfeedback' ),
				),
			),
		);
	}
}
