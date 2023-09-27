<?php

/**
 * Survey Response class.
 *
 * @see UserFeedback_DB
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage DB
 * @author  David Paternina
 */
class UserFeedback_Response extends UserFeedback_DB {

	/**
	 * @inheritdoc
	 */
	protected $table_name = 'userfeedback_survey_responses';

	/**
	 * @inheritdoc
	 */
	function get_columns() {
		return array( 'id', 'survey_id', 'answers', 'page_submitted', 'user_ip', 'user_browser', 'user_os', 'user_device', 'status', 'submitted_at' );
	}

	/**
	 * @inheritdoc
	 */
	protected $casts = array(
		'answers'        => 'array',
		'page_submitted' => 'object',
	);

	/**
	 * @inheritdoc
	 */
	protected $timestamp_column = 'submitted_at';

	/**
	 * @inheritdoc
	 */
	public function cast_entity_attributes( $item ) {
		$response = parent::cast_entity_attributes( $item );

		if ( isset( $response->page_submitted ) ) {
			$response->page_submitted->url =
				'publish' === get_post_status( $response->page_submitted->id )
					? get_permalink( $response->page_submitted->id )
					: null;
		}
		return $response;
	}

	/**
	 * @inheritdoc
	 */
	function create_table() {
		global $wpdb;

		if ( self::table_exists() ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = self::get_table();

		$surveys_db_instance = new UserFeedback_Survey();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            survey_id bigint(20) NOT NULL,
            answers longtext,
            page_submitted text,
            user_ip varchar(256),
            user_browser varchar(128),
            user_os varchar(128),
            user_device varchar(64),
			status enum('publish', 'draft', 'trash') DEFAULT 'publish',
            submitted_at timestamp NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (survey_id) 
                REFERENCES {$surveys_db_instance->get_table()}({$surveys_db_instance->primary_key}) ON DELETE CASCADE
        ) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Change Responses status to trash
	 *
	 * @param $response_ids
	 * @return bool|int
	 */
	public static function trash( $response_ids ) {
		return self::update_many(
			$response_ids,
			array(
				'status' => 'trash',
			)
		);
	}
	
	/**
	 * Change Responses status to publish
	 *
	 * @param $response_ids
	 * @return bool|int
	 */
	public static function restore( $response_ids ) {
		return self::update_many(
			$response_ids,
			array(
				'status' => 'publish',
			)
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


	public function add_status_column() {
		global $wpdb;
		$table_name = self::get_table();
		// check if column exists
		$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$table_name}' AND column_name = 'status'" );
		// create status column
		if(empty($row)){
			$wpdb->query("ALTER TABLE $table_name ADD status enum('publish', 'draft', 'trash') DEFAULT 'publish' AFTER user_device");
		}
		
	}

	function get_relationship_config( $name ) {
		switch ( $name ) {
			case 'survey':
				return array(
					'type'  => 'one',
					'class' => UserFeedback_Survey::class,
					'key'   => 'survey_id',
				);
			default:
				return null;
		}
	}
}
