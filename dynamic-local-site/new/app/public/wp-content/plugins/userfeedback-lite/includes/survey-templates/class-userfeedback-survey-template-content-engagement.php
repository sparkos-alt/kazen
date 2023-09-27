<?php

class UserFeedback_Survey_Template_Content_Engagement extends UserFeedback_Survey_Template {


	/**
	 * @inheritdoc
	 */
	protected $template_key = 'content-engagement';

	/**
	 * @inheritdoc
	 */
	protected $is_pro = false;

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Content Engagement', 'userfeedback' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Measure what content is engaging, and what content to create.', 'userfeedback' );
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
					'title'  => __( 'Did you find this content engaging?', 'userfeedback' ),
					'config' => array(
						'options' => array(
							__( 'Yes', 'userfeedback' ),
							__( 'No', 'userfeedback' ),
						),
					),
				),
				array(
					'type'  => 'long-text',
					'title' => __( 'What content would you like us to create?', 'userfeedback' ),
				),
			),
		);
	}
}
