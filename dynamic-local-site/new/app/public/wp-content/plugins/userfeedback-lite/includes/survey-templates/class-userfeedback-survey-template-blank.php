<?php

class UserFeedback_Survey_Template_Blank extends UserFeedback_Survey_Template {


	/**
	 * @inheritdoc
	 */
	protected $template_key = 'blank';

	/**
	 * @inheritdoc
	 */
	protected $is_pro = false;

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Start From Scratch', 'userfeedback' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Create a completely custom UserFeedback survey.', 'userfeedback' );
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public function get_config() {
		return array(
			'questions' => array(),
		);
	}
}
