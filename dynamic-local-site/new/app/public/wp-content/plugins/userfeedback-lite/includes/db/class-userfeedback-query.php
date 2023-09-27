<?php

/**
 * Query class.
 *
 * Small database query builder
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage DB
 * @author  David Paternina
 */
class UserFeedback_Query {

	/**
	 * Query Table
	 *
	 * @var $table
	 */
	private $table;

	/**
	 * Primary Key Column name
	 *
	 * @var string
	 */
	private $primary_key_column;

	/**
	 * Query Select values
	 *
	 * @var string[]
	 */
	private $selects = array( '*' );

	/**
	 * Query Where clause
	 *
	 * @var string
	 */
	private $where = '';

	/**
	 * Relation-based filter config
	 *
	 * @var array
	 */
	private $table_join = '';

	/**
	 * Query Order By attribute
	 *
	 * @var string
	 */
	private $orderby;

	/**
	 * Query Sort. ASC | DESC
	 *
	 * @var string
	 */
	private $order;

	/**
	 * Query limit. Used in pagination
	 *
	 * @var string|int
	 */
	private $limit;

	/**
	 * Original $per_page value
	 *
	 * @var int
	 */
	private $per_page;

	/**
	 * Query offset. Used in pagination
	 *
	 * @var string|int
	 */
	private $offset;

	/**
	 * Query Page, if paginated
	 *
	 * @var
	 */
	private $page;

	/**
	 * Whether query is paginated
	 *
	 * @var bool
	 */
	private $is_paginated = false;

	/**
	 * Group and count results by attribute
	 *
	 * @var string
	 */
	private $group_by;

	/**
	 * Create new Query for table
	 *
	 * @param $table
	 * @param $primary_key_column
	 */
	public function __construct( $table, $primary_key_column ) {
		$this->table              = $table;
		$this->primary_key_column = $primary_key_column;
	}

	/**
	 * Get Query select
	 *
	 * @return array|string
	 */
	private function get_select_query() {
		return esc_sql( implode( ',', $this->selects ) );
	}

	/**
	 * Get Sort SQl
	 *
	 * @return string
	 */
	private function get_sort_sql() {
		$orderby = $this->orderby ?: $this->primary_key_column;
		$order   = $this->order ?: 'ASC';
		return "ORDER BY {$this->table}.{$orderby} {$order}";
	}

	/**
	 * Get Group By SQL
	 *
	 * @return string
	 */
	private function get_grouping_sql() {
		if ( empty( $this->group_by ) ) {
			return '';
		}

		return "GROUP BY {$this->group_by}";
	}

	/**
	 * Get pagination SQL
	 *
	 * @return string
	 */
	private function get_pagination_sql() {
		if ( $this->limit ) {
			$offset = $this->offset ?: 0;
			return "LIMIT {$this->limit} OFFSET {$offset}";
		}

		return '';
	}

	/**
	 * Get complete sql
	 *
	 * @return string
	 */
	public function get_sql() {
		return "
            SELECT {$this->get_select_query()}
            FROM $this->table
            {$this->table_join}
            {$this->where}
            {$this->get_grouping_sql()}
            {$this->get_sort_sql()} 
			{$this->get_pagination_sql()}
        ";
	}

	/**
	 * Update Query select
	 *
	 * @param $select
	 * @return UserFeedback_Query
	 */
	public function select( $select = array( '*' ) ) {
		$this->selects = $select;
		return $this;
	}

	/**
	 * Add raw select
	 *
	 * @param $select
	 * @return void
	 */
	public function select_raw( $select ) {
		$this->selects[] = $select;
		return $this;
	}

	/**
	 * Update Query Where clause
	 *
	 * @param array $args
	 * @return UserFeedback_Query
	 */
	public function where( $args = array(), $or = false, $override = false ) {
		global $wpdb;

		$where_parts = array();
		$where       = $override ? '' : $this->where;

		$allowed_symbols = array( '<', '>', '<=', '>=', '!=', '=', 'is', 'is not' );

		$values = array();

		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) ) {

				if ( sizeof( $value ) < 3 ) {
					continue;
				}

				$key          = $value[0];
				$operator     = $value[1];
				$target_value = $value[2];

				if ( ! in_array( $operator, $allowed_symbols ) ) {
					continue;
				}

				if ( $target_value === null ) {
					$where_parts[] = "{$key} {$operator} null";
				} else {
					$where_parts[] = "{$key} {$operator} %s";
					$values[]      = $target_value;
				}
			} else {
				if ( $value !== null ) {
					$values[]      = $value;
					$where_parts[] = "{$key} = '%s'";
				} else {
					$where_parts[] = "{$key} is null";
				}
			}
		}

		if ( empty( $where ) ) {
			$where = 'WHERE ';
		} elseif ( $or ) {
			$where .= ' OR ';
		} else {
			$where .= ' AND ';
		}

		$where .= '(' . implode( ' AND ', $where_parts ) . ')';

		if ( ! empty( $where ) ) {
			$this->where = $wpdb->prepare( $where, $values );
		}

		return $this;
	}

	/**
	 * Add Or where config
	 *
	 * @param $args
	 * @return $this
	 */
	public function or_where( $args ) {
		return $this->where( $args, true );
	}

	/**
	 * Join tables
	 *
	 * @param $table
	 * @param $this_table_attr
	 * @param $other_table_attr
	 * @return void
	 */
	public function join( $table, $this_table_attr, $other_table_attr ) {
		$this->table_join = "LEFT JOIN {$table} on {$this_table_attr} = {$other_table_attr}";
	}

	/**
	 * Update Query sortby and order
	 *
	 * @param $attr
	 * @param $order
	 * @return UserFeedback_Query
	 */
	public function orderby( $attr, $order = 'ASC' ) {
		$this->orderby = $attr;
		$this->order   = $order;

		return $this;
	}

	/**
	 * Add grouping to SQL query
	 *
	 * @param $attr
	 * @return $this
	 */
	public function group_by( $attr ) {
		$this->selects[] = 'count(*) as count';
		$this->group_by  = $attr;

		return $this;
	}

	/**
	 * Update query pagination
	 *
	 * @param $per_page
	 * @param $page
	 * @return UserFeedback_Query
	 */
	public function paginate( $per_page, $page = 1 ) {
		$this->is_paginated = true;
		$this->page         = $page;
		$this->per_page     = $per_page;

		$limit = $per_page;

		$offset = $page > 1 ? ( $page - 1 ) * $limit : 0;

		if ( $limit < 1 ) {
			$limit = PHP_INT_MAX;
		}

		$this->limit  = $limit;
		$this->offset = $offset;

		return $this;
	}

	/**
	 * Execute query
	 *
	 * @return array|object|null
	 */
	public function run() {
		global $wpdb;
		return $wpdb->get_results( $this->get_sql() );
	}

	/**
	 * Get Query Where clause
	 *
	 * @return string
	 */
	public function get_where_query() {
		return $this->where;
	}

	/**
	 * Returns true if query has pagination params
	 *
	 * @return bool
	 */
	public function is_paginated() {
		return $this->is_paginated;
	}

	/**
	 * Get Pagination basic info
	 *
	 * @return array
	 */
	public function get_pagination() {
		return array(
			'page'     => $this->page,
			'per_page' => $this->per_page,
		);
	}
}
