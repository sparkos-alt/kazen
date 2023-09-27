<?php

/**
 * Survey class.
 *
 * @see UserFeedback_DB
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage DB
 * @author  David Paternina
 */
class UserFeedback_Survey extends UserFeedback_DB {

	/**
	 * @inheritdoc
	 */
	protected $table_name = 'userfeedback_surveys';

	/**
	 * @inheritdoc
	 */
	protected $casts = array(
		'questions'     => 'array',
		'settings'      => 'object',
		'notifications' => 'object',
	);

	/**
	 * @inheritdoc
	 */
	public static function find( $id ) {
		return self::where(
			array(
				'id' => $id,
			)
		)->with_count( array( 'responses' ) )->single();
	}

	/**
	 * Add survey impression
	 *
	 * @param $id
	 * @return void
	 */
	public static function record_impression( $id ) {
		$instance = new static();

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"
                UPDATE {$instance->get_table()}
                    SET impressions = impressions + 1
                    WHERE %1s = %s
                ",
				strval( $instance->primary_key ),
				strval( $id )
			)
		);

		do_action( 'userfeedback_after_db_impression', $id );
	}

	/**
	 * Change Surveys status to draft
	 *
	 * @param $survey_ids
	 * @return bool|int
	 */
	public static function draft( $survey_ids ) {
		return self::update_many(
			$survey_ids,
			array(
				'status' => 'draft',
			)
		);
	}

	/**
	 * Change Surveys status to publish
	 *
	 * @param $survey_ids
	 * @return bool|int
	 */
	public static function publish( $survey_ids ) {
		return self::update_many(
			$survey_ids,
			array(
				'status' => 'publish',
			)
		);
	}

	/**
	 * Change Surveys status to trash
	 *
	 * @param $survey_ids
	 * @return bool|int
	 */
	public static function trash( $survey_ids ) {
		return self::update_many(
			$survey_ids,
			array(
				'status' => 'trash',
			)
		);
	}

	/**
	 * Change Surveys status to draft
	 *
	 * @param $survey_ids
	 * @return bool|int
	 */
	public static function restore( $survey_ids ) {
		return self::update_many(
			$survey_ids,
			array(
				'status' => 'draft',
			)
		);
	}

	/**
	 * Search Survey question by id
	 *
	 * @param $survey
	 * @param $question_id
	 * @return false|mixed
	 */
	public static function search_question( $survey, $question_id ) {

		if ( is_numeric( $survey ) ) {
			$survey = self::find( $survey );
		}

		$questions = $survey->questions;

		foreach ( $questions as $question ) {
			if ( $question->id === $question_id ) {
				return $question;
			}
		}

		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function get_columns() {
		return array( 'id', 'title', 'status', 'questions', 'settings', 'notifications', 'impressions', 'publish_at', 'created_at' );
	}

	/**
	 * @inheritdoc
	 */
	public function create_table() {
		global $wpdb;

		if ( self::table_exists() ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = self::get_table();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(128),
            status enum('publish', 'draft', 'trash') DEFAULT 'draft',
            questions longtext,
            impressions bigint(20) default 0 NOT NULL,
            settings text,
            notifications text,
            publish_at timestamp NULL,
            created_at timestamp NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * @inheritdoc
	 */
	public function get_relationship_config( $name ) {
		switch ( $name ) {
			case 'responses':
				return array(
					'type'  => 'many',
					'class' => UserFeedback_Response::class,
					'key'   => 'survey_id',
				);
			default:
				return null;
		}
	}
}
