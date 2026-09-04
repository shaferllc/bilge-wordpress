<?php

/**
 * Standalone test for the WP plugin's release resolver (Lookout_Plugin::release()).
 * No WordPress runtime needed — get_option is stubbed off a $GLOBALS array.
 * Run: php packages/lookout-wordpress/tests/release-test.php
 */

declare(strict_types=1);

define('ABSPATH', __DIR__);

$GLOBALS['__options'] = [];

function get_option(string $key, $default = false)
{
    return $GLOBALS['__options'][$key] ?? $default;
}

require __DIR__.'/../includes/class-lookout-plugin.php';

$failures = 0;
$check = static function (string $label, bool $ok) use (&$failures): void {
    echo ($ok ? 'PASS' : 'FAIL').' — '.$label."\n";
    if (! $ok) {
        $failures++;
    }
};

// --- source-level assertions first: these print an explicit FAIL on the unfixed tree,
// rather than being skipped by the fatal that the runtime assertions below trigger. ---

$pluginSrc = (string) file_get_contents(__DIR__.'/../includes/class-lookout-plugin.php');
$uninstallSrc = (string) file_get_contents(__DIR__.'/../uninstall.php');

$resolverRefs = substr_count($pluginSrc, 'self::release()');
$check('payload sites reference the resolver at least 4 times (found '.$resolverRefs.')', $resolverRefs >= 4);

$check(
    'lookout_release is registered with an empty default',
    (bool) preg_match("/register_setting\\('lookout',\\s*'lookout_release',\\s*\\[.*?'default'\\s*=>\\s*''/s", $pluginSrc)
);

$check('uninstall.php deletes lookout_release', str_contains($uninstallSrc, "delete_option('lookout_release')"));

// --- runtime assertions against the static resolver. On the unfixed tree,
// Lookout_Plugin::release() does not exist and the first call below fatals with
// "Call to undefined method Lookout_Plugin::release()" — an acceptable RED signal,
// but it means the script terminates here without reaching the final OK/exit line. ---

$GLOBALS['__options'] = ['lookout_release' => '2026.09.01'];
$check('returns the option value when set', Lookout_Plugin::release() === '2026.09.01');

$GLOBALS['__options'] = [];
$check('returns empty string when the option is absent', Lookout_Plugin::release() === '');

$GLOBALS['__options'] = ['lookout_release' => ''];
$check('returns empty string when the option is empty string', Lookout_Plugin::release() === '');

$GLOBALS['__options'] = ['lookout_release' => "  \t \n"];
$check('returns empty string when the option is whitespace-only', Lookout_Plugin::release() === '');

$GLOBALS['__options'] = ['lookout_release' => 'from-option'];
define('LOOKOUT_RELEASE', 'from-constant');
$check('a defined LOOKOUT_RELEASE constant wins over the option', Lookout_Plugin::release() === 'from-constant');

echo $failures === 0 ? "\nOK\n" : "\n{$failures} FAILED\n";
exit($failures === 0 ? 0 : 1);
