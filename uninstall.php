<?php

/**
 * Fired when the plugin is uninstalled.
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('bilge_enabled');
delete_option('bilge_api_key');
delete_option('bilge_base_url');
delete_option('bilge_environment');
delete_option('bilge_release');
delete_option('bilge_report_http_404');
delete_option('bilge_traces_enabled');
delete_option('bilge_profiling_enabled');
delete_option('bilge_rum_enabled');
delete_option('bilge_cron_monitoring');
delete_option('bilge_logs_enabled');
delete_option('bilge_consent');
delete_transient('bilge_remote_config');
delete_transient('bilge_env_context');
