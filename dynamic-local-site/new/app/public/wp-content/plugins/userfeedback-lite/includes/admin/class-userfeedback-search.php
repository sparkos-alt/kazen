<?php

/**
 * Results Controller class.
 *
 * Handles API calls related to Search Posts and Pages Results
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author  David Paternina
 */
class UserFeedback_Search {

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
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_search_results' ),
				'permission_callback' => array( $this, 'view_search_results_permission_check' ),
			)
		);
	}

	/**
	 * Permissions/capabilities check
	 *
	 * @return bool
	 */
	public function view_search_results_permission_check() {
		return true;
	}

	/**
	 * Get Survey results data
	 *
	 * @param $survey_id
	 * @return mixed|null
	 */
	public static function get_search_results( $request ) {
		global $wpdb;
		// params
		$post_title  = $request->get_param( 'title' );
		$post_types  = array( 'post', 'page' );
		$search_text = '%' . urldecode( $post_title ) . '%';

		// get all the post ids with a title that matches our parameter
		$id_results = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title LIKE %s", $search_text ) );
		if ( empty( $id_results ) ) {
			return rest_ensure_response( $request );
		}

		// format the ids into an array
		$post_ids = array();
		foreach ( $id_results as $id ) {
			$post_ids[] = $id->ID;
		}

		// grab all the post objects
		$args  = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'post__in'       => $post_ids,
		);
		$posts = get_posts( $args );
		$data  = array();
		foreach ( $posts as $post ) {
			$data[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title,
			);
		}

		// Return all of our post response data
		return rest_ensure_response( $data );
	}
}

new UserFeedback_Search();
