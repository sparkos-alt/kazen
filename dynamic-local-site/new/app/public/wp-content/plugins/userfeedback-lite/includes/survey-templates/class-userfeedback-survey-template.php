<?php

abstract class UserFeedback_Survey_Template {

	/**
	 * Template key
	 *
	 * @var string
	 */
	protected $template_key;

	/**
	 * Is the template included in Pro only
	 *
	 * @var bool
	 */
	protected $is_pro = true;

	/**
	 * Returns the template key
	 *
	 * @return string Template key
	 */
	public function get_key() {
		return $this->template_key;
	}

	/**
	 * Returns the localized template name
	 *
	 * @return mixed|void
	 */
	abstract public function get_name();

	/**
	 * Returns the localized template description
	 * Passes through a filter allowing for customization
	 *
	 * @return mixed|void
	 */
	abstract public function get_description();

	/**
	 * Returns all data for the template
	 *
	 * @return array
	 */
	public function get_data() {
		$key    = $this->get_key();
		$config = apply_filters( "userfeedback_get_template_config_{$key}", $this->get_config() );

		return array(
			'key'             => $key,
			'name'            => $this->get_name(),
			'description'     => $this->get_description(),
			'config'          => $config,
			'is_available'    => ! $this->is_pro || ( userfeedback_is_pro_version() && userfeedback_is_licensed() ),
			'required_addons' => $this->get_required_addons(),
		);
	}

	/**
	 * Get array of the addons required by this template
	 *
	 * @return array
	 */
	public function get_required_addons() {
		return array();
	}

	/**
	 * Returns the template config.
	 * Returns null if the template is not supported (is Pro only)
	 *
	 * @return array|null
	 */
	abstract function get_config();
}
