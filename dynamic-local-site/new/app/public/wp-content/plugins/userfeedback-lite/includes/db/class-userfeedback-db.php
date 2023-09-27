<?php

require_once USERFEEDBACK_PLUGIN_DIR . 'includes/db/class-userfeedback-query.php';

/**
 * DB class.
 *
 * Abstract class used for communicating with the DB.
 *
 * It intends to serve as a small ORM to make it easier to
 * create, update, and delete "models" from the database.
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage DB
 * @author  David Paternina
 */
abstract class UserFeedback_DB {

	/**
	 * Database table name
	 *
	 * @var string
	 */
	protected $table_name;

	/**
	 * Database table primary key field
	 *
	 * @var string
	 */
	protected $primary_key = 'id';

	/**
	 * Items count per page
	 *
	 * @var int
	 */
	protected $per_page = 10;

	/**
	 * Instance of Query class
	 *
	 * @var UserFeedback_Query
	 */
	protected $query;

	/**
	 * DB Entity casts
	 *
	 * @var array
	 */
	protected $casts = array();

	/**
	 * Count related objects
	 *
	 * @var array
	 */
	protected $counts = array();

	/**
	 * Count related objects with conditions
	 *
	 * @var array
	 */
	protected $counts_where = array();

	/**
	 * Relations included in query
	 *
	 * @var array
	 */
	protected $with = array();

	/**
	 * @var string
	 */
	protected $timestamp_column = 'created_at';

	/**
	 * Whether this query has a 'group by' clause
	 *
	 * @var bool
	 */
	protected $is_grouping = false;

	/**
	 * Checks if table exists
	 *
	 * @return bool
	 */
	public static function table_exists() {
		global $wpdb;

		$instance = new static();// self::get_instance();
		$table    = $instance->get_table();

		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}


	/**
	 * Add Where clause to query
	 *
	 * @param $args
	 * @return static
	 */
	public static function where( $args ) {
		$instance = new static();
		$instance->query->where( $args );
		return $instance;
	}

	/**
	 * Get all items in DB
	 *
	 * @return array|false|object|string|null
	 */
	public static function all() {
		return ( new static() )->get();
	}

	/**
	 * Get DB item by primary  key
	 *
	 * @param $id
	 * @return array|object|null
	 */
	public static function find( $id ) {
		$instance = new static();// self::get_instance();
		return self::get_by( $instance->primary_key, $id );
	}

	/**
	 * Query element by a db column
	 *
	 * @param $column
	 * @param $value
	 * @return array|object|null
	 */
	public static function get_by( $column, $value ) {
		$instance = new static();// self::get_instance();
		$table    = $instance->get_table();

		global $wpdb;
		return $instance->process_item(
			$wpdb->get_row(
				$wpdb->prepare(
					"
                    SELECT *
                    FROM $table
                    WHERE %1s = %s
                    LIMIT %d
                    ",
					strval( $column ),
					strval( $value ),
					1
				)
			)
		);
	}

	/**
	 * Create item and return the Id of the inserted row
	 *
	 * @param $args array
	 * @return int
	 */
	public static function create( $args, $new_timestamps = true ) {
		global $wpdb;

		$instance = new static();// self::get_instance();
		$table    = $instance->get_table();

		foreach ( $args as $key => $value ) {
			if ( ! in_array( $key, $instance->get_columns() ) ) {
				unset( $args[ $key ] );
			}
		}

		$params = $args;

		if ( $new_timestamps ) {
			$params = array_merge(
				$params,
				array(
					$instance->timestamp_column => current_time( 'mysql' ),
				)
			);
		}

		$params = $instance->encode_entity_attributes( $params );

		$wpdb->insert( $table, $params );
		return $wpdb->insert_id;
	}

	/**
	 * Update item by primary key
	 *
	 * @param $id
	 * @param $args
	 * @return int
	 */
	public static function update( $id, $args ) {
		global $wpdb;

		$instance = new static();// self::get_instance();
		$table    = $instance->get_table();

		unset( $args[ $instance->primary_key ] );
		unset( $args[ $instance->timestamp_column ] );

		$params = array();

		foreach ( $args as $key => $value ) {
			if ( array_search( $key, $instance->get_columns() ) ) {
				$params[ $key ] = $value;
			}
		}

		$params = $instance->encode_entity_attributes( $params );

		return $wpdb->update(
			$table,
			$params,
			array(
				$instance->primary_key => $id,
			)
		);
	}

	/**
	 * Update many items by primary key
	 *
	 * @param $ids
	 * @param $args
	 * @return int
	 */
	public static function update_many( $ids, $args ) {
		global $wpdb;

		$instance = new static();// self::get_instance();
		$table    = $instance->get_table();

		unset( $args[ $instance->primary_key ] );
		unset( $args[ $instance->timestamp_column ] );

		$where_sql = "WHERE {$instance->primary_key} IN (" . implode( ', ', array_fill( 0, count( $ids ), '%d' ) ) . ')';
		$where_sql = $wpdb->prepare( $where_sql, $ids );

		// --------

		$params = $instance->encode_entity_attributes( $args );

		foreach ( $args as $key => $value ) {
			if ( array_search( $key, $instance->get_columns() ) ) {
				$params[ $key ] = $value;
			}
		}

		$set_sql = 'SET ';

		foreach ( array_keys( $params ) as $index => $key ) {
			$set_sql .= "{$key}=%s";

			if ( $index < sizeof( $params ) - 1 ) {
				$set_sql .= ',';
			}
		}

		$set_sql   = $wpdb->prepare( $set_sql, array_values( $params ) );
		$final_sql = "UPDATE {$table} {$set_sql} {$where_sql}";

		return $wpdb->query(
			$wpdb->prepare( $final_sql )
		);
	}

	/**
	 * Delete DB items by primary key
	 *
	 * @param $ids array
	 * @return bool|int
	 */
	public static function delete( $ids ) {
		global $wpdb;

		$instance = new static();// self::get_instance();
		$table    = $instance->get_table();

		$sql = "DELETE FROM {$table} WHERE ";

		foreach ( $ids as $index => $id ) {
			$sql .= "{$instance->primary_key} = %s";

			if ( $index < sizeof( $ids ) - 1 ) {
				$sql .= ' OR ';
			}
		}

		return $wpdb->query(
			$wpdb->prepare( $sql, $ids )
		);
	}

	/**
	 * Get table elements count
	 *
	 * @param string $where_sql Sanitized where sql
	 * @return int Elements count
	 */
	public static function count( $where_sql = '' ) {

		global $wpdb;

		$instance = new static();// self::get_instance();
		$table    = $instance->get_table();

		return absint(
			$wpdb->get_var(
				"SELECT COUNT({$table}.{$instance->primary_key})
					FROM {$table}
					{$where_sql}"
			)
		);
	}

	/**
	 * Get new DB Query object
	 *
	 * @return static
	 */
	public static function query() {
		return new static();
	}

	// ---------------------------
	// ---- Instance functions ---
	// ---------------------------

	/**
	 * Get related objects count
	 *
	 * @param $relations
	 * @return $this
	 */
	public function with_count( $relations = array() ) {
		$this->counts = $relations;
		return $this;
	}

	/**
	 * Get related object count with query
	 *
	 * @param $relation
	 * @param $where_config
	 * @param $as
	 * @return UserFeedback_DB
	 */
	public function with_count_where( $relation, $where_config, $as = null ) {
		$as = $as ?: "{$relation}_count";

		$this->counts_where[] = array(
			'relation' => $relation,
			'where'    => $where_config,
			'as'       => $as,
		);

		return $this;
	}

	/**
	 * Include related objects
	 *
	 * @param $relations
	 * @return UserFeedback_DB
	 */
	public function with( $relations = array() ) {
		$this->with = $relations;
		return $this;
	}

	/**
	 * And an OR condition to current query
	 *
	 * @param $args
	 * @return $this
	 */
	public function or_where( $args ) {
		$this->query->or_where( $args );
		return $this;
	}

	/**
	 * And an AND condition to current query
	 *
	 * @param $args
	 * @return $this
	 */
	public function add_where( $args, $override = false ) {
		$this->query->where( $args, false, $override );
		return $this;
	}

	/**
	 * Add group by parameter
	 *
	 * @param $attribute
	 * @return $this
	 */
	public function group_by( $attribute ) {
		$this->is_grouping = true;
		$this->query->group_by( $attribute );
		return $this;
	}

	/**
	 * Add where has config
	 *
	 * @param $relation
	 * @param $attributes
	 * @return $this
	 */
	public function where_has( $relation, $attributes ) {

		$config = $this->get_relationship_config( $relation );

		if ( ! $config ) {
			return $this;
		}

		/**
		 * @var UserFeedback_DB $related_instance
		 */
		$related_instance = ( new $config['class']() );
		$related_table    = $related_instance->get_table();

		$this->query->join(
			$related_table,
			"{$this->get_table()}.{$config['key']}",
			"{$related_table}.{$related_instance->primary_key}"
		);
		$this->query->where( $attributes );
		return $this;
	}

	/**
	 *  Create DB helper instance
	 */
	public function __construct() {
		$this->query = new UserFeedback_Query( $this->get_table(), $this->primary_key );
	}

	/**
	 * Get table name with WP prefix
	 *
	 * @return string
	 */
	public function get_table() {
		global $wpdb;
		return $wpdb->prefix . $this->table_name;
	}

	/**
	 * Cast item attributes
	 *
	 * @param $item
	 * @return mixed
	 */
	public function cast_entity_attributes( $item ) {
		foreach ( $this->casts as $attr => $type ) {
			if ( ! isset( $item->{$attr} ) ) {
				continue;
			}

			$raw_value = $item->{$attr};

			switch ( $type ) {
				case 'array':
					$item->{$attr} = ! empty( $raw_value ) ? json_decode( $raw_value ) : array();
					break;
				case 'object':
					$item->{$attr} = ! empty( $raw_value ) ? json_decode( $raw_value ) : new stdClass();
					break;
			}
		}

		return $item;
	}

	/**
	 * Populate item aggregates
	 *
	 * @param $item
	 * @return mixed
	 */
	public function populate_aggregates( $item ) {
		foreach ( $this->counts as $relation ) {
			$config = $this->get_relationship_config( $relation );

			if ( ! $config ) {
				continue;
			}

			if ( $config['type'] === 'many' ) {

				/**
				 * @var UserFeedback_DB $related_instance
				 */
				$related_instance = ( new $config['class']() );

				$query = $related_instance->query->where(
					array(
						$config['key'] => $item->{$this->primary_key},
					)
				);

				$count = $config['class']::count( $query->get_where_query() );

				$item->{"{$relation}_count"} = $count;
			}
		}

		foreach ( $this->counts_where as $count_where_item ) {
			$config = $this->get_relationship_config( $count_where_item['relation'] );

			if ( ! $config ) {
				continue;
			}

			if ( $config['type'] === 'many' ) {

				/**
				 * @var UserFeedback_DB $related_instance
				 */
				$related_instance = ( new $config['class']() );

				$query = $related_instance->query->where(
					array_merge(
						array(
							$config['key'] => $item->{$this->primary_key},
						),
						$count_where_item['where']
					)
				);

				$count = $config['class']::count( $query->get_where_query() );

				$item->{$count_where_item['as']} = $count;
			}
		}

		return $item;
	}

	/**
	 * Populate related items
	 *
	 * @param $item
	 * @return mixed
	 */
	public function populate_relations( $item ) {
		foreach ( $this->with as $relation ) {
			$config = $this->get_relationship_config( $relation );

			if ( ! $config ) {
				continue;
			}

			if ( $config['type'] === 'many' ) {

				/**
				 * @var UserFeedback_DB $related_instance
				 */
				$related_instance = ( new $config['class']() );

				$related_instance->query->where(
					array(
						$config['key'] => $item->{$this->primary_key},
					)
				);

				$related = $related_instance->get();

				$item->{$relation} = $related;
			} elseif ( $config['type'] === 'one' ) {

				/**
				 * @var UserFeedback_DB $related_instance
				 */
				$related_instance = ( new $config['class']() );

				$related_instance->query->where(
					array(
						$related_instance->primary_key => $item->{$config['key']},
					)
				);

				$related           = $related_instance->single();
				$item->{$relation} = $related;
			}
		}

		return $item;
	}

	/**
	 * Encode entity params if necessary
	 *
	 * @param $params
	 * @return mixed
	 */
	public function encode_entity_attributes( $params ) {
		foreach ( $this->casts as $attr => $type ) {

			if ( ! isset( $params[ $attr ] ) ) {
				continue;
			}

			$raw_value = $params[ $attr ];

			$value = null;

			switch ( $type ) {
				case 'array':
					$value = ! empty( $raw_value ) ? $raw_value : array();
					break;
				case 'object':
					$value = ! empty( $raw_value ) ? $raw_value : new stdClass();
					break;
			}

			if ( $value !== null ) {
				$params[ $attr ] = json_encode( $value );
			}
		}

		return $params;
	}

	/**
	 * Apply pagination to Query
	 *
	 * @param $per_page
	 * @param $page
	 * @return UserFeedback_DB
	 */
	public function paginate( $per_page, $page = 1 ) {
		$per_page = $per_page ? (int) $per_page : $this->per_page;
		$per_page = (int) apply_filters(
			"{$this->get_table()}_per_page",
			$per_page
		);

		$this->query->paginate( $per_page, $page );
		return $this;
	}

	/**
	 * Select fields
	 *
	 * @param $columns
	 * @return UserFeedback_DB
	 */
	public function select( $columns = array( '*' ) ) {
		$has_primary        = false;
		$applicable_columns = array();

		foreach ( $columns as $column ) {

			$column_data    = explode( '.', $column );
			$full_column    = $column;
			$has_table_name = sizeof( $column_data ) > 1;

			if ( $has_table_name ) {
				$column = $column_data[1];
			}

			if ( $column === '*' || $column === $this->primary_key || $column === 'count' ) {
				$has_primary = true;
			}

			if ( in_array( $column, $this->get_columns() ) ) {
				$applicable_columns[] = $has_table_name ? $full_column : $column;
			}
		}

		if ( ! $has_primary ) {
			$applicable_columns = array_merge(
				array( 'id' ),
				$applicable_columns
			);
		}

		$this->query->select( $applicable_columns );

		return $this;
	}

	/**
	 * Add raw select
	 *
	 * @param $select
	 * @return $this
	 */
	public function select_raw( $select ) {
		$this->query->select_raw( $select );
		return $this;
	}

	/**
	 * Apply sort to query
	 *
	 * @param $field
	 * @param $order
	 * @return $this
	 */
	public function sort( $field, $order = 'ASC' ) {
		$orderby = ! in_array( $field, $this->get_columns() ) ? $this->primary_key : $field;
		$order   = strtoupper( $order );
		$sort    = 'ASC' === $order ? 'ASC' : 'DESC';
		$this->query->orderby( $orderby, $sort );

		return $this;
	}

	/**
	 * Get all elements in table, paginated.
	 *
	 * @return array
	 */
	public function get() {

		$results = $this->query->run();
		$results = array_map( array( $this, 'process_item' ), $results );

		if ( $this->query->is_paginated() ) {
			$total_items = self::count( $this->query->get_where_query() );

			$pagination = $this->query->get_pagination();

			$total_pages = ceil( $total_items / $pagination['per_page'] );

			return array(
				'items'      => $results,
				'pagination' => array_merge(
					array(
						'total' => $total_items,
						'pages' => $total_pages,
					),
					$pagination
				),
			);
		}

		return $results;
	}

	/**
	 * Get a single result item
	 *
	 * @return mixed
	 */
	public function single() {
		global $wpdb;

		$sql = "{$this->sql()} LIMIT 1";

		$raw_item = $wpdb->get_row(
			$wpdb->prepare( $sql )
		);

		return $raw_item ? $this->process_item( $raw_item ) : null;
	}

	/**
	 * Get Query object count
	 *
	 * @return int
	 */
	public function get_count() {
		return self::count( $this->query->get_where_query() );
	}

	/**
	 * Process result item, parsing JSON attributes and including any requested relations or aggregates
	 *
	 * @param $item
	 * @return mixed
	 */
	protected function process_item( $item ) {

		if ( empty( $item->id ) ) {
			return $item;
		}

		$item = $this->populate_relations( $item );

		if ( $this->is_grouping ) {
			return $item;
		}

		$item = $this->cast_entity_attributes( $item );
		$item = $this->populate_aggregates( $item );

		return $item;
	}

	/**
	 * Get current Query SQL
	 *
	 * @return string
	 */
	public function sql() {
		return $this->query->get_sql();
	}

	/**
	 * Drop DB Table
	 *
	 * @return void
	 */
	public function dropTable() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( ! self::table_exists() ) {
			return;
		}

		$table_name = self::get_table();

		$sql = "DROP TABLE IF EXISTS {$table_name}";

		$wpdb->query(
			$wpdb->prepare( $sql )
		);
	}

	// ---------------------------
	// ---- Abstract functions ---
	// ---------------------------

	/**
	 * Retrieve the list of columns for the database table.
	 * Sub-classes should define an array of columns here.
	 *
	 * @return array List of columns.
	 */
	abstract function get_columns();

	/**
	 * Create table if it does not exist
	 */
	abstract function create_table();

	/**
	 * Get relationships config
	 *
	 * @param $name
	 * @return mixed
	 */
	abstract function get_relationship_config( $name );
}
