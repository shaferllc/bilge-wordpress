<?php

/**
 * Local debug page ("Bilge's Ignition", WordPress edition): exception
 * headline, stack frames with source snippets read from disk, breadcrumbs,
 * and a copy-as-Markdown button. Renders entirely in-process — source lines
 * never leave the machine.
 *
 * Shown instead of PHP's bare fatal output when WP_DEBUG is on (override with
 * the `bilge_debug_page_enabled` filter). Never shown for AJAX/REST/CLI.
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

class Bilge_Debug_Page
{
    private const CONTEXT_RADIUS = 8;

    private const MAX_SOURCE_FRAMES = 12;

    public static function enabled(): bool
    {
        $default = defined('WP_DEBUG') && WP_DEBUG;
        if (PHP_SAPI === 'cli') {
            return false;
        }
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return false;
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }
        if (function_exists('apply_filters')) {
            return (bool) apply_filters('bilge_debug_page_enabled', $default);
        }

        return $default;
    }

    /**
     * @param  list<array<string, mixed>>  $breadcrumbs
     */
    public static function render(Throwable $e, array $breadcrumbs = []): string
    {
        $class = get_class($e);
        $message = $e->getMessage() !== '' ? $e->getMessage() : $class;

        $frames = [['file' => $e->getFile(), 'line' => $e->getLine(), 'function' => '{thrown}']];
        foreach (array_slice($e->getTrace(), 0, 60) as $frame) {
            $frames[] = [
                'file' => isset($frame['file']) && is_string($frame['file']) ? $frame['file'] : '[internal]',
                'line' => isset($frame['line']) ? (int) $frame['line'] : 0,
                'function' => (isset($frame['class']) ? $frame['class'].($frame['type'] ?? '::') : '').($frame['function'] ?? ''),
            ];
        }

        $markdown = self::markdown($class, $message, $frames, $breadcrumbs);

        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            .'<meta name="robots" content="noindex,nofollow"><meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>'.self::h($class).' — Bilge</title><style>'
            .':root{--bg:#0f1115;--panel:#171a21;--line:#262c38;--text:#e6e9ef;--muted:#8b93a7;--dim:#5b6376;--accent:#f0506e;--accent-soft:#3a1f2a;--amber-soft:#3a2f1a;--code:#c9d1e4}'
            .'html,body{margin:0;background:var(--bg);color:var(--text);font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}'
            .'code,pre{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}'
            .'.wrap{max-width:1100px;margin:0 auto;padding:24px 20px 80px}'
            .'.top{border-left:4px solid var(--accent);background:linear-gradient(90deg,var(--accent-soft),transparent 60%);padding:16px 18px;border-radius:8px;margin-bottom:18px}'
            .'.cls{color:var(--accent);font-size:15px;font-weight:600;margin-bottom:4px}'
            .'h1{margin:0;font-size:20px;word-break:break-word}'
            .'.btn{display:inline-block;margin-top:12px;padding:6px 12px;border-radius:6px;border:1px solid var(--line);background:var(--panel);color:var(--text);font-weight:600;font-size:12px;cursor:pointer}'
            .'.frame{background:var(--panel);border:1px solid var(--line);border-radius:8px;margin-bottom:10px;overflow:hidden}'
            .'.frame .hd{padding:8px 12px;color:var(--muted);font-size:12px;border-bottom:1px solid var(--line);word-break:break-all}'
            .'pre.code{margin:0;overflow-x:auto;font-size:12.5px;color:var(--code);padding:8px 0}'
            .'pre.code span{display:block;padding:0 12px;white-space:pre}'
            .'pre.code span.hl{background:var(--amber-soft)}'
            .'details{background:var(--panel);border:1px solid var(--line);border-radius:8px;margin-top:12px}'
            .'summary{padding:10px 14px;cursor:pointer;font-weight:600;font-size:13px}'
            .'.crumb{display:flex;gap:10px;padding:6px 14px;border-top:1px solid var(--line);font-size:12px}'
            .'.crumb .lvl{min-width:56px;color:var(--dim);text-transform:uppercase;font-size:10px}'
            .'.crumb .cat{color:var(--muted);min-width:110px}'
            .'.crumb .msg{color:var(--code);word-break:break-word}'
            .'.foot{margin-top:26px;color:var(--dim);font-size:11px;text-align:center}'
            .'</style></head><body><div class="wrap">'
            .'<div class="top"><div class="cls">'.self::h($class).'</div><h1>'.self::h($message).'</h1>'
            .'<button class="btn" id="lk-md" type="button">Copy as Markdown</button></div>'
            .self::frames_html($frames)
            .self::breadcrumbs_html($breadcrumbs)
            .'<div class="foot">Rendered locally by Bilge · source snippets never leave this machine</div></div>'
            .'<script type="application/json" id="lk-copy-data">'.self::json_island($markdown).'</script>'
            .'<script>(function(){var d={};try{d=JSON.parse(document.getElementById("lk-copy-data").textContent||"{}")}catch(e){}'
            .'var b=document.getElementById("lk-md");if(b){b.addEventListener("click",function(){'
            .'(navigator.clipboard?navigator.clipboard.writeText(d.markdown||""):Promise.reject()).then(function(){'
            .'b.textContent="Copied ✓";setTimeout(function(){b.textContent="Copy as Markdown"},1500)}).catch(function(){})})}})();</script>'
            .'</body></html>';

        return $html;
    }

    /**
     * @param  list<array{file: string, line: int, function: string}>  $frames
     */
    private static function frames_html(array $frames): string
    {
        $out = '';
        $enriched = 0;
        foreach ($frames as $frame) {
            $body = '';
            if ($enriched < self::MAX_SOURCE_FRAMES) {
                $window = self::source_window($frame['file'], $frame['line']);
                if ($window !== null) {
                    $enriched++;
                    $rows = '';
                    $n = $window['from'];
                    foreach ($window['lines'] as $text) {
                        $rows .= '<span class="'.($n === $frame['line'] ? 'hl' : '').'">'
                            .str_pad((string) $n, 5, ' ', STR_PAD_LEFT).'  '.self::h($text).'</span>';
                        $n++;
                    }
                    $body = '<pre class="code">'.$rows.'</pre>';
                }
            }
            $out .= '<div class="frame"><div class="hd">'.self::h($frame['file']).':'.$frame['line']
                .' '.self::h($frame['function']).'</div>'.$body.'</div>';
        }

        return $out;
    }

    /**
     * @return array{from: int, lines: list<string>}|null
     */
    private static function source_window(string $file, int $line): ?array
    {
        if ($line < 1 || ! is_file($file) || ! is_readable($file)) {
            return null;
        }
        $size = @filesize($file);
        if ($size === false || $size > 2_000_000) {
            return null;
        }
        $lines = @file($file, FILE_IGNORE_NEW_LINES);
        if (! is_array($lines) || $line > count($lines)) {
            return null;
        }
        $from = max(0, $line - 1 - self::CONTEXT_RADIUS);
        $to = min(count($lines) - 1, $line - 1 + self::CONTEXT_RADIUS);

        return ['from' => $from + 1, 'lines' => array_slice($lines, $from, $to - $from + 1)];
    }

    /**
     * @param  list<array<string, mixed>>  $breadcrumbs
     */
    private static function breadcrumbs_html(array $breadcrumbs): string
    {
        if ($breadcrumbs === []) {
            return '';
        }
        $rows = '';
        foreach (array_reverse($breadcrumbs) as $crumb) {
            if (! is_array($crumb)) {
                continue;
            }
            $rows .= '<div class="crumb"><span class="lvl">'.self::h((string) ($crumb['level'] ?? 'info')).'</span>'
                .'<span class="cat">'.self::h((string) ($crumb['category'] ?? '')).'</span>'
                .'<span class="msg">'.self::h(is_string($crumb['message'] ?? null) ? $crumb['message'] : '').'</span></div>';
        }

        return '<details open><summary>Breadcrumbs ('.count($breadcrumbs).')</summary>'.$rows.'</details>';
    }

    /**
     * @param  list<array{file: string, line: int, function: string}>  $frames
     * @param  list<array<string, mixed>>  $breadcrumbs
     */
    private static function markdown(string $class, string $message, array $frames, array $breadcrumbs): string
    {
        $lines = ['# '.$class.': '.$message, '', '## Stack trace', '', '```'];
        foreach ($frames as $i => $frame) {
            $lines[] = '#'.$i.' '.$frame['file'].':'.$frame['line'].'  '.$frame['function'];
        }
        $lines[] = '```';
        $lines[] = '';
        if ($breadcrumbs !== []) {
            $lines[] = '## Breadcrumbs ('.count($breadcrumbs).')';
            $lines[] = '';
            foreach (array_reverse($breadcrumbs) as $crumb) {
                if (is_array($crumb)) {
                    $lines[] = '- `'.($crumb['level'] ?? 'info').'` **'.($crumb['category'] ?? '').'** '
                        .(is_string($crumb['message'] ?? null) ? $crumb['message'] : '');
                }
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private static function json_island(string $markdown): string
    {
        $encoded = json_encode(['markdown' => $markdown], JSON_HEX_TAG | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE);

        return $encoded === false ? '{}' : $encoded;
    }

    private static function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
