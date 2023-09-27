<?php

class UserFeedback_Survey_Templates {

	/**
	 * Array of registered templates
	 *
	 * @var array
	 */
	private $templates = array();

	/**
	 * Class instance
	 *
	 * @var UserFeedback_Survey_Templates
	 */
	public static $instance;

	/**
	 * Get class instance
	 *
	 * @return UserFeedback_Survey_Templates|static
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Loads template files and registers them
	 */
	public function __construct() {
		// Load included templates

		require_once USERFEEDBACK_PLUGIN_DIR . 'includes/survey-templates/class-userfeedback-survey-template-blank.php';
		require_once USERFEEDBACK_PLUGIN_DIR . 'includes/survey-templates/class-userfeedback-survey-template-website-feedback.php';
		require_once USERFEEDBACK_PLUGIN_DIR . 'includes/survey-templates/class-userfeedback-survey-template-website-experience.php';
		require_once USERFEEDBACK_PLUGIN_DIR . 'includes/survey-templates/class-userfeedback-survey-template-content-engagement.php';
		require_once USERFEEDBACK_PLUGIN_DIR . 'includes/survey-templates/class-userfeedback-survey-template-restaurant-menu.php';
		require_once USERFEEDBACK_PLUGIN_DIR . 'includes/survey-templates/class-userfeedback-survey-template-phone-lead.php';

		// Register templates

		// Blank
		$blankTemplate = new UserFeedback_Survey_Template_Blank();
		$this->register_template( $blankTemplate->get_key(), $blankTemplate );

		// Website Feedback
		$webTemplate = new UserFeedback_Survey_Template_Web_Feedback();
		$this->register_template( $webTemplate->get_key(), $webTemplate );

		// Website Experience
		$webExperience = new UserFeedback_Survey_Template_Web_Experience();
		$this->register_template( $webExperience->get_key(), $webExperience );

		// Content Engagement
		$contentEngagement = new UserFeedback_Survey_Template_Content_Engagement();
		$this->register_template( $contentEngagement->get_key(), $contentEngagement );

		// Restaurant Menu
		$restaurantMenu = new UserFeedback_Survey_Template_Restaurant_Menu();
		$this->register_template( $restaurantMenu->get_key(), $restaurantMenu );

		// Phone Lead
		$phoneLead = new UserFeedback_Survey_Template_Phone_Lead();
		$this->register_template( $phoneLead->get_key(), $phoneLead );

		// Register Pro templates with name and descriptions only

		// Ecommerce
		$this->register_template(
			'ecommerce',
			array(
				'key'         => 'ecommerce',
				'name'        => __( 'eCommerce Store Survey (PRO)', 'userfeedback' ),
				'description' => __( 'Uncover why your visitors purchased from your store.', 'userfeedback' ),
			)
		);

		// B2B
		$this->register_template(
			'b2b',
			array(
				'key'         => 'b2b',
				'name'        => __( 'B2B Satisfaction Survey (PRO)', 'userfeedback' ),
				'description' => __( 'See what customers think about your product or service and find ways to improve.', 'userfeedback' ),
			)
		);

		// NPS
		$this->register_template(
			'nps',
			array(
				'key'         => 'nps',
				'name'        => __( 'NPS Survey (PRO)', 'userfeedback' ),
				'description' => __( 'See how likely a customer is to refer a friend or colleague.', 'userfeedback' ),
			)
		);

		// Ecommerce experience
		$this->register_template(
			'ecommerce-experience',
			array(
				'key'         => 'ecommerce-experience',
				'name'        => __( 'eCommerce Store Experience (PRO)', 'userfeedback' ),
				'description' => __( 'Quickly see how customers rate your store with a 1-5 scale.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'website-design',
			array(
				'key'         => 'website-design',
				'name'        => __( 'Website Design Feedback (PRO)', 'userfeedback' ),
				'description' => __( 'Find out how much your website users enjoy using your website. Get feedback on how to improve.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'ecommerce-conversion-optimization',
			array(
				'key'         => 'ecommerce-conversion-optimization',
				'name'        => __( 'eCommerce Conversion Optimization (PRO)', 'userfeedback' ),
				'description' => __( 'Understand why users are not making purchases, and collect their email address.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'nps-product-feedback',
			array(
				'key'         => 'nps-product-feedback',
				'name'        => __( 'NPS (R) Product Feedback (PRO)', 'userfeedback' ),
				'description' => __( 'Find out how likely customers are likely to refer your product, and what can be improved.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'b2b-buyer-survey',
			array(
				'key'         => 'b2b-buyer-survey',
				'name'        => __( 'B2B Buyer Persona Survey (PRO)', 'userfeedback' ),
				'description' => __( 'Learn more about the buyers shopping at your eCommerce store.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'post-purchase',
			array(
				'key'         => 'post-purchase',
				'name'        => __( 'Post Purchase Review (PRO)', 'userfeedback' ),
				'description' => __( 'Increase conversions by understanding how easy your checkout process is to complete.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'product-usage',
			array(
				'key'         => 'product-usage',
				'name'        => __( 'Product Usage Survey (PRO)', 'userfeedback' ),
				'description' => __( 'Uncover how often your product or service is actually used.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'pricing-page-info',
			array(
				'key'         => 'pricing-page-info',
				'name'        => __( 'Pricing Page Information (PRO)', 'userfeedback' ),
				'description' => __( 'Determine what questions can be answered from your pricing page to maximize conversions.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'product-usage-info',
			array(
				'key'         => 'product-usage-info',
				'name'        => __( 'Product Usage Information (PRO)', 'userfeedback' ),
				'description' => __( 'See how where and how often your product or service is being used.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'buyer-journey',
			array(
				'key'         => 'buyer-journey',
				'name'        => __( 'Buyer Journey Research (PRO)', 'userfeedback' ),
				'description' => __( 'Learn how users find your website, so you can maximize your marketing budget.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'beta-opt-in',
			array(
				'key'         => 'beta-opt-in',
				'name'        => __( 'User Beta Testing Opt-in (PRO)', 'userfeedback' ),
				'description' => __( 'Easily find users for your latest feature or beta testing period.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'product-offering',
			array(
				'key'         => 'product-offering',
				'name'        => __( 'Product Offering Intelligence (PRO)', 'userfeedback' ),
				'description' => __( "Find out why someone didn't purchase from you, and collect their email address.", 'userfeedback' ),
			)
		);

		$this->register_template(
			'feature-research',
			array(
				'key'         => 'feature-research',
				'name'        => __( 'Website Feature Research (PRO)', 'userfeedback' ),
				'description' => __( 'Target website features to add to maximize your conversion rates.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'competitive-research',
			array(
				'key'         => 'competitive-research',
				'name'        => __( 'Competitive Research (PRO)', 'userfeedback' ),
				'description' => __( 'Find out why customers choose your brand over another.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'content-research',
			array(
				'key'         => 'competitive-research',
				'name'        => __( 'Content Research (PRO)', 'userfeedback' ),
				'description' => __( 'Learn which content is engaging, and what content to create.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'product-research',
			array(
				'key'         => 'product-research',
				'name'        => __( 'Product Research (PRO)', 'userfeedback' ),
				'description' => __( 'Determine what features you should stop advertising.', 'userfeedback' ),
			)
		);

		$this->register_template(
			'saas-feedback',
			array(
				'key'         => 'saas-feedback',
				'name'        => __( 'SAAS Feature Feedback (PRO)', 'userfeedback' ),
				'description' => __( 'Uncover which features are missing from your offering so that you can attract more customers.', 'userfeedback' ),
			)
		);
	}

	/**
	 * Registers template in $templates array
	 * Template data is passed through the userfeedback_register_template_{template_key} before registering
	 *
	 * @param UserFeedback_Survey_Template|array $template
	 * @return void
	 */
	public function register_template( $key, $template ) {

		if ( $template instanceof UserFeedback_Survey_Template ) {
			$this->templates[ $key ] = $template->get_data();
		} elseif ( is_array( $template ) ) {
			$this->templates[ $key ] = $template;
		}
	}

	/**
	 * Returns the registered templates
	 *
	 * @return array
	 */
	public function get_available_templates() {
		return $this->templates;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'userfeedback' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'userfeedback' ), '1.0.0' );
	}
}
