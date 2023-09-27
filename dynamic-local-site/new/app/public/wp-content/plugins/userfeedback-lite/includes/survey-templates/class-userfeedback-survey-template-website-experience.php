<?php

class UserFeedback_Survey_Template_Web_Experience extends UserFeedback_Survey_Template {


	/**
	 * @inheritdoc
	 */
	protected $template_key = 'web-experience';

	/**
	 * @inheritdoc
	 */
	protected $is_pro = false;

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Website Experience', 'userfeedback' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Learn how customers currently rate your website experience.', 'userfeedback' );
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
					'type'   => 'radio-button',
					'title'  => __( 'On a scale of 1-5, how would you rate your experience? ', 'userfeedback' ),
					'config' => array(
						'options' => array( 1, 2, 3, 4, 5 ),
					),
				),
			),
		);
	}
}
