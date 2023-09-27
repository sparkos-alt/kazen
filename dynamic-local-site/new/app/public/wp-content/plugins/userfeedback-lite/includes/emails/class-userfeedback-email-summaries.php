<?php
/**
 * Email Summaries main class.
 *
 * @since 1.0.0
 */
class UserFeedback_Email_Summaries {

	/**
	 * Email template to use for this class.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $email_template = 'summaries';

	/**
	 * Test email template
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $test_email_template = 'summaries';

	/**
	 * Email options
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $email_options;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$options                              = array();
		$disable_email_summaries              = userfeedback_get_option( 'summaries_disabled' );
		$options['email_summaries']           = ! $disable_email_summaries;
		$options['summaries_html_template']   = userfeedback_get_option( 'summaries_html_template' );
		$options['summaries_carbon_copy']     = userfeedback_get_option( 'summaries_carbon_copy' );
		$options['summaries_email_addresses'] = userfeedback_get_option( 'summaries_email_addresses' );
		$options['summaries_header_image']    = userfeedback_get_option( 'notifications_header_image' );

		$this->email_options = $options;
		$this->hooks();

		if ( ! $disable_email_summaries && wp_next_scheduled( 'userfeedback_email_summaries_cron' ) ) {
			wp_clear_scheduled_hook( 'userfeedback_email_summaries_cron' );
		}

		if ( ! $disable_email_summaries && ! wp_next_scheduled( 'userfeedback_email_summaries_cron' ) ) {
			wp_schedule_event( $this->get_first_cron_date(), 'userfeedback_email_summaries_weekly', 'userfeedback_email_summaries_cron' );
		}
	}

	/**
	 * Email Summaries hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		if ( $this->email_options['email_summaries'] ) {
			add_action( 'init', array( $this, 'preview' ) );
			add_filter( 'userfeedback_email_template_paths', array( $this, 'add_email_template_path' ) );
			add_filter( 'userfeedback_emails_templates_set_initial_args', array( $this, 'set_template_args' ) );
			add_filter( 'cron_schedules', array( $this, 'add_weekly_cron_schedule' ) );
			add_action( 'userfeedback_email_summaries_cron', array( $this, 'cron' ) );
			add_action( 'wp_ajax_userfeedback_send_test_summary_email', array( $this, 'send_test_email' ) );
			add_action( 'userfeedback_after_update_settings', array( $this, 'reset_email_summaries_options' ), 10, 2 );
		}

	}

	/**
	 * Load required scripts for email summaries features
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function admin_scripts() {
		if ( userfeedback_screen_is_settings() ) {
			// This will load the required dependencies for the WordPress media uploader
			wp_enqueue_media();
		}
	}

	/**
	 * Check if Email Summaries are enabled in settings.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function is_enabled() {
		if ( ! isset( $this->is_enabled ) ) {
			$this->is_enabled = false;
		}

		return apply_filters( 'userfeedback_emails_summaries_is_enabled', $this->is_enabled );
	}

	/**
	 * Preview Email Summary.
	 *
	 * @since 1.0.0
	 */
	public function preview() {

		if ( ! current_user_can( 'userfeedback_save_settings' ) ) {
			return;
		}

		if ( ! $this->is_preview() ) {
			return;
		}

		// initiate email class.
		$emails = new UserFeedback_WP_Emails( $this->email_template );

		// check if html template option is enabled
		if ( ! $this->is_enabled_html_template() ) {
			$emails->__set( 'html', false );
		}

		$content = $emails->build_email();

		if ( ! $this->is_enabled_html_template() ) {
			$content = wpautop( $content );
		}

		echo $content; // phpcs:ignore

		exit;
	}

	/**
	 * Check whether it's in preview mode
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_preview() {
		if ( isset( $_GET['userfeedback_email_preview'], $_GET['userfeedback_email_template'] ) && 'summary' === $_GET['userfeedback_email_template'] ) { // phpcs:ignore
			return true;
		}
		return false;
	}

	/**
	 * Get the email header image.
	 *
	 * @since 1.0.0
	 *
	 * @return string The email from address.
	 */
	public function get_header_image() {
		// set default header image
		$img = array(
			'url' => plugins_url( 'assets/img/emails/userfeedback-logo.png', USERFEEDBACK_PLUGIN_FILE ),
			'2x'  => '', // plugins_url( "assets/img/emails/logo-MonsterInsights@2x.png", USERFEEDBACK_PLUGIN_FILE ),
		);

		if ( userfeedback_is_pro_version() && userfeedback_is_licensed() && ! empty( $this->email_options['summaries_header_image'] ) ) {
			$img['url'] = $this->email_options['summaries_header_image'];
			$img['2x']  = '';
		}

		return apply_filters( 'userfeedback_email_header_image', $img );
	}

	/**
	 * Get next cron occurrence date.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	protected function get_first_cron_date() {
		$schedule           = array();
		$schedule['day']    = rand( 0, 1 );
		$schedule['hour']   = rand( 0, 23 );
		$schedule['minute'] = rand( 0, 59 );
		$schedule['second'] = rand( 0, 59 );
		$schedule['offset'] = ( $schedule['day'] * DAY_IN_SECONDS ) +
							  ( $schedule['hour'] * HOUR_IN_SECONDS ) +
							  ( $schedule['minute'] * MINUTE_IN_SECONDS ) +
							  $schedule['second'];
		$date               = strtotime( 'next saturday' ) + $schedule['offset'];

		return $date;
	}

	/**
	 * Add summaries email template path
	 *
	 * @since 1.0.0
	 *
	 * @param array $schedules WP cron schedules.
	 *
	 * @return array
	 */
	public function add_email_template_path( $file_paths ) {
		// $file_paths['1000'] = USERFEEDBACK_PLUGIN_DIR . 'pro/includes/emails/templates';
		return $file_paths;
	}

	/**
	 * Add custom Email Summaries cron schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param array $schedules WP cron schedules.
	 *
	 * @return array
	 */
	public function add_weekly_cron_schedule( $schedules ) {
		$schedules['userfeedback_email_summaries_weekly'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display'  => esc_html__( 'Weekly UserFeedback Email Summaries', 'userfeedback' ),
		);

		return $schedules;
	}

	/**
	 * Get email subject
	 *
	 * @since 1.0.0
	 */
	public function get_email_subject() {

		$site_url        = get_site_url();
		$site_url_parsed = parse_url( $site_url );// Can't use wp_parse_url as that was added in WP 4.4 and we still support 3.8.
		$site_url        = isset( $site_url_parsed['host'] ) ? $site_url_parsed['host'] : $site_url;

		// Translators: The domain of the site is appended to the subject.
		$subject = sprintf( __( 'UserFeedback Summary - %s', 'userfeedback' ), $site_url );

		return apply_filters( 'userfeedback_emails_summaries_cron_subject', $subject );
	}

	/**
	 * Get email addresses to send
	 *
	 * @since 1.0.0
	 */
	public function get_email_addresses() {
		$emails          = array();
		$email_addresses = $this->email_options['summaries_email_addresses'];

		if ( ! empty( $email_addresses ) && is_array( $email_addresses ) ) {
			foreach ( $email_addresses as $email_address ) {
				if ( ! empty( $email_address ) && is_email( $email_address ) ) {
					$emails[] = $email_address;
				}
			}
		} else {
			$emails[] = get_option( 'admin_email' );
		}

		return apply_filters( 'userfeedback_email_addresses_to_send', $emails );
	}

	/**
	 * check if carbon copy option is enabled
	 *
	 * @since 1.0.0
	 */
	public function is_cc_enabled() {
		$value = false;
		if ( 'yes' === $this->email_options['summaries_carbon_copy'] ) {
			$value = true;
		}
		return apply_filters( 'userfeedback_email_cc_enabled', $value, $this );
	}

	/**
	 * Check if html template option is turned on
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_enabled_html_template() {
		$value = true;
		if ( false === $this->email_options['summaries_html_template'] ) {
			$value = false;
		}
		return apply_filters( 'userfeedback_email_html_template', $value, $this );
	}

	/**
	 * Email Summaries cron callback.
	 *
	 * @since 1.0.0
	 */
	public function cron() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$email            = array();
		$email['subject'] = $this->get_email_subject();
		$email['address'] = $this->get_email_addresses();
		$email['address'] = array_map( 'sanitize_email', $email['address'] );

		// Create new email.
		$emails = new UserFeedback_WP_Emails( $this->email_template );

		// Maybe include CC.
		if ( $this->is_cc_enabled() ) {
			$emails->__set( 'cc', implode( ',', $this->get_email_addresses() ) );
		}

		// check if html template option is enabled
		if ( ! $this->is_enabled_html_template() ) {
			$emails->__set( 'html', false );
		}

		// Go.
		foreach ( $email['address'] as $address ) {
			$emails->send( trim( $address ), $email['subject'] );
		}
	}

	/**
	 * Send test email
	 *
	 * @since 1.0.0
	 */
	public function send_test_email() {
		// Run a security check first.
		check_ajax_referer( 'uf-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'userfeedback_save_settings' ) ) {
			return;
		}

		$email            = array();
		$email['subject'] = '[Test email] UserFeedback Summary';
		$email['address'] = $this->get_email_addresses();
		$email['address'] = array_map( 'sanitize_email', $email['address'] );

		// Create new email.
		$emails = new Userfeedback_WP_Emails( $this->test_email_template );

		// Maybe include CC.
		if ( $this->is_cc_enabled() ) {
			$emails->__set( 'cc', implode( ',', $this->get_email_addresses() ) );
		}

		// check if html template option is enabled
		if ( ! $this->is_enabled_html_template() ) {
			$emails->__set( 'html', false );
		}

		// Go.
		foreach ( $email['address'] as $address ) {
			if ( ! $emails->send( trim( $address ), $email['subject'] ) ) {
				wp_send_json_error();
			}
		}
		wp_send_json_success();
	}

	/**
	 * Email summaries template arguments
	 *
	 * @since 1.0.0
	 */
	public function set_template_args( $args ) {

		$start_date = $this->get_summaries_start_date();
		$end_date   = $this->get_summaries_end_date();

		$args['body']['preview_title']    = $this->get_email_subject();
		$args['body']['header_image']     = $this->get_header_image();
		$args['body']['title']            = esc_html__( 'Hi there!', 'userfeedback' );
		$args['body']['description']      =
			sprintf(
				esc_html__( 'Below is the total number of survey responses for each active survey from the week of %s ', 'userfeedback' ),
				date( 'F j, Y', strtotime( $start_date ) ) . ' - ' . date( 'F j, Y', strtotime( $end_date ) )
			);
		$args['body']['summaries']        = $this->get_summaries();
		$args['body']['settings_tab_url'] = esc_url( admin_url( 'admin.php?page=userfeedback_settings#/email' ) );

		return apply_filters( 'userfeedback_email_summaries_template_args', $args );
	}

	/**
	 * get the start date from the last week
	 *
	 * @since 1.0.0
	 */
	public function get_summaries_start_date() {
		return date( 'Y-m-d', strtotime( '-1 day, last week' ) ); // sunday of last week
	}

	/**
	 * get the end date from the last week
	 *
	 * @since 1.0.0
	 */
	public function get_summaries_end_date() {
		return date( 'Y-m-d', strtotime( 'last saturday' ) ); // last saturday
	}

	/**
	 * data for email template
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function get_summaries() {

		$start_date = new DateTime( $this->get_summaries_start_date() );
		$end_date   = new DateTime( $this->get_summaries_end_date() );

		$start_date = $start_date->format( 'Y-m-d' );
		$end_date   = $end_date->format( 'Y-m-d' );

		$where_config = array(
			array(
				'submitted_at',
				'>=',
				$start_date,
			),
			array(
				'submitted_at',
				'<=',
				$end_date,
			),
		);

		$responses = UserFeedback_Response::where( $where_config )
			->select( array( 'id', 'survey_id', 'submitted_at' ) )
			->with( array( 'survey' ) )
			->group_by( 'survey_id' )
			->get();

		return array_map(
			function( $result ) {
				return array(
					'name'      => $result->survey->title,
					'responses' => $result->count,
				);
			},
			$responses
		);
	}


	/**
	 * reset email summaries options
	 *
	 * @since 1.0.0
	 */
	public function reset_email_summaries_options( $key, $value ) {
		if ( isset( $key ) && $key === 'email_summaries' && isset( $value ) && $value === 'off' ) {
			$default_email = array(
				'email' => get_option( 'admin_email' ),
			);
			userfeedback_update_option( 'summaries_email_addresses', array( $default_email ) );
			userfeedback_update_option( 'summaries_header_image', '' );
		}
	}
}

new UserFeedback_Email_Summaries();
