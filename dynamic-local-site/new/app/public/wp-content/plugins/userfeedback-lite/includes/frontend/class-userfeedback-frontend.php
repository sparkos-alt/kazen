<?php

/**
 * Frontend class.
 *
 * Handles API calls made from the fronted by the Survey Widget
 * Also handles the preview route
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author  David Paternina
 */
class UserFeedback_Frontend
{

	// Helpful static variables
	static $USERFEEDBACK_SURVEY_COOKIE_PREFIX = 'userfeedback-survey-';

	public function __construct()
	{
		require_once USERFEEDBACK_PLUGIN_DIR . 'includes/lib/class-mobile-detect.php';

		add_action('template_redirect', array($this, 'enqueue_styles_and_scripts_for_surveys'), 20);
		add_action('rest_api_init', array($this, 'register_frontend_routes'));

	}

	/**
	 * Register frontend REST routes
	 *
	 * @return void
	 */
	public function register_frontend_routes()
	{
		register_rest_route(
			'userfeedback/v1',
			'/surveys/(?P<id>\w+)/responses',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'save_survey_response'),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/surveys/(?P<id>\w+)/impression',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'record_survey_impression'),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Validate individual answer
	 *
	 * @param $value
	 * @param $question
	 * @return array
	 */
	private function validate_individual_answer($value, $question)
	{

		// Skip validation for empty values on non-required questions
		if (is_null($value) && !$question->settings->required) {
			return array(
				'is_valid' => true,
				'errors'   => array(),
			);
		}

		if (is_array($value)) {

			// Check if question accepts array as value
			if ($question->type !== 'checkbox') {
				return array(
					'is_valid' => false,
					'errors'   => array(
						sprintf(
							__("Question [%s] can't take an array as value.", 'userfeedback'),
							$question->id
						),
					),
				);
			}

			// Recursively check each value
			$errors   = array();
			$is_valid = true;
			foreach ($value as $inner_value) {
				$answer_validation = $this->validate_individual_answer($inner_value, $question);
				$is_valid          = $is_valid && $answer_validation['is_valid'];
				$errors            = array_merge($errors, $answer_validation['errors']);
			}

			return array(
				'is_valid' => $is_valid,
				'errors'   => $errors,
			);
		} else {
			$errors   = array();
			$is_valid = true;

			// Check if string has less than 400 characters
			if (strlen($value) > 400) {
				$is_valid = false;
				$errors[] = __('Value exceeds 400 characters.', 'userfeedback');
			}

			// If question accepts multiple values, check if $value is a valid option
			$types_with_options = array('checkbox', 'radio-button');

			if (in_array($question->type, $types_with_options)) {
				$available_options = $question->config->options;
				$value_is_allowed  = in_array($value, $available_options);

				$is_valid = $is_valid && $value_is_allowed;

				if (!$value_is_allowed) {
					$errors[] = sprintf(
						__('Value "%s" is not allowed', 'userfeedback'),
						$value
					);
				}
			} elseif ($question->type === 'email') {
				// Validate email
				$filtered_email = sanitize_email($value);

				if (empty($filtered_email)) {
					$is_valid = false;
					$errors[] = sprintf(
						__('The provided email is not valid', 'userfeedback'),
						$value
					);
				}
			}

			// Validations for star-rating and nps are included in the question-types addon

			return apply_filters(
				'userfeedback_answer_validation',
				array(
					'is_valid' => $is_valid,
					'errors'   => $errors,
				),
				$value,
				$question
			);
		}
	}

	/**
	 * Validate Response answers
	 *
	 * @param $answers
	 * @param $survey_id
	 * @return array
	 */
	private function validate_response_answers($answers, $survey_id)
	{
		$errors  = array();
		$success = true;

		$survey = UserFeedback_Survey::find($survey_id);

		// Check if Survey exists
		if (!$survey) {
			return array(
				'success' => false,
				'errors'  => array(
					sprintf(
						__('Survey with id %s does not exist.', 'userfeedback'),
						$survey_id
					),
				),
				'status'  => 404,
			);
		}

		// Basic validation
		foreach ($answers as $answer) {
			$question_id = $answer['question_id'];
			$value       = $answer['value'];

			$question          = UserFeedback_Survey::search_question($survey, $question_id);
			$answer_validation = $this->validate_individual_answer($value, $question);

			$success = $success && $answer_validation['is_valid'];

			if (!$answer_validation['is_valid']) {
				$errors[] = array(
					'question_id' => $question_id,
					'errors'      => $answer_validation['errors'],
				);
			}
		}

		return array(
			'success' => $success,
			'errors'  => $errors,
		);
	}

	/**
	 * Save Survey response
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function save_survey_response(WP_REST_Request $request)
	{
		if (!wp_verify_nonce($request->get_param('nonce'), "userfeedback_survey_submission-{$request['id']}")) {
			return new WP_REST_Response(null, 403);
		}

		$survey_id = $request['id'];

		$id_address = UserFeedback_Device_Detect::ip();

		$os      = UserFeedback_Device_Detect::os();
		$browser = UserFeedback_Device_Detect::browser();

		$device = UserFeedback_Device_Detect::deviceType();

		// Validate Answers
		$validation = $this->validate_response_answers($request->get_json_params()['answers'], $survey_id);

		if (!$validation['success']) {
			$status = isset($validation['status']) ? $validation['status'] : 400;
			return new WP_REST_Response(
				array(
					'success' => false,
					'errors'  => $validation['errors'],
				),
				$status
			);
		}

		// Sanitize requests answers
		$request_params = $request->get_json_params();
		if ( ! empty( $request_params['answers'] ) ) {
			foreach( $request_params['answers'] as $index => $answer ) {
				// Sanitize answer's value.
				$value = sanitize_text_field( $answer['value'] );
				$request_params['answers'][ $index ]['value'] = $value;

				// Sanitize answer's comments.
				if ( ! empty( $answer['extra']['comments'] ) ) {
					$comments = sanitize_text_field( $answer['extra']['comments'] );
					$request_params['answers'][ $index ]['extra']['comments'] = $comments;
				}
			}
		}

		if ( ! empty( $request_params['page_submitted'] ) ) {
			if ( ! empty( $request_params['page_submitted']['id'] ) ) {
				$request_params['page_submitted']['id'] = intval( $request_params['page_submitted']['id'] );
			}
			if ( ! empty( $request_params['page_submitted']['name'] ) ) {
				$request_params['page_submitted']['name'] = sanitize_text_field( $request_params['page_submitted']['name'] );
			}
		}

		$response_id = UserFeedback_Response::create(
			array_merge(
				$request_params,
				array(
					'survey_id'    => sanitize_text_field($survey_id),
					'user_ip'      => $id_address,
					'user_browser' => $browser,
					'user_os'      => $os,
					'user_device'  => $device,
				)
			)
		);

		do_action('userfeedback_survey_response', $survey_id, $response_id, $request->get_json_params());

		return new WP_REST_Response(
			array(
				'success'     => true,
				'response_id' => $response_id,
			)
		);
	}

	/**
	 * Record Survey impression
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function record_survey_impression(WP_REST_Request $request)
	{
		if (!wp_verify_nonce($request->get_param('nonce'), "userfeedback_survey_impression-{$request['id']}")) {
			return new WP_REST_Response(null, 403);
		}

		$survey_id = $request['id'];
		UserFeedback_Survey::record_impression($survey_id);

		return new WP_REST_Response(null, 204);
	}

	/**
	 * Load frontend styles
	 *
	 * @return void
	 */
	public function enqueue_styles_and_scripts_for_surveys()
	{
		global $wp_query, $post;

		$user_is_admin = current_user_can('administrator');
		if (empty($post) || ($user_is_admin && !apply_filters('userfeedback_enable_surveys_for_admins', false))) {
			return;
		}

		$surveys = $this->get_surveys_for_current_page();

		$surveys = array_map(
			function ($survey) {
				$survey->nonces = array(
					'submission' => wp_create_nonce("userfeedback_survey_submission-{$survey->id}"),
					'impression' => wp_create_nonce("userfeedback_survey_impression-{$survey->id}"),
				);
				return $survey;
			},
			$surveys
		);

		/*
		 * If there are no Surveys available, we don't enqueue the scripts
		 */
		if (sizeof($surveys) > 0 || has_shortcode($post->post_content, UserFeedback_Shortcodes::$USERFEEDBACK_SURVEY_SHORTCODE)) {
			$this->enqueue_frontend_styles();
			$this->enqueue_base_frontend_scripts();
			$this->enqueue_frontend_scripts_for_surveys($surveys);
		}
	}

	/**
	 * Get Surveys available for the current page
	 *
	 * @return array
	 */
	public function get_surveys_for_current_page()
	{
		$surveys = UserFeedback_Survey::where(
			array(
				'status'     => 'publish',
				'publish_at' => null,
			)
		)->or_where(
			array(
				'status' => 'publish',
				array(
					'publish_at',
					'<',
					current_time('mysql', true),
				),
			)
		)->select(array('title', 'questions', 'settings'))
			->sort('id', 'desc')
			->get();

		$surveys = array_map(
			function ($survey) {
				$thank_you_config = $survey->settings->thank_you;

				if ($thank_you_config->type === 'redirect') {
					$page_id = $thank_you_config->redirect_to->id;

					if ('publish' == get_post_status($page_id)) {
						$thank_you_config->redirect_url = get_permalink($page_id);
					}
				}

				$survey->cookie_name = self::get_survey_cookie_name($survey);
				return $survey;
			},
			$surveys
		);

		return $surveys;
	}

	/**
	 * Get frontend asset full URL
	 *
	 * @since 1.0.0
	 * @param $path
	 * @return mixed|void
	 */
	public static function get_frontend_asset_url($path)
	{
		return esc_url(
			apply_filters(
				'userfeedback_frontend_assets_url',
				plugins_url($path, USERFEEDBACK_PLUGIN_FILE),
				$path
			)
		);
	}

	/**
	 * Enqueue frontend styles
	 *
	 * @return void
	 */
	public function enqueue_frontend_styles()
	{
		// Enqueue with super low priority so these load after theme CSS files
		add_action(
			'wp_head',
			function () {
				wp_enqueue_style(
					'userfeedback-frontend-styles',
					$this->get_frontend_asset_url('/assets/vue/css/frontend.css'),
					array(),
					userfeedback_get_asset_version()
				);
				$userfeedback_settings = userfeedback_get_options();
				// load custom fonts for the widget
				if(isset($userfeedback_settings['widget_font']['family']) && $userfeedback_settings['widget_font']['family'] !== ''){
					$font_family = str_replace(' ', '+', $userfeedback_settings['widget_font']['family']);
					$font_weight = (isset($userfeedback_settings['widget_font']['weight']) && $userfeedback_settings['widget_font']['weight'] !== '') ? $userfeedback_settings['widget_font']['weight'] : 'regular';
					$font_url = '';
					if($font_weight === 'regular'){
						$font_url = "https://fonts.googleapis.com/css?family={$font_family}&display=swap";
					}else {
						$font_url = "https://fonts.googleapis.com/css?family={$font_family}:{$font_weight}&display=swap";
					}
					wp_enqueue_style(
						'userfeedback-frontend-fonts',
						$font_url,
						array(),
						userfeedback_get_asset_version()
					); 
					echo "<style>.userfeedback-widget * {
						font-family: '{$userfeedback_settings['widget_font']['family']}' !important;
					}</style>";
				}
				
			},
			20
		);
	}

	/**
	 * Load frontend scripts
	 *
	 * @return void
	 */
	public function enqueue_base_frontend_scripts()
	{
		wp_register_script(
			'userfeedback-frontend-vendors',
			$this->get_frontend_asset_url('/assets/vue/js/chunk-vendors.js'),
			array(),
			userfeedback_get_asset_version(),
			true
		);
		wp_enqueue_script('userfeedback-frontend-vendors');

		wp_register_script(
			'userfeedback-frontend-common',
			$this->get_frontend_asset_url('/assets/vue/js/chunk-common.js'),
			array(),
			userfeedback_get_asset_version(),
			true
		);
		wp_enqueue_script('userfeedback-frontend-common');

		wp_localize_script(
			'userfeedback-frontend-common',
			'userfeedback_addons_frontend',
			array()
		);

		wp_register_script(
			'userfeedback-frontend-widget',
			$this->get_frontend_asset_url('/assets/vue/js/frontend.js'),
			apply_filters('userfeedback_frontend_script_dependencies', array()),
			userfeedback_get_asset_version(),
			true
		);
	}

	/**
	 * Enqueue and localize script for preview
	 *
	 * @param $survey
	 * @return void
	 */
	public function enqueue_frontend_scripts_for_preview($survey)
	{
		wp_enqueue_script('userfeedback-frontend-widget');

		$localization_object = apply_filters(
			'userfeedback_frontend_script_localization',
			array(
				'wp_rest_nonce'      => wp_create_nonce('wp_rest'),
				'rest_url'           => rest_url(),
				'assets'             => plugins_url('/assets/vue', USERFEEDBACK_PLUGIN_FILE),
				'is_pro'             => userfeedback_is_pro_version(),
				'is_licensed'        => userfeedback_is_licensed(),
				'use_survey'         => $survey,
				'addons'             => array(),
				'widget_settings'    => userfeedback_get_frontend_widget_settings(),
			)
		);

		wp_localize_script(
			'userfeedback-frontend-widget',
			'userfeedback_frontend',
			$localization_object
		);
	}

	/**
	 * Enqueue Frontend scripts
	 *
	 * @return void
	 */
	public function enqueue_frontend_scripts_for_surveys($surveys)
	{
		global $post, $current_user;

		if (!is_admin() && $post) {
			$user_roles = [];
			if (is_user_logged_in()) {
				$user_roles = $current_user->roles;
			}
			wp_enqueue_script('userfeedback-frontend-widget');

			$localization_object = apply_filters(
				'userfeedback_frontend_script_localization',
				array(
					'wp_rest_nonce'   => wp_create_nonce('wp_rest'),
					'rest_url'        => rest_url(),
					'assets'          => plugins_url('/assets/vue', USERFEEDBACK_PLUGIN_FILE),
					'is_pro'          => userfeedback_is_pro_version(),
					'is_licensed'     => userfeedback_is_licensed(),
					'surveys'         => $surveys,
					'widget_settings' => userfeedback_get_frontend_widget_settings(),
					'is_preview'      => false,
					'integrations'    => array(),
					'addons'          => get_option('userfeedback_parsed_addons'),
					'current_page'    => array(
						'id'   => $post->ID,
						'name' => $post->post_title,
					),
					'logic' => [
						'user_logged_in' => is_user_logged_in(),
						'page_type' => userfeedback_get_type_of_page(),
						'post_type' => (is_singular()) ? get_post_type() : false,
						'post_id' => (is_singular()) ? $post->ID : false,
						'taxonomy' => userfeedback_get_taxonomy(),
						'taxonomy_term' => userfeedback_get_term(),
						'current_url' => userfeedback_get_current_url(),
						'referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false,
					],
				)
			);

			wp_localize_script(
				'userfeedback-frontend-widget',
				'userfeedback_frontend',
				$localization_object
			);
		}
	}

	/**
	 * Get cookie name for survey
	 *
	 * @param $survey
	 * @return string
	 */
	public static function get_survey_cookie_name($survey)
	{
		return self::$USERFEEDBACK_SURVEY_COOKIE_PREFIX . $survey->id;
	}
}

new UserFeedback_Frontend();
