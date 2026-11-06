<?php

/**
 * Standalone test for the WP plugin's release resolver (Bilge_Plugin::release()).
 * No WordPress runtime needed — get_option is stubbed off a $GLOBALS array.
 * Run: php packages/bilge-wordpress/tests/release-test.php
 */

declare(strict_types=1);

define('ABSPATH', __DIR__);

$GLOBALS['__options'] = [];

function get_option(string $key, $default = false)
{
    return $GLOBALS['__options'][$key] ?? $default;
}

require __DIR__.'/../includes/class-bilge-plugin.php';

$failures = 0;
$check = static function (string $label, bool $ok) use (&$failures): void {
    echo ($ok ? 'PASS' : 'FAIL').' — '.$label."\n";
    if (! $ok) {
        $failures++;
    }
};

// --- source-level assertions first: these print an explicit FAIL on the unfixed tree,
// rather than being skipped by the fatal that the runtime assertions below trigger. ---

$pluginSrc = (string) file_get_contents(__DIR__.'/../includes/class-bilge-plugin.php');
$uninstallSrc = (string) file_get_contents(__DIR__.'/../uninstall.php');

$resolverRefs = substr_count($pluginSrc, 'self::release()');
$check('payload sites reference the resolver at least 4 times (found '.$resolverRefs.')', $resolverRefs >= 4);

$check(
    'bilge_release is registered with an empty default',
    (bool) preg_match("/register_setting\\('bilge',\\s*'bilge_release',\\s*\\[.*?'default'\\s*=>\\s*''/s", $pluginSrc)
);

$check('uninstall.php deletes bilge_release', str_contains($uninstallSrc, "delete_option('bilge_release')"));

// --- runtime assertions against the static resolver. On the unfixed tree,
// Bilge_Plugin::release() does not exist and the first call below fatals with
// "Call to undefined method Bilge_Plugin::release()" — an acceptable RED signal,
// but it means the script terminates here without reaching the final OK/exit line. ---

$GLOBALS['__options'] = ['bilge_release' => '2026.09.01'];
$check('returns the option value when set', Bilge_Plugin::release() === '2026.09.01');

$GLOBALS['__options'] = [];
$check('returns empty string when the option is absent', Bilge_Plugin::release() === '');

$GLOBALS['__options'] = ['bilge_release' => ''];
$check('returns empty string when the option is empty string', Bilge_Plugin::release() === '');

$GLOBALS['__options'] = ['bilge_release' => "  \t \n"];
$check('returns empty string when the option is whitespace-only', Bilge_Plugin::release() === '');

$GLOBALS['__options'] = ['bilge_release' => 'from-option'];
define('BILGE_RELEASE', 'from-constant');
$check('a defined BILGE_RELEASE constant wins over the option', Bilge_Plugin::release() === 'from-constant');

echo $failures === 0 ? "\nOK\n" : "\n{$failures} FAILED\n";
exit($failures === 0 ? 0 : 1);
