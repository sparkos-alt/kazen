<?php

/**
 * Email Response Notifications main class.
 *
 * @since 1.0.0
 */
class UserFeedback_Email_Response_Notification {

	/**
	 * Email template to use for this class.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $email_template = 'response-notification';

	/**
	 * Test email template
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $test_email_template = 'response-notification';

	/**
	 * Email options
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $email_options = array();

	/**
	 * The survey this notification is for
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	private $survey;

	/**
	 * The response this notification is for
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	private $response;

	/**
	 * Notification logic config
	 *
	 * @since 1.0.0
	 *
	 * @var
	 */
	private $config;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $survey, $response ) {

		$this->survey   = $survey;
		$this->response = $response;
		$this->config   = $survey->notifications->email;
	}

	private function set_options() {
		$options                    = array();
		$options['email_addresses'] = $this->config->addresses;
		$options['html_template']   = userfeedback_get_option( 'summaries_html_template' );
		$options['header_image']    = userfeedback_get_option( 'notifications_header_image' );

		$this->email_options = $options;
	}

	/**
	 * Send email
	 *
	 * @return void
	 */
	private function send() {
		$email            = array();
		$email['subject'] = $this->get_email_subject();
		$email['address'] = $this->get_email_addresses();
		$email['address'] = array_map( 'sanitize_email', $email['address'] );

		// Create new email.
		$emails = new Userfeedback_WP_Emails( $this->email_template );
		$emails->set_initial_args( $this->get_template_args() );

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
	}

	/**
	 * Check if email should be sent
	 *
	 * @return void
	 */
	public function maybe_send() {
		// if ( !$this->config->active || empty( $this->config->addresses ) ) {
		// If notifications aren't active, or no emails are set, bail...
		// return;
		// }

		$logic = $this->config->logic;

		if ( $logic->enable && sizeof( $logic->conditions ) > 0 ) {
			$send = true;

			foreach ( $logic->conditions as $condition ) {
				$question_id = $condition->question_id;

				$answer = $this->get_question_answer( $question_id );

				if ( empty( $answer ) ) {
					break;
				}

				$symbol          = $condition->compare;
				$submitted_value = $answer->value;
				$compare_to      = $condition->value;

				$send = $send && userfeedback_check_logic( $symbol, $submitted_value, $compare_to );
			}

			if ( ! $send ) {
				return;
			}
		}

		$this->set_options();
		$this->send();
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

		if ( userfeedback_is_pro_version() && userfeedback_is_licensed() && ! empty( $this->email_options['header_image'] ) ) {
			$img['url'] = $this->email_options['header_image'];
			$img['2x']  = '';
		}

		return apply_filters( 'userfeedback_email_header_image', $img );
	}

	/**
	 * Get email subject
	 *
	 * @since 1.0.0
	 */
	public function get_email_subject() {

		$site_url        = get_site_url();
		$site_url_parsed = parse_url( $site_url );

		// Translators: The domain of the site is appended to the subject.
		$subject = sprintf( __( 'New UserFeedback Response - %s', 'userfeedback' ), $this->survey->title );

		return apply_filters( 'userfeedback_emails_new_response_subject', $subject, $this->survey, $this->response );
	}

	/**
	 * Get email addresses to send
	 *
	 * @since 1.0.0
	 */
	public function get_email_addresses() {
		$emails          = array();
		$email_addresses = $this->config->active ? explode( ',', $this->config->addresses ) : array();

		if ( ! empty( $email_addresses ) ) {
			foreach ( $email_addresses as $email_address ) {
				if ( ! empty( $email_address ) && is_email( $email_address ) ) {
					$emails[] = $email_address;
				}
			}
		}

		return apply_filters( 'userfeedback_email_notification_addresses', $emails, $this->survey, $this->response );
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
		if ( false === $this->email_options['html_template'] ) {
			$value = false;
		}
		return apply_filters( 'userfeedback_email_html_template', $value, $this );
	}

	/**
	 * Get email summaries template arguments
	 *
	 * @since 1.0.0
	 */
	private function get_template_args() {

		$args['preview_title'] = $this->get_email_subject();
		$args['header_image']  = $this->get_header_image();
		$args['survey_id']     = $this->survey->id;
		$args['survey_title']  = $this->survey->title;
		$args['title']         = sprintf(
			esc_html__( 'New Response to <b>%s</b>', 'userfeedback' ),
			$this->survey->title
		);

		$survey_id               = $this->survey->id;
		$notification_config_url = userfeedback_get_screen_url( 'userfeedback_surveys', "edit/$survey_id/notifications" );

		$args['description'] =
			sprintf(
				esc_html__( 'You are receiving this UserFeedback survey notification from <b>%1$s</b>. <a href="%2$s">Adjust your settings here</a>.', 'userfeedback' ),
				get_bloginfo( 'name' ),
				$notification_config_url
			);

		$args['answers']          = $this->get_answers();
		$args['settings_tab_url'] = $notification_config_url;

		return apply_filters( 'userfeedback_email_notification_template_args', $args );
	}

	/**
	 * Get response answers
	 *
	 * @return array|array[]
	 */
	private function get_answers() {
		$questions = $this->survey->questions;

		$answers = array_map(
			function( $question ) {
				return array(
					'question_id'    => $question->id,
					'question_title' => $question->title,
					'type'           => $question->type,
					'value'          => $this->get_question_answer_html( $question ),
				);
			},
			$questions
		);

		return $answers;
	}

	private function get_question_answer( $question_id ) {
		$answers = $this->response->answers;

		// Find answer...
		foreach ( $answers as $answer ) {
			if ( $answer->question_id === $question_id ) {
				return $answer;
			}
		}

		return null;
	}

	/**
	 * Get processed question answer
	 *
	 * @param $question
	 * @return string
	 */
	private function get_question_answer_html( $question ) {
		$answers = $this->response->answers;

		$found_answer = $this->get_question_answer( $question->id );

		$skipped_content = sprintf(
			__( '%1$sSkipped%2$s', 'userfeedback' ),
			'<small><i>',
			'</i></small>'
		);

		if ( empty( $found_answer ) ) {
			return $skipped_content;
		}

		$raw_value = $found_answer->value;

		// If answer is null, the question was skipped...
		if ( empty( $raw_value ) ) {
			return $skipped_content;
		};
		$value = $raw_value;

		if ( $question->type === 'email' ) {
			$value = sprintf(
				'<a href="mailto:%1$s">%1$s</a>',
				$raw_value
			);
		} elseif ( $question->type === 'nps' ) {
			$value = $value . '<small>/10</small>';
		} elseif ( $question->type === 'checkbox' ) {
			$value = implode( ', ', $value );
		} elseif ( $question->type === 'star-rating' ) {
			$value = sprintf( __( '%s stars', 'userfeedback' ), $value );
		}

		if ( ! empty( $found_answer->extra ) ) {

			foreach ( $found_answer->extra as $attr => $extra_value ) {
				$value .= sprintf(
					'<br/><p>- %s: <i>%s</i></p>',
					$attr,
					$extra_value
				);
			}
		}

		return $value;
	}
}

// Hook onto response action
function userfeedback_send_response_email_notification( $survey_id, $response_id ) {
	$survey   = UserFeedback_Survey::find( $survey_id );
	$response = UserFeedback_Response::find( $response_id );

	$notification_email = new UserFeedback_Email_Response_Notification( $survey, $response );
	$notification_email->maybe_send();
}
add_action( 'userfeedback_survey_response', 'userfeedback_send_response_email_notification', 10, 2 );
