<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo wp_kses_post( $title );

echo "\n\n";

echo wp_kses_post( $description );

echo "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

foreach ( $summaries as $survey ) {
	echo wp_kses_post(
		"\t" .
		__( 'Survey: ' ) .
		$survey['name'] .
		"\n\n"
	);

	echo wp_kses_post(
		"\t" .
		__( 'Responses: ' ) .
		$survey['responses'] .
		"\n\n"
	);
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf(
	esc_html__( 'Sent from %s', 'userfeedback' ),
	esc_url_raw(get_site_url())
);
