<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/i18n.php';

$root = dirname(__DIR__);
$quiet = in_array('--quiet', $argv, true);
$reportPath = null;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg !== '--quiet') {
        $reportPath = $arg;
        break;
    }
}

$defaultLocale = defined('I18N_DEFAULT_LOCALE') ? I18N_DEFAULT_LOCALE : 'en';
$locales = function_exists('i18n_supported_locales') ? i18n_supported_locales() : ['en', 'fr', 'ar', 'es'];
$keys = collectCatalogKeys($root);

$reportLines = [
    'Catalog i18n audit',
    'Generated: ' . date('c'),
    'Root: ' . $root,
    'Keys scanned: ' . count($keys),
    '',
];

$hasMissing = false;
foreach ($locales as $locale) {
    $catalog = i18n_catalog($locale);
    $missing = [];
    foreach ($keys as $key => $locations) {
        if (i18n_lookup($catalog, $key) !== null) {
            continue;
        }

        $missing[$key] = $locations;
    }

    $reportLines[] = 'Missing in ' . strtoupper($locale) . ': ' . count($missing) . ' keys';
    foreach ($missing as $key => $locations) {
        $reportLines[] = '  - ' . $key . ' [' . implode(', ', array_slice($locations, 0, 3)) . (count($locations) > 3 ? ', ...' : '') . ']';
    }
    $reportLines[] = '';

    if ($missing !== []) {
        $hasMissing = true;
    }
}

$reportPath = $reportPath ?? ($root . DIRECTORY_SEPARATOR . 'missing_catalog_translations_' . date('Y-m-d') . '.txt');
file_put_contents($reportPath, implode(PHP_EOL, $reportLines) . PHP_EOL);

if (!$quiet) {
    foreach ($reportLines as $line) {
        echo $line . PHP_EOL;
    }
    echo 'Output: ' . $reportPath . PHP_EOL;
}

exit($hasMissing ? 1 : 0);

/**
 * @return array<string,string[]>
 */
function collectCatalogKeys(string $root): array
{
    $keys = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $file): bool {
                $name = $file->getFilename();
                if ($file->isDir()) {
                    return !in_array($name, [
                        '.git',
                        '.cursor',
                        '.gemini',
                        '__brave_tmp',
                        'Images',
                        'impeccable-main',
                        'lang',
                        'lib',
                        'mdfiles',
                        'node_modules',
                        'scripts',
                        'vendor',
                    ], true);
                }

                return shouldScanCatalogPhpFile($file);
            }
        )
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        foreach (extractCatalogKeys((string) file_get_contents($file->getPathname())) as $key) {
            $keys[$key][$relativePath] = true;
        }
    }

    ksort($keys, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($keys as $key => $locations) {
        $keys[$key] = array_keys($locations);
        sort($keys[$key], SORT_NATURAL | SORT_FLAG_CASE);
    }

    return $keys;
}

function shouldScanCatalogPhpFile(SplFileInfo $file): bool
{
    if (strtolower($file->getExtension()) !== 'php') {
        return false;
    }

    $path = str_replace('\\', '/', $file->getPathname());
    $name = $file->getFilename();

    if ($name === 'dashboard.php' || str_starts_with($name, 'admin')) {
        return false;
    }

    return !str_ends_with($path, '/src/Services/admin-helpers.php')
        && !str_ends_with($path, '/includes/i18n.php')
        && !str_contains($path, '/lang/')
        && !str_contains($path, '/scripts/');
}

/**
 * @return string[]
 */
function extractCatalogKeys(string $source): array
{
    $tokens = token_get_all($source);
    $keys = [];
    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        if (!is_array($token) || $token[0] !== T_STRING || !in_array($token[1], ['i18n_t', 'i18n_e'], true)) {
            continue;
        }

        $openParen = nextMeaningfulTokenIndex($tokens, $i + 1);
        if ($openParen === null || $tokens[$openParen] !== '(') {
            continue;
        }

        $firstArg = nextMeaningfulTokenIndex($tokens, $openParen + 1);
        if ($firstArg === null || !is_array($tokens[$firstArg]) || $tokens[$firstArg][0] !== T_CONSTANT_ENCAPSED_STRING) {
            continue;
        }

        $afterFirstArg = nextMeaningfulTokenIndex($tokens, $firstArg + 1);
        if ($afterFirstArg !== null && $tokens[$afterFirstArg] === '.') {
            continue;
        }

        $keys[] = decodePhpStringToken($tokens[$firstArg][1]);
    }

    return array_values(array_unique($keys));
}

function nextMeaningfulTokenIndex(array $tokens, int $start): ?int
{
    $count = count($tokens);
    for ($i = $start; $i < $count; $i++) {
        $token = $tokens[$i];
        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        return $i;
    }

    return null;
}

function decodePhpStringToken(string $token): string
{
    $quote = $token[0] ?? "'";
    $body = substr($token, 1, -1);

    if ($quote === "'") {
        return str_replace(["\\\\", "\\'"], ["\\", "'"], $body);
    }

    return stripcslashes($body);
}
