<?php

class UserFeedback_Survey_Template_Restaurant_Menu extends UserFeedback_Survey_Template {


	/**
	 * @inheritdoc
	 */
	protected $template_key = 'restaurant-menu';

	/**
	 * @inheritdoc
	 */
	protected $is_pro = false;

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Restaurant Menu Survey', 'userfeedback' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'See which items to add to your menu.', 'userfeedback' );
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
					'title' => __( 'What items should we add to our menu? ', 'userfeedback' ),
				),
			),
		);
	}
}
