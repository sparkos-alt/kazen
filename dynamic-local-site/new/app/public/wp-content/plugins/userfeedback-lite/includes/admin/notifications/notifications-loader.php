<?php

$base = plugin_dir_path( UserFeedback()->file );

require_once $base . 'includes/admin/notifications/class-userfeedback-notification-external.php';
require_once $base . 'includes/admin/notifications/class-userfeedback-notification-monsterinsights.php';
require_once $base . 'includes/admin/notifications/class-userfeedback-notification-no-live-surveys.php';
require_once $base . 'includes/admin/notifications/class-userfeedback-notification-no-created-surveys.php';
require_once $base . 'includes/admin/notifications/class-userfeedback-notification-first-response.php';
require_once $base . 'includes/admin/notifications/class-userfeedback-notification-popular-survey.php';
require_once $base . 'includes/admin/notifications/class-userfeedback-notification-woocommerce.php';

// Lite Only
require_once $base . 'includes/admin/notifications/class-userfeedback-notification-upgrade-after-7-days.php';
require_once $base . 'includes/admin/notifications/class-userfeedback-notification-upgrade-after-14-days.php';
require_once $base . 'includes/admin/notifications/class-userfeedback-notification-upgrade-after-30-days.php';
require_once $base . 'includes/admin/notifications/class-userfeedback-notification-upgrade-after-10-entries.php';
