<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo sprintf(
	esc_html__( 'New Response to %s', 'userfeedback' ),
	esc_html__($survey_title)
);


echo "\n\n";

$notification_config_url = userfeedback_get_screen_url( 'userfeedback_surveys', "edit/$survey_id/notifications" );

echo sprintf(
	esc_html__( 'You are receiving this UserFeedback survey notification from %1$s. Adjust your settings here: %2$s.', 'userfeedback' ),
	esc_html__(get_bloginfo( 'name' )),
	esc_url_raw($notification_config_url)
);

echo "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

foreach ( $answers as $answer ) {
	echo "\t" . esc_html( $answer['question_title'] ) . "\n\n";
	echo "\t" . esc_html( $answer['value'] ) . "\n\n";
	echo "\t--------\n\n";
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf(
	esc_html__(__( 'Sent from %s', 'userfeedback' )),
	esc_url_raw(get_site_url())
);
