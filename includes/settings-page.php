<?php
/**
 * Settings UI under Settings → Bilge.
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$bilge_base = (string) get_option('bilge_base_url', '');
$bilge_key = (string) get_option('bilge_api_key', '');
$bilge_connected = get_option('bilge_enabled') && $bilge_base !== '' && $bilge_key !== '';
?>
<style>
    .bilge-admin { max-width: 860px; margin: 24px 20px 40px 0; color: #1e293b; }
    .bilge-admin * { box-sizing: border-box; }
    .bilge-hero {
        display: flex; align-items: center; justify-content: space-between; gap: 24px;
        padding: 28px 32px; border-radius: 16px; color: #e2e8f0;
        background: radial-gradient(120% 140% at 0% 0%, #16324a 0%, #0b1220 60%);
        box-shadow: 0 10px 30px -12px rgba(8, 15, 30, 0.55);
    }
    .bilge-hero h1 { color: #fff; font-size: 26px; line-height: 1.1; margin: 0 0 6px; font-weight: 700; letter-spacing: -0.01em; }
    .bilge-hero p { margin: 0; color: #94a3b8; font-size: 14px; max-width: 46ch; }
    .bilge-mark { display: inline-flex; align-items: center; gap: 11px; margin-bottom: 14px; }
    .bilge-mark .dot { width: 12px; height: 12px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 4px rgba(52, 211, 153, 0.18); }
    .bilge-mark span { font-size: 12px; letter-spacing: 0.22em; text-transform: uppercase; color: #5eead4; font-weight: 600; }
    .bilge-status { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; white-space: nowrap; }
    .bilge-status.on { background: rgba(52, 211, 153, 0.14); color: #6ee7b7; border: 1px solid rgba(52, 211, 153, 0.35); }
    .bilge-status.off { background: rgba(148, 163, 184, 0.12); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); }
    .bilge-status .pulse { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
    .bilge-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px 28px; margin-top: 20px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); }
    .bilge-card h2 { font-size: 16px; font-weight: 700; margin: 0 0 4px; color: #0f172a; }
    .bilge-card .sub { margin: 0 0 18px; color: #64748b; font-size: 13px; }
    .bilge-field { padding: 16px 0; border-top: 1px solid #f1f5f9; }
    .bilge-field:first-of-type { border-top: 0; padding-top: 4px; }
    .bilge-field label.lbl { display: block; font-weight: 600; font-size: 13px; color: #0f172a; margin-bottom: 6px; }
    .bilge-field input[type=url], .bilge-field input[type=password], .bilge-field input[type=text] {
        width: 100%; max-width: 480px; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 9px; font-size: 14px; background: #f8fafc;
    }
    .bilge-field input:focus { border-color: #34d399; box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.18); outline: none; background: #fff; }
    .bilge-field .hint { margin: 7px 0 0; color: #94a3b8; font-size: 12px; }
    .bilge-toggle { display: flex; align-items: flex-start; gap: 12px; }
    .bilge-toggle input { margin-top: 3px; width: 16px; height: 16px; accent-color: #10b981; }
    .bilge-toggle .body strong { display: block; font-size: 13px; color: #0f172a; }
    .bilge-toggle .body span { display: block; color: #64748b; font-size: 12px; margin-top: 2px; }
    .bilge-actions { margin-top: 22px; display: flex; align-items: center; gap: 12px; }
    .bilge-btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 9px; font-size: 14px; font-weight: 600; border: 0; cursor: pointer; text-decoration: none; }
    .bilge-btn.primary { background: #0f766e; color: #fff; }
    .bilge-btn.primary:hover { background: #115e59; }
    .bilge-btn.ghost { background: #f1f5f9; color: #334155; }
    .bilge-btn.ghost:hover { background: #e2e8f0; }
    .bilge-notice { padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 18px; }
    .bilge-notice.ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .bilge-notice.err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .bilge-foot { margin-top: 16px; color: #94a3b8; font-size: 12px; }
</style>

<div class="bilge-admin">
    <?php if (isset($_GET['bilge_test'])) { ?>
        <?php if ((string) $_GET['bilge_test'] === '1') { ?>
            <div class="bilge-notice ok"><?php esc_html_e('Test event sent. Check your Bilge project for a new issue.', 'bilge'); ?></div>
        <?php } else { ?>
            <div class="bilge-notice err"><?php esc_html_e('Could not send test: enable the plugin and fill API key and base URL.', 'bilge'); ?></div>
        <?php } ?>
    <?php } ?>

    <div class="bilge-hero">
        <div>
            <div class="bilge-mark"><span class="dot"></span><span><?php esc_html_e('Bilge', 'bilge'); ?></span></div>
            <h1><?php esc_html_e('Monitoring &amp; performance', 'bilge'); ?></h1>
            <p><?php esc_html_e('Send errors, fatals, 404s, request performance, and browser vitals from this site to your Bilge project.', 'bilge'); ?></p>
        </div>
        <div class="bilge-status <?php echo $bilge_connected ? 'on' : 'off'; ?>">
            <span class="pulse"></span>
            <?php echo $bilge_connected ? esc_html__('Connected', 'bilge') : esc_html__('Not connected', 'bilge'); ?>
        </div>
    </div>

    <form action="options.php" method="post">
        <?php settings_fields('bilge'); ?>

        <div class="bilge-card">
            <h2><?php esc_html_e('Connection', 'bilge'); ?></h2>
            <p class="sub"><?php esc_html_e('Use a dedicated project API key from your Bilge project settings.', 'bilge'); ?></p>

            <div class="bilge-field">
                <div class="bilge-toggle">
                    <input type="hidden" name="bilge_enabled" value="0" />
                    <input type="checkbox" id="bilge_enabled" name="bilge_enabled" value="1" <?php checked(get_option('bilge_enabled')); ?> />
                    <div class="body">
                        <strong><?php esc_html_e('Enable Bilge', 'bilge'); ?></strong>
                        <span><?php esc_html_e('Master switch. When off, nothing is sent.', 'bilge'); ?></span>
                    </div>
                </div>
            </div>

            <div class="bilge-field">
                <label class="lbl" for="bilge_base_url"><?php esc_html_e('Bilge base URL', 'bilge'); ?></label>
                <input type="url" id="bilge_base_url" name="bilge_base_url" value="<?php echo esc_attr($bilge_base); ?>" placeholder="https://app.example.com" autocomplete="off" />
                <p class="hint"><?php esc_html_e('Origin only, no trailing slash (e.g. https://bilge.example.com).', 'bilge'); ?></p>
            </div>

            <div class="bilge-field">
                <label class="lbl" for="bilge_api_key"><?php esc_html_e('Project API key', 'bilge'); ?></label>
                <input type="password" id="bilge_api_key" name="bilge_api_key" value="<?php echo esc_attr($bilge_key); ?>" autocomplete="off" />
            </div>

            <div class="bilge-field">
                <label class="lbl" for="bilge_environment"><?php esc_html_e('Environment', 'bilge'); ?></label>
                <input type="text" id="bilge_environment" name="bilge_environment" value="<?php echo esc_attr((string) get_option('bilge_environment', 'production')); ?>" />
                <p class="hint"><?php esc_html_e('Tagged on each event (e.g. production, staging).', 'bilge'); ?></p>
            </div>

            <div class="bilge-field">
                <label class="lbl" for="bilge_release"><?php esc_html_e('Release', 'bilge'); ?></label>
                <input type="text" id="bilge_release" name="bilge_release" value="<?php echo esc_attr((string) get_option('bilge_release', '')); ?>" />
                <p class="hint"><?php esc_html_e('Deploy identifier (e.g. git SHA, version tag). Leave blank if unused - an empty value is never sent. Overridden by a BILGE_RELEASE constant in wp-config.php.', 'bilge'); ?></p>
            </div>
        </div>

        <div class="bilge-card">
            <h2><?php esc_html_e('What to send', 'bilge'); ?></h2>
            <p class="sub"><?php esc_html_e('Errors and fatals are always sent while enabled. Sampling rates are controlled from your Bilge project settings.', 'bilge'); ?></p>

            <div class="bilge-field">
                <div class="bilge-toggle">
                    <input type="hidden" name="bilge_report_http_404" value="0" />
                    <input type="checkbox" id="bilge_report_http_404" name="bilge_report_http_404" value="1" <?php checked(get_option('bilge_report_http_404', true)); ?> />
                    <div class="body">
                        <strong><?php esc_html_e('404 pages', 'bilge'); ?></strong>
                        <span><?php esc_html_e('Report missing pages (HTTP 404) as warnings.', 'bilge'); ?></span>
                    </div>
                </div>
            </div>

            <div class="bilge-field">
                <div class="bilge-toggle">
                    <input type="hidden" name="bilge_traces_enabled" value="0" />
                    <input type="checkbox" id="bilge_traces_enabled" name="bilge_traces_enabled" value="1" <?php checked(get_option('bilge_traces_enabled', true)); ?> />
                    <div class="body">
                        <strong><?php esc_html_e('Performance traces', 'bilge'); ?></strong>
                        <span><?php esc_html_e('Request timing, slow DB queries, and outbound HTTP for sampled page views.', 'bilge'); ?></span>
                    </div>
                </div>
            </div>

            <div class="bilge-field">
                <div class="bilge-toggle">
                    <input type="hidden" name="bilge_profiling_enabled" value="0" />
                    <input type="checkbox" id="bilge_profiling_enabled" name="bilge_profiling_enabled" value="1" <?php checked(get_option('bilge_profiling_enabled', true)); ?> />
                    <div class="body">
                        <strong><?php esc_html_e('CPU profiles', 'bilge'); ?></strong>
                        <span><?php esc_html_e('Sample a profile on each sampled trace so it carries a flame graph / hotspots. Uses the Excimer PHP extension when available; otherwise a lightweight cooperative sampler that needs no extension.', 'bilge'); ?></span>
                    </div>
                </div>
            </div>

            <div class="bilge-field">
                <div class="bilge-toggle">
                    <input type="hidden" name="bilge_rum_enabled" value="0" />
                    <input type="checkbox" id="bilge_rum_enabled" name="bilge_rum_enabled" value="1" <?php checked(get_option('bilge_rum_enabled', false)); ?> />
                    <div class="body">
                        <strong><?php esc_html_e('Browser monitoring (RUM)', 'bilge'); ?></strong>
                        <span><?php esc_html_e('Web Vitals and front-end JS errors from visitors. No cookies set, no IP collected by the script. Disclose this in your privacy policy. Requires consent.', 'bilge'); ?></span>
                    </div>
                </div>
            </div>

            <div class="bilge-field">
                <div class="bilge-toggle">
                    <input type="hidden" name="bilge_cron_monitoring" value="0" />
                    <input type="checkbox" id="bilge_cron_monitoring" name="bilge_cron_monitoring" value="1" <?php checked(get_option('bilge_cron_monitoring', false)); ?> />
                    <div class="body">
                        <strong><?php esc_html_e('WP-Cron monitoring', 'bilge'); ?></strong>
                        <span><?php esc_html_e('Report each scheduled WP-Cron event as a check-in so Bilge can alert on missed or failing crons. Noisy core maintenance hooks are skipped.', 'bilge'); ?></span>
                    </div>
                </div>
            </div>

            <div class="bilge-field">
                <div class="bilge-toggle">
                    <input type="hidden" name="bilge_logs_enabled" value="0" />
                    <input type="checkbox" id="bilge_logs_enabled" name="bilge_logs_enabled" value="1" <?php checked(get_option('bilge_logs_enabled', false)); ?> />
                    <div class="body">
                        <strong><?php esc_html_e('PHP logs', 'bilge'); ?></strong>
                        <span><?php esc_html_e('Forward PHP warnings, notices, and deprecations (the entries that go to debug.log) to your Logs stream. Fatals are already reported as errors. Off by default — this stream can be noisy on some sites.', 'bilge'); ?></span>
                    </div>
                </div>
            </div>

            <div class="bilge-field">
                <div class="bilge-toggle">
                    <input type="hidden" name="bilge_auth_monitoring" value="0" />
                    <input type="checkbox" id="bilge_auth_monitoring" name="bilge_auth_monitoring" value="1" <?php checked(get_option('bilge_auth_monitoring', false)); ?> />
                    <div class="body">
                        <strong><?php esc_html_e('Authentication monitoring', 'bilge'); ?></strong>
                        <span><?php esc_html_e('Report login, logout, failed-login, registration, and password-reset events to your Authentication watcher. Sends the user id and a display label (email/username) only — never passwords. Off by default.', 'bilge'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bilge-actions">
            <button type="submit" class="bilge-btn primary"><?php esc_html_e('Save changes', 'bilge'); ?></button>
        </div>
    </form>

    <div class="bilge-card">
        <h2><?php esc_html_e('Send a test event', 'bilge'); ?></h2>
        <p class="sub"><?php esc_html_e('Posts a harmless info-level event so you can confirm the key and URL.', 'bilge'); ?></p>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="bilge-actions" style="margin-top:0;">
            <?php wp_nonce_field('bilge_send_test'); ?>
            <input type="hidden" name="action" value="bilge_send_test" />
            <button type="submit" class="bilge-btn ghost"><?php esc_html_e('Send test event', 'bilge'); ?></button>
        </form>
    </div>

    <p class="bilge-foot">
        <?php esc_html_e('If WordPress and Bilge share the same hostname, the plugin skips sending to avoid recursion. Use a different host for your site or for Bilge.', 'bilge'); ?>
    </p>
</div>
