<?php

/**
 * Standalone test for the WP local debug page renderer.
 * No WordPress runtime needed — only ABSPATH is defined.
 * Run: php packages/lookout-wordpress/tests/debug-page-test.php
 */

declare(strict_types=1);

define('ABSPATH', __DIR__);

require __DIR__.'/../includes/class-lookout-debug-page.php';

$failures = 0;
$check = static function (string $label, bool $ok) use (&$failures): void {
    echo ($ok ? 'PASS' : 'FAIL').' — '.$label."\n";
    if (! $ok) {
        $failures++;
    }
};

function lookout_test_boom(): Throwable
{
    try {
        throw new RuntimeException('post 42 could not be published');
    } catch (RuntimeException $e) {
        return $e;
    }
}

$crumbs = [
    ['level' => 'info', 'category' => 'hook', 'message' => 'crumb-'.'older'],
    ['level' => 'debug', 'category' => 'db', 'message' => 'crumb-'.'newer'],
];

$html = Lookout_Debug_Page::render(lookout_test_boom(), $crumbs);

$check('headline class + message', str_contains($html, 'RuntimeException') && str_contains($html, 'post 42 could not be published'));
$check('source snippet from this file', str_contains($html, 'could not be published&#039;') || str_contains($html, 'could not be published&apos;'));
$check('frame header shows file:line', str_contains($html, 'debug-page-test.php'));
$check('breadcrumbs render', str_contains($html, 'Breadcrumbs (2)'));
$check('breadcrumbs newest first', strpos($html, 'crumb-'.'newer') < strpos($html, 'crumb-'.'older'));
$check('markdown copy island present', str_contains($html, 'lk-copy-data') && str_contains($html, 'Copy as Markdown'));
$check('markdown island escapes script breakout', ! str_contains(explode('lk-copy-data', $html)[1] ?? '', '</script>'.'>'));

$decoded = json_decode(trim(explode('</script>', explode('id="lk-copy-data">', $html)[1] ?? '')[0] ?? ''), true);
$check('island decodes with stack + crumbs', is_array($decoded)
    && str_contains((string) $decoded['markdown'], '# RuntimeException: post 42 could not be published')
    && str_contains((string) $decoded['markdown'], 'crumb-'.'newer'));

// CLI never shows the page (this test runs under CLI, so enabled() must be false even with WP_DEBUG).
define('WP_DEBUG', true);
$check('disabled under CLI', Lookout_Debug_Page::enabled() === false);

echo $failures === 0 ? "\nAll debug-page tests passed\n" : "\n{$failures} failure(s)\n";
exit($failures === 0 ? 0 : 1);
