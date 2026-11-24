<?php
// Loaded by WP-CLI before WordPress, only in the isolated integration test runner.
if (!defined('WP_CLI') || !WP_CLI) return;
WP_CLI::add_hook('after_wp_load', static function () { add_filter('pre_wp_mail', '__return_true'); });
