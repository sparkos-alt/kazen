<?php

/**
 * Surveys Controller class.
 *
 * Handles API calls related to Surveys
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author  David Paternina
 */
class UserFeedback_Surveys {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'in_admin_footer', [ $this, 'promote_userfeedback' ] );
	}

	/**
	 * Registers REST routes
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			'userfeedback/v1',
			'/surveys',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_surveys' ),
				'permission_callback' => array( $this, 'create_edit_surveys_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/surveys/(?P<id>\w+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_survey' ),
				'permission_callback' => array( $this, 'create_edit_surveys_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/surveys',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_survey' ),
				'permission_callback' => array( $this, 'create_edit_surveys_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/surveys/restore',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'restore_surveys' ),
				'permission_callback' => array( $this, 'delete_surveys_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/surveys/draft',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'draft_surveys' ),
				'permission_callback' => array( $this, 'create_edit_surveys_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/surveys/trash',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'trash_surveys' ),
				'permission_callback' => array( $this, 'delete_surveys_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/surveys/publish',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'publish_surveys' ),
				'permission_callback' => array( $this, 'create_edit_surveys_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/surveys',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_surveys' ),
				'permission_callback' => array( $this, 'delete_surveys_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/surveys/(?P<id>\w+)/duplicate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'duplicate_survey' ),
				'permission_callback' => array( $this, 'create_edit_surveys_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/survey-templates',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_available_survey_templates' ),
				'permission_callback' => array( $this, 'create_edit_surveys_permission_check' ),
			)
		);
	}

	/**
	 * Permissions Check
	 *
	 * @return bool
	 */
	public function create_edit_surveys_permission_check() {
		return current_user_can( 'userfeedback_create_edit_surveys' );
	}

	/**
	 * Permissions Check
	 *
	 * @return bool
	 */
	public function delete_surveys_permission_check() {
		return current_user_can( 'userfeedback_delete_surveys' );
	}

	/**
	 * Get Surveys
	 *
	 * @return WP_REST_Response
	 */
	public function get_surveys( WP_REST_Request $request ) {
		$has_pagination = false;

		$query = UserFeedback_Survey::where(
			array(
				array( 'status', '!=', 'trash' ), // Get only published and drafts by default
			)
		)->with_count( array( 'responses' ) );

		if ( $request->has_param( 'filter' ) ) {
			$filters = $request->get_param( 'filter' );

			foreach ( $filters as $attr => $value ) {
				if ( $value === 'all' ) {
					break;
				}

				if ( $attr === 'status' && $value === 'publish' ) {
					$query->add_where(
						array(
							'status'     => 'publish',
							'publish_at' => null,
						),
						true
					)->or_where(
						array(
							'status' => 'publish',
							array( 'publish_at', '<=', current_time( 'mysql', true ) ),
						)
					);
				} elseif ( $attr === 'status' && $value === 'scheduled' ) {
					$query->add_where(
						array(
							'status' => 'publish',
							array( 'publish_at', 'is not', null ),
							array( 'publish_at', '>', current_time( 'mysql', true ) ),
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
		}

		if ( $request->has_param( 'orderby' ) ) {
			$field = $request->get_param( 'orderby' );
			$order = $request->get_param( 'order' );
			$order = $order ?: 'desc';

			$query->sort( $field, $order );
		} else {
			$query->sort( 'id', 'desc' );
		}

		if ( $request->has_param( 'select' ) ) {
			$query->select( $request->get_param( 'select' ) );
		}

        if ( $request->has_param( 'page' ) ) {
            $has_pagination = true;
            $query->paginate(
				$request->has_param( 'per_page' ) ? $request->get_param('per_page') : 10,
                $request->get_param( 'page' )
            );
        }

		$surveys = $query->get();

		if ( ! $has_pagination ) {
			return new WP_REST_Response( $surveys );
		}

		// Data for quick filters
		$count_by_status_result = UserFeedback_Survey::query()
			->select( array( 'status', 'count' ) )
			->select_raw( 'if (publish_at is null or publish_at <= now(), false, true) as scheduled' )
			->group_by( 'status, scheduled' )
			->get();

		$allTotal = 0;

		foreach ( $count_by_status_result as $item ) {
			if ( $item->status !== 'trash' ) {
				$allTotal += $item->count;
			}

			if ( $item->status === 'publish' && $item->scheduled ) {
				$item->status = 'scheduled';
			}

			unset( $item->scheduled );
		}

		array_unshift(
			$count_by_status_result,
			array(
				'status' => 'all',
				'count'  => $allTotal,
			)
		);

		$surveys['status_filters'] = $count_by_status_result;

		return new WP_REST_Response( $surveys );
	}

	/**
	 * Get a single survey by id
	 *
	 * @return WP_REST_Response
	 */
	public function get_survey( WP_REST_Request $request ) {

		$survey_id = $request->get_param( 'id' );

		$survey = UserFeedback_Survey::find( $survey_id );

		if ( ! $survey ) {
			return new WP_REST_Response( null, 404 );
		}

		if ( $request->has_param( 'with' ) ) {
			$query_instance = UserFeedback_Survey::query();
			$query_instance->with( explode( ',', $request->get_param( 'with' ) ) );
			$survey = $query_instance->populate_relations( $survey );
		}

		return new WP_REST_Response(
			$survey
		);
	}

	/**
	 * Saves or updates a Survey
	 *
	 * @return WP_REST_Response
	 */
	public function save_survey( WP_REST_Request $request ) {

		// Check if params include id
		$survey_id = $request['id'];

		if ( userfeedback_is_tracking_allowed() && isset( $request['template'] ) ) {
			$tracked_data = get_option( 'userfeedback_tracking_data', array() );

			if ( isset( $tracked_data['templates'] ) ) {
				$tracked_data['templates'][] = $request['template'];
			} else {
				$tracked_data['templates'] = array(
					$request['template'],
				);
			}

			update_option( 'userfeedback_tracking_data', $tracked_data );
		}

		$params = $request->get_params();
		$survey_count = UserFeedback_Survey::count();

		if(isset($params['title']) && 'First Survey' === $params['title'] && $survey_count > 0) {
			$survey = UserFeedback_Survey::get_by( 'title', 'First Survey' );
			return new WP_REST_Response( $survey );
		}

		if ( isset( $survey_id ) && $survey_id != 'null' ) {
			UserFeedback_Survey::update( $survey_id, $params );
			$survey = UserFeedback_Survey::find( $survey_id );
		} else {
			$number_of_surveys = UserFeedback_Survey::count();
			$new_survey_title  =
				empty( $params['title'] ) ?
					sprintf( __( 'Survey #%d', 'userfeedback' ), $number_of_surveys + 1 ) : $params['title'];

			$new_id = UserFeedback_Survey::create(
				array_merge(
					$params,
					array( 'title' => $new_survey_title )
				)
			);
			$survey = UserFeedback_Survey::find( $new_id );
		}

		return new WP_REST_Response( $survey );
	}

	/**
	 * Duplicate survey with the given id
	 *
	 * @return WP_REST_Response
	 */
	public function duplicate_survey( $data ) {

		$survey_id = $data['id'];
		$survey    = UserFeedback_Survey::find( $survey_id );

		// Save as new survey, return new one's id
		unset( $survey->id );
		$new_survey_id = UserFeedback_Survey::create(
			array_merge(
				(array) $survey,
				array(
					'title'  => sprintf( __( 'Copy of %s', 'userfeedback' ), $survey->title ),
					'status' => 'draft',
				)
			)
		);

		$new_survey = UserFeedback_Survey::find( $new_survey_id );

		return new WP_REST_Response(
			$new_survey
		);
	}

	/**
	 * Restore surveys by Id
	 *
	 * @return WP_REST_Response
	 */
	public function restore_surveys( $data ) {
		$survey_ids = $data['survey_ids'];
		UserFeedback_Survey::restore( $survey_ids );
		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Draft surveys by Id
	 *
	 * @return WP_REST_Response
	 */
	public function draft_surveys( $data ) {
		$survey_ids = $data['survey_ids'];
		$temp       = UserFeedback_Survey::draft( $survey_ids );
		return new WP_REST_Response( $temp, 200 );
	}

	/**
	 * Publish surveys by Id
	 *
	 * @return WP_REST_Response
	 */
	public function publish_surveys( $data ) {
		$survey_ids = $data['survey_ids'];
		$temp       = UserFeedback_Survey::publish( $survey_ids );
		return new WP_REST_Response( $temp, 200 );
	}

	/**
	 * Trash surveys by Id
	 *
	 * @return WP_REST_Response
	 */
	public function trash_surveys( $data ) {
		$survey_ids = $data['survey_ids'];
		UserFeedback_Survey::trash( $survey_ids );
		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Delete surveys by Id
	 *
	 * @return WP_REST_Response
	 */
	public function delete_surveys( $data ) {
		$survey_ids = $data['survey_ids'];
		UserFeedback_Survey::delete( $survey_ids );
		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Get available Survey templates
	 *
	 * @return WP_REST_Response
	 */
	public function get_available_survey_templates() {
		$templates = UserFeedback_Survey_Templates::get_instance()->get_available_templates();
		return new WP_REST_Response( $templates );
	}

	/**
	 * Pre-footer promotion block, displayed on all WPForms admin pages except Form Builder.
	 */
	public function promote_userfeedback() {
		$is_uf_page = userfeedback_screen_is_userfeedback();
		
		if( !$is_uf_page ){
			return;
		}

		$is_pro = userfeedback_is_pro_version();

		$links = [
			[
				'text' => __( 'Support', 'userfeedback' ),
				'link' => $is_pro ? userfeedback_get_url('footer_link', 'made-with-love', 'https://www.userfeedback.com/contact/') : 'https://wordpress.org/support/plugin/userfeedback-lite/',
				'target' => '_blank',
			],
			[
				'text' => __( 'Docs', 'userfeedback' ),
				'link' => userfeedback_get_url('footer_link', 'made-with-love', 'https://www.userfeedback.com/docs/'),
				'target' => '_blank',
			],
			[
				'text' => __( 'Free Plugins', 'userfeedback' ),
				'link' => admin_url('admin.php?page=userfeedback_settings#/about'),
				'target' => '_self',
			],
			[
				'text' => __( 'Suggest a Feature', 'userfeedback' ),
				'link' => userfeedback_get_url('footer_link', 'made-with-love', 'https://www.userfeedback.com/suggest-feature/'),
				'target' => '_blank',
			],
		];
		if(!empty($links)){
			echo '<div class="userfeedback-love">';
			echo esc_html__('Made with ♥ by the UserFeedback Team', 'userfeedback');
			$links_output = [];
			foreach($links as $link){
				$links_output[] = '<a target="'.esc_attr($link['target']).'" href="'.esc_url($link['link']).'">' . esc_html($link['text']) . '</a>';
			}
			echo '<div>'. implode('<span class="sep">/</span>', $links_output) .'</div>';
			echo '</div>';
		}
		
	}

}

new UserFeedback_Surveys();
