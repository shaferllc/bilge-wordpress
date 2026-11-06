<?php

/**
 * Custom metrics.
 *
 * A small developer API — bilge_metric() — for counters, gauges, and distributions. Entries are
 * buffered per request and flushed once at shutdown to /api/ingest/metric (after the page is
 * delivered). Gated on the remote `metrics` signal; never throws.
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class Bilge_Metrics
{
    private const MAX = 100;

    private const KINDS = ['counter', 'gauge', 'distribution'];

    private const NAME_PATTERN = '/^[a-zA-Z][a-zA-Z0-9_.:\/-]*$/';

    /** @var list<array<string, mixed>> */
    private static array $buffer = [];

    private static bool $flush_registered = false;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function record(string $name, float $value, string $kind = 'counter', ?string $unit = null, array $attributes = []): void
    {
        try {
            if (! self::enabled() || count(self::$buffer) >= self::MAX) {
                return;
            }
            if (preg_match(self::NAME_PATTERN, $name) !== 1) {
                return; // Skip invalid names so one bad metric can't drop the whole batch.
            }
            if (! in_array($kind, self::KINDS, true)) {
                $kind = 'counter';
            }

            self::$buffer[] = array_filter([
                'name' => substr($name, 0, 128),
                'kind' => $kind,
                'value' => $value,
                'unit' => $unit !== null && $unit !== '' ? substr($unit, 0, 32) : null,
                'attributes' => $attributes !== [] ? array_slice($attributes, 0, 64, true) : null,
            ], static fn ($v): bool => $v !== null);

            self::register_flush();
        } catch (Throwable $e) {
            // Never let a metric call affect the request.
        }
    }

    private static function enabled(): bool
    {
        if (! get_option('bilge_enabled', false)) {
            return false;
        }
        if ((string) get_option('bilge_api_key', '') === '' || (string) get_option('bilge_base_url', '') === '') {
            return false;
        }

        return Bilge_Config::is_enabled('metrics');
    }

    private static function register_flush(): void
    {
        if (self::$flush_registered) {
            return;
        }
        self::$flush_registered = true;
        add_action('shutdown', [self::class, 'flush'], PHP_INT_MAX);
    }

    public static function flush(): void
    {
        try {
            if (self::$buffer === []) {
                return;
            }
            $entries = self::$buffer;
            self::$buffer = [];

            $environment = (string) get_option('bilge_environment', 'production');
            foreach ($entries as &$entry) {
                $entry['environment'] = $environment;
            }
            unset($entry);

            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            Bilge_Client::send_metrics(['entries' => $entries]);
        } catch (Throwable $e) {
            error_log('Bilge: metrics flush failed: '.$e->getMessage());
        }
    }
}

if (! function_exists('bilge_metric')) {
    /**
     * Record a custom metric (counter | gauge | distribution). Safe to call anywhere.
     *
     * @param  array<string, mixed>  $attributes
     */
    function bilge_metric(string $name, float|int $value = 1, string $kind = 'counter', ?string $unit = null, array $attributes = []): void
    {
        if (class_exists('Bilge_Metrics')) {
            Bilge_Metrics::record($name, (float) $value, $kind, $unit, $attributes);
        }
    }
}
