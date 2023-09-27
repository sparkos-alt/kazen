<?php

use WP_Rocket\Engine\License\API\User;

/**
 * Results Controller class.
 *
 * Handles API calls related to Survey Results
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author  David Paternina
 */
class UserFeedback_Results {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers REST routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'userfeedback/v1',
			'/results-summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_results_summary' ),
				'permission_callback' => array( $this, 'view_results_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/surveys/(?P<id>\w+)/results',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_survey_results' ),
				'permission_callback' => array( $this, 'view_results_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/surveys/(?P<id>\w+)/responses',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_survey_responses' ),
				'permission_callback' => array( $this, 'view_results_permission_check' ),
			)
		);
		
		register_rest_route(
			'userfeedback/v1',
			'/surveys/(?P<id>\w+)/responses/trash',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'trash_responses' ),
				'permission_callback' => array( $this, 'view_results_permission_check' ),
				'args'				  => array(
					'response_ids' => array(
						'required'          => true,
						'type'              => 'array',
						'description'       => __('Survey response ids.', 'userfeedback'),
						'sanitize_callback' => function($ids) {
							return array_map('esc_attr', $ids);
						},
						'validate_callback' => array( $this, 'validate_response_ids' )
					)
				)
			)
		);
		
		register_rest_route(
			'userfeedback/v1',
			'/surveys/(?P<id>\w+)/responses/restore',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'restore_responses' ),
				'permission_callback' => array( $this, 'view_results_permission_check' ),
				'args'				  => array(
					'response_ids' => array(
						'required'          => true,
						'type'              => 'array',
						'description'       => __('Survey response ids.', 'userfeedback'),
						'sanitize_callback' => function($ids) {
							return array_map('esc_attr', $ids);
						},
						'validate_callback' => array( $this, 'validate_response_ids' )
					)
				)
			)
		);
		
		register_rest_route(
			'userfeedback/v1',
			'/surveys/(?P<id>\w+)/responses',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_responses' ),
				'permission_callback' => array( $this, 'view_results_permission_check' ),
				'args'				  => array(
					'response_ids' => array(
						'required'          => true,
						'type'              => 'array',
						'description'       => __('Survey response ids.', 'userfeedback'),
						'sanitize_callback' => function($ids) {
							return array_map('esc_attr', $ids);
						},
						'validate_callback' => array( $this, 'validate_response_ids' )
					)
				)
			)
		);
	}

	/**
	 * Get Survey results data
	 *
	 * @param $survey_id
	 * @return mixed|null
	 */
	public static function get_survey_results_data( $survey_id ) {

		$start_date_7_days  = ( new DateTime() )->modify( '-7 days' );
		$start_date_30_days = ( new DateTime() )->modify( '-30 days' );
		$end_date           = new DateTime();

		$survey = UserFeedback_Survey::where(
			array(
				'id' => $survey_id,
			)
		)->select( array( 'title', 'status', 'impressions', 'questions' ) )
			->with_count_where(
				'responses',
				array(
					array(
						'submitted_at',
						'>=',
						$start_date_7_days->format( 'Y-m-d' ),
					),
					array(
						'submitted_at',
						'<=',
						$end_date->format( 'Y-m-d' ),
					),
				),
				'responses_count_7_days'
			)
			->with_count_where(
				'responses',
				array(
					array(
						'submitted_at',
						'>=',
						$start_date_30_days->format( 'Y-m-d' ),
					),
					array(
						'submitted_at',
						'<=',
						$end_date->format( 'Y-m-d' ),
					),
				),
				'responses_count_30_days'
			)
			->with( array( 'responses' ) )
			->single();

		if ( $survey === null ) {
			return null;
		}

		// Survey total responses
		$total_responses         = sizeof( $survey->responses );
		$survey->responses_count = $total_responses;

		// Survey question stats
		$quantitative_question_types = array( 'radio-button', 'checkbox', 'nps', 'star-rating' );
		$question_stats              = array();

		$questions = $survey->questions;
		$responses = $survey->responses;

		foreach ( $questions as $question ) {
			$id   = $question->id;
			$type = $question->type;

			$is_quantitative = in_array( $type, $quantitative_question_types );

			$question_data = array(
				'id'              => $question->id,
				'title'           => $question->title,
				'type'            => $question->type,
				'total_answers'   => 0,
				'skipped'         => 0,
				'is_quantitative' => $is_quantitative,
			);

			if ( $is_quantitative ) {
				switch ( $type ) {
					case 'radio-button':
					case 'checkbox':
						$question_data['options'] = array_map(
							function ( $option ) {
								return array(
									'value' => $option,
									'count' => 0,
								);
							},
							$question->config->options
						);
						break;
					case 'nps':
						$question_data['options'] = array_map(
							function ( $option ) {
								return array(
									'value' => $option,
									'count' => 0,
								);
							},
							array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 )
						);
						break;
					case 'star-rating':
						$question_data['options'] = array_map(
							function ( $option ) {
								return array(
									'value' => $option,
									'count' => 0,
								);
							},
							array( 1, 2, 3, 4, 5 )
						);
						break;
				}
			} else {
				$question_data['answers'] = array();
			}

			foreach ( $responses as $response ) {
				$question_answer_index = array_search( $id, array_column( $response->answers, 'question_id' ) );
				$value                 = $response->answers[ $question_answer_index ]->value;
				$extra                 = isset( $response->answers[ $question_answer_index ]->extra ) ? $response->answers[ $question_answer_index ]->extra : null;

				if ( $question_answer_index === false || $value === null ) {
					$question_data['skipped']++;
					continue;
				} else {
					$question_data['total_answers']++;
				}

				if ( $is_quantitative ) {
					if ( is_array( $value ) ) {
						foreach ( $value as $picked_value ) {
							$option_index = array_search( $picked_value, array_column( $question_data['options'], 'value' ) );
							$question_data['options'][ $option_index ]['count']++;
						}
					} else {
						$option_index = array_search( $value, array_column( $question_data['options'], 'value' ) );
						$question_data['options'][ $option_index ]['count']++;
					}
				}

				$question_data['answers'][] = array(
					'response_id' => $response->id,
					'value'       => $value,
					'date'        => $response->submitted_at,
					'extra'       => $extra,
				);
			}

			$question_stats[] = $question_data;
		}

		$survey->question_stats = $question_stats;

		return $survey;
	}

	/**
	 * Permissions/capabilities check
	 *
	 * @return bool
	 */
	public function view_results_permission_check() {
		return current_user_can( 'userfeedback_view_results' );
	}
	
	
	/**
	 * Validate response ids callback
	 *
	 * @return bool
	 */
	public function validate_response_ids($ids) {
		global $wpdb;
		$table_name = (new UserFeedback_Response())->get_table();
		$placeholders = array_fill(0, count($ids), '%d');
		$query = $wpdb->prepare("SELECT * FROM $table_name WHERE id IN (" . implode(', ', $placeholders) . ")", $ids);
		$result = $wpdb->get_results($query);
		return count($result) === count($ids);
	}

	/**
	 * Get Results summary by date range
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 * @throws Exception
	 */
	public function get_results_summary( WP_REST_Request $request ) {
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );
		$survey_id  = $request->get_param( 'survey_id' );

		$start_date = $start_date ? new DateTime( $start_date ) : ( new DateTime() )->modify( '-7 days' )->setTime( 0, 0 );

		if ( ! userfeedback_is_pro_version() || ! userfeedback_is_licensed() ) {
			$start_date = ( new DateTime() )->modify( '-7 days' )->setTime( 0, 0 );
		}

		$end_date = $end_date ? new DateTime( $end_date ) : new DateTime();
		$end_date = $end_date->modify( '+1 day' );

		$where_config = array(
			array(
				'submitted_at',
				'>=',
				$start_date->format( 'Y-m-d' ),
			),
			array(
				'submitted_at',
				'<=',
				$end_date->format( 'Y-m-d' ),
			),
		);

		if ( isset( $survey_id ) ) {
			$where_config[] = array(
				'survey_id',
				'=',
				$survey_id,
			);
		}

		$responses_query_obj = UserFeedback_Response::query();
		/**
		 * Get responses
		 */
		$responses = UserFeedback_Response::where( $where_config )
			->select(
				array(
					"{$responses_query_obj->get_table()}.id",
					"{$responses_query_obj->get_table()}.survey_id",
					"{$responses_query_obj->get_table()}.submitted_at",
				)
			)
			->get();

		/*
		 * Get data for graph
		 */
		$data_points = array();

		$iterate_date = $start_date;
		while ( $iterate_date->diff( $end_date )->days > 0 ) {
			$responses_for_date = array_filter(
				$responses,
				function( $response ) use ( $iterate_date ) {
					$response_date = new DateTime( $response->submitted_at );
					return $iterate_date->format( 'Y-m-d' ) === $response_date->format( 'Y-m-d' );
				}
			);

			$data_points[] = array(
				'date'  => $iterate_date->format( 'Y-m-d' ),
				'count' => sizeof( $responses_for_date ),
			);

			$iterate_date->modify( '+1 day' );
		}

		/*
		 * Get Surveys info
		 */
		$surveys_query = UserFeedback_Survey::where(
			array(
				array( 'status', '!=', 'trash' ),
			)
		);

		$surveys_query->select( array( 'title', 'status', 'created_at' ) )
			->with_count( array( 'responses' ) )
			->with_count_where( 'responses', $where_config, 'range_responses_count' )
			->sort( 'id', 'desc' );

		return new WP_REST_Response(
			array(
				'total_responses' => sizeof( $responses ),
				'data_points'     => $data_points,
				'surveys'         => $surveys_query->get(),
			)
		);
	}

	/**
	 * Get Survey with responses
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_survey_results( WP_REST_Request $request ) {
		$survey_id = $request['id'];

		$survey = self::get_survey_results_data( $survey_id );

		if ( $survey === null ) {
			return new WP_REST_Response( null, 404 );
		}

		// Remove the original questions and responses arrays to get a cleaner AJAX response
		unset( $survey->questions );
		unset( $survey->responses );

		return new WP_REST_Response( $survey );
	}

	/**
	 * Get Survey responses
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_survey_responses( WP_REST_Request $request ) {

		(new UserFeedback_Response)->add_status_column();

		$survey_id = $request->get_param( 'id' );

		// Get responses
		$query = UserFeedback_Response::where(
			array(
				'survey_id' => $survey_id,
				array( 'status', '!=', 'trash' ), // Get only published and drafts by default
			)
		)
		->sort( 'id', 'desc' )
		->paginate(
			$request->get_param( 'per_page' ),
			$request->get_param( 'page' )
		);

		if ( $request->has_param( 'filter' ) ) {
			$filters = $request->get_param( 'filter' );
			foreach ($filters as $attr => $value) {
				if ($value === 'all') {
					$query->add_where(
						array(
							'status'     => 'publish'
						),
						true
					);
					break;
				}

				if ( $attr === 'status' && $value === 'publish' ) {
					$query->add_where(
						array(
							'status'     => 'publish'
						),
						true
					);
				} else {
					$query->add_where(
						array(
							$attr => $value,
						),
						true
					);
				}

			}
		} else {
			$query->add_where(
				array(
					'status'     => 'publish'
				),
				true
			);
		}

		$responses = $query->get();

		// Data for quick filters
		$count_by_status_result = UserFeedback_Response::query()
			->select( array( 'status', 'count' ) )
			->group_by( 'status' )
			->get();

		$allTotal = 0;

		foreach ( $count_by_status_result as $item ) {
			if ( $item->status !== 'trash' ) {
				$allTotal += $item->count;
			}

			if ( $item->status === 'publish' ) {
				$item->status = 'publish';
			}
		}

		array_unshift(
			$count_by_status_result,
			array(
				'status' => 'all',
				'count'  => $allTotal,
			)
		);

		$responses['status_filters'] = $count_by_status_result;

		return new WP_REST_Response( $responses );
	}
	
	
	/**
	 * Trash responses
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function trash_responses( WP_REST_Request $request ) {
		$response_ids = $request->get_param( 'response_ids' );
		// Trash responses
		$responses = UserFeedback_Response::trash($response_ids);
		return new WP_REST_Response( $responses );
	}

	/**
	 * Restore responses
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function restore_responses( WP_REST_Request $request ) {
		$response_ids = $request->get_param( 'response_ids' );
		// Restore responses
		$responses = UserFeedback_Response::restore($response_ids);
		return new WP_REST_Response( $responses );
	}
	
	/**
	 * Delete responses by Id
	 *
	 * @return WP_REST_Response
	 */
	public function delete_responses( $data ) {
		$response_ids = $data['response_ids'];
		// Delete responses
		UserFeedback_Response::delete($response_ids);
		return new WP_REST_Response( null, 204 );
	}
}

new UserFeedback_Results();
