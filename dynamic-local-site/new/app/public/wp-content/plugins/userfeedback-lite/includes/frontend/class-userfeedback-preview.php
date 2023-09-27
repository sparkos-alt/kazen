<?php

class UserFeedback_Preview {

	static $USERFEEDBACK_PREVIEW_ELEMENT_PREFIX = 'userfeedback-preview';
	static $USERFEEDBACK_PREVIEW_PARAM          = 'userfeedback-preview';

	public $title          = '';
	public $body           = '';
	private $virtual_pages = array();  // the main array of virtual pages

	function __construct() {
		// Virtual pages are checked in the 'parse_request' filter.
		// This action starts everything off if we are a virtual page
		add_action( 'parse_request', array( $this, 'parse_request' ) );
	}

	function add( $virtual_regexp, $content_function ) {
		$this->virtual_pages[ $virtual_regexp ] = $content_function;
	}

	// Check page requests for Virtual pages
	// If we have one, call the appropriate content generation function
	//
	function parse_request( &$wp ) {

		if( empty( $_GET ) ){
			return false;
		}

		$page            = esc_url_raw(
			filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL )
		);
		$get_params_keys = array();
		if ( ! empty( $_GET ) && ! empty( array_keys( $_GET ) ) ) {
			$get_params_keys = array_filter( filter_input_array( INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
		}

		$matched = false;
		foreach ( $this->virtual_pages as $param_name => $func ) {
			if ( in_array( $param_name, $get_params_keys ) ) {
				$matched = true;
				break;
			}
		}
		// Do nothing if not matched
		if ( ! $matched ) {
			return;
		}

		// setup hooks and filters to generate virtual movie page
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		add_filter( 'the_posts', array( $this, 'create_dummy_post' ) );
		add_filter(
			'edit_post_link',
			function ( $link, $post_id ) {
				if ( $post_id === -1 ) {
					return false;
				}
				return $link;
			},
			10,
			2
		);

		// Call user content generation function
		// Called last so it can remove any filters it doesn't like
		// It should set:
		// $this->body   -- body of the virtual page
		// $this->title  -- title of the virtual page
		// $this->template  -- optional theme-provided template
		// eg: page
		// $this->subtemplate -- optional subtemplate (eg movie)
		// Doco is unclear whether call by reference works for call_user_func()
		// so using call_user_func_array() instead, where it's mentioned.
		// See end of file for example code.
		$this->template    = null;
		$this->subtemplate = null;
		$this->title       = null;
		unset( $this->body );
		call_user_func_array( $func, array( $this, $page ) );

		if ( ! isset( $this->body ) ) {
			wp_die( 'Virtual Themed Pages: must have ->body' );
		}

		return ( $wp );
	}


	// Set up a dummy post/page
	// From the WP view, a post == a page
	//
	function create_dummy_post( $posts ) {
		// have to create a dummy post as otherwise many templates
		// don't call the_content filter
		global $wp, $wp_query;

		// create a fake post intance
		$p = new stdClass();
		// fill $p with everything a page in the database would have
		$p->ID                    = -1;
		$p->post_author           = 1;
		$p->post_date             = current_time( 'mysql' );
		$p->post_date_gmt         = current_time( 'mysql', $gmt = 1 );
		$p->post_content          = $this->body;
		$p->post_title            = $this->title;
		$p->post_excerpt          = '';
		$p->post_status           = 'publish';
		$p->ping_status           = 'closed';
		$p->post_password         = '';
		$p->post_name             = 'user-feedback-survey'; // slug
		$p->to_ping               = '';
		$p->pinged                = '';
		$p->modified              = $p->post_date;
		$p->modified_gmt          = $p->post_date_gmt;
		$p->post_content_filtered = '';
		$p->post_parent           = 0;
		$p->guid                  = get_home_url( '/' . $p->post_name ); // use url instead?
		$p->menu_order            = 0;
		$p->post_type             = 'page';
		$p->post_mime_type        = '';
		$p->comment_status        = 'closed';
		$p->comment_count         = 0;
		$p->filter                = 'raw';
		$p->ancestors             = array(); // 3.6

		// reset wp_query properties to simulate a found page
		$wp_query->is_page     = true;
		$wp_query->is_singular = true;
		$wp_query->is_preview  = true;
		$wp_query->is_home     = false;
		$wp_query->is_archive  = false;
		$wp_query->is_category = false;
		unset( $wp_query->query['error'] );
		$wp->query                     = array();
		$wp_query->query_vars['error'] = '';
		$wp_query->is_404              = false;

		$wp_query->current_post  = $p->ID;
		$wp_query->found_posts   = 1;
		$wp_query->post_count    = 1;
		$wp_query->comment_count = 0;
		// -1 for current_comment displays comment if not logged in!
		$wp_query->current_comment = null;
		$wp_query->is_singular     = 1;

		$wp_query->post              = $p;
		$wp_query->posts             = array( $p );
		$wp_query->queried_object    = $p;
		$wp_query->queried_object_id = $p->ID;
		$wp_query->current_post      = $p->ID;
		$wp_query->post_count        = 1;

		return array( $p );
	}


	// Virtual Movie page - tell WordPress we are using the given
	// template if it exists; otherwise we fall back to page.php.
	//
	// This func gets called before any output to browser
	// and exits at completion.
	//
	function template_redirect() {
		// $this->body   -- body of the virtual page
		// $this->title  -- title of the virtual page
		// $this->template  -- optional theme-provided template eg: 'page'
		// $this->subtemplate -- optional subtemplate (eg movie)
		//

		if ( ! empty( $this->template ) && ! empty( $this->subtemplate ) ) {
			// looks for in child first, then master:
			// template-subtemplate.php, template.php
			get_template_part( $this->template, $this->subtemplate );
		} elseif ( ! empty( $this->template ) ) {
			// looks for in child, then master:
			// template.php
			get_template_part( $this->template );
		} elseif ( ! empty( $this->subtemplate ) ) {
			// looks for in child, then master:
			// template.php
			get_template_part( $this->subtemplate );
		} else {
			get_template_part( 'page' );
		}
	}
}
