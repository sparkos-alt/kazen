<?php

class UserFeedback_Survey_Template_Web_Feedback extends UserFeedback_Survey_Template {


	/**
	 * @inheritdoc
	 */
	protected $template_key = 'web-feedback';

	/**
	 * @inheritdoc
	 */
	protected $is_pro = false;

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Website Feedback', 'userfeedback' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'See what users think about your website.', 'userfeedback' );
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
					'type'  => 'long-text',
					'title' => __( 'What can we do to improve this website?', 'userfeedback' ),
				),
			),
		);
	}
}
