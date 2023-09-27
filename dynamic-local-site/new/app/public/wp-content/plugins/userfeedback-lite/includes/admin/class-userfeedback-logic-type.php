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
class UserFeedback_Logic_Type
{

	public function __construct()
	{
		add_action('rest_api_init', array($this, 'register_routes'));
	}

	/**
	 * Registers REST routes
	 *
	 * @return void
	 */
	public function register_routes()
	{
		register_rest_route(
			'userfeedback/v1',
			'/logic-type',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'get_logic_type_results'),
				'permission_callback' => array($this, 'view_logic_type_results_permission_check'),
			)
		);
	}

	/**
	 * Permissions/capabilities check
	 *
	 * @return bool
	 */
	public function view_logic_type_results_permission_check()
	{
		return true;
	}

	/**
	 * Get Survey results data
	 *
	 * @param $survey_id
	 * @return mixed|null
	 */
	public function get_logic_type_results($request)
	{
		global $wpdb;
		// params
		$logic_type  = $request->get_param('logic_type');

		switch ($logic_type) {
			case 'user_logged_in':
				$data = $this->userLoggedInOptions($request);
				break;

			case 'wp_page_type':
				$data = $this->wpPageTypeOptions($request);
				break;

			case 'post_type':
				$data = $this->postTypeOptions($request);
				break;
			case 'taxonomy':
				$data = $this->taxonomyOptions($request);
				break;
			case 'taxonomy_term':
				$data = $this->taxonomyTermsOptions($request);
				break;

			default:
				$data = [];
				break;
		}

		// Return all of our post response data
		return rest_ensure_response($data);
	}

	protected function userLoggedInOptions($request)
	{
		$search = $request->get_param('search');
		$data = array(
			array(
				'id' => 'user_logged_in',
				'label' => __('Logged-in', 'userfeedback'),
			),
			array(
				'id' => 'user_logged_out',
				'label' => __('Logged-out', 'userfeedback'),
			),
		);

		if ($search) {
			$data = array_values(array_filter(array_map(function ($item) use ($search) {
				if (strpos(strtolower($item['label']), strtolower($search)) !==  false) {
					return $item;
				}
			}, $data)));
		}

		return $data;
	}

	protected function wpPageTypeOptions($request)
	{
		$search = $request->get_param('search');
		$data = array(
			array(
				'id' => 'is_front_page',
				'label' => __('Homepage', 'userfeedback'),
			),
			array(
				'id' => 'is_archive',
				'label' => __('Archive', 'userfeedback'),
			),
			array(
				'id' => 'is_single',
				'label' => __('Single post/page', 'userfeedback'),
			),
			array(
				'id' => 'is_search',
				'label' => __('Search page', 'userfeedback'),
			),
			array(
				'id' => 'is_404',
				'label' => __('404 page', 'userfeedback'),
			),
			array(
				'id' => 'is_author',
				'label' => __('Author page', 'userfeedback'),
			),
		);

		if ($search) {
			$data = array_values(array_filter(array_map(function ($item) use ($search) {
				if (strpos(strtolower($item['label']), strtolower($search)) !==  false) {
					return $item;
				}
			}, $data)));
		}

		return $data;
	}

	protected function postTypeOptions($request)
	{
		$search = $request->get_param('search');
		$post_types = get_post_types(array('public' => true), 'objects');
		$data    = array();
		foreach ($post_types as $post_type) {
			$data[] = array(
				'label' => $post_type->label,
				'id' => $post_type->name,
			);
		}

		if ($search) {
			$data = array_values(array_filter(array_map(function ($item) use ($search) {
				if (strpos(strtolower($item['label']), strtolower($search)) !==  false) {
					return $item;
				}
			}, $data)));
		}

		return $data;
	}

	protected function taxonomyOptions($request)
	{
		$search = $request->get_param('search');
		$taxonomies = get_taxonomies(
			array(
				'public' => true,
			),
			'objects'
		);
		$data    = array();
		foreach ($taxonomies as $taxonomy) {
			if ('post_format' === $taxonomy->name) {
				continue;
			}
			$data[] = array(
				// Translators: this is the name of the taxonomy.
				'label' => $taxonomy->labels->singular_name,
				'id' => $taxonomy->name,
			);
		}

		if ($search) {
			$data = array_values(array_filter(array_map(function ($item) use ($search) {
				if (strpos(strtolower($item['label']), strtolower($search)) !==  false) {
					return $item;
				}
			}, $data)));
		}

		return $data;
	}

	protected function taxonomyTermsOptions($request)
	{
		$search = $request->get_param('search');
		$public_taxonomies = get_taxonomies(
			array(
				'public' => true,
			)
		);


		$terms = get_terms(
			array(
				// 'search'     => $search,
				'taxonomy'   => $public_taxonomies,
				'hide_empty' => false,
			)
		);

		$data = array();

		foreach ($terms as $term) {
			$data[] = array(
				'label' => $term->name,
				'id'   => $term->term_id,
			);
		}

		if ($search) {
			$data = array_values(array_filter(array_map(function ($item) use ($search) {
				if (strpos(strtolower($item['label']), strtolower($search)) !==  false) {
					return $item;
				}
			}, $data)));
		}

		return $data;
	}
}

new UserFeedback_Logic_Type();
