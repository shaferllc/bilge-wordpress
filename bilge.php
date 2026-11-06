<?php

/**
 * Plugin Name: Bilge
 * Description: Send errors, fatals, 404s, performance traces, CPU profiles, PHP logs, cron check-ins, and browser RUM to Bilge.
 * Version: 0.8.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Bilge
 * License: MIT
 * Text Domain: bilge
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('BILGE_VERSION', '0.8.0');
define('BILGE_PLUGIN_FILE', __FILE__);
define('BILGE_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once BILGE_PLUGIN_DIR.'includes/class-bilge-consent.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-config.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-breadcrumbs.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-client.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-profiler.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-tracer.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-rum.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-cron-monitor.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-logger.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-mail.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-auth.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-metrics.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-debug-page.php';
require_once BILGE_PLUGIN_DIR.'includes/class-bilge-plugin.php';

// Golden rule: monitoring must never break the host site. Each subsystem boots in isolation so a
// failure in one disables only that feature, and the whole bootstrap is guarded so a fatal here can
// never white-screen WordPress.
foreach (
    [
        static fn () => Bilge_Breadcrumbs::boot(),
        static fn () => Bilge_Plugin::instance()->boot(),
        static fn () => Bilge_Tracer::boot(),
        static fn () => Bilge_Cron_Monitor::boot(),
        static fn () => Bilge_Logger::boot(),
        static fn () => Bilge_Mail::boot(),
        static fn () => Bilge_Auth::boot(),
        static fn () => add_action('wp_footer', ['Bilge_Rum', 'render'], PHP_INT_MAX),
    ] as $bilge_boot
) {
    try {
        $bilge_boot();
    } catch (Throwable $e) {
        if (function_exists('error_log')) {
            error_log('Bilge: boot failed: '.$e->getMessage());
        }
    }
}
