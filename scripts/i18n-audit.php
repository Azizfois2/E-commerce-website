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
$locales = array_values(array_filter(
    function_exists('i18n_supported_locales') ? i18n_supported_locales() : ['en', 'fr', 'ar', 'es'],
    static fn(string $locale): bool => $locale !== $defaultLocale
));

$keys = collectAdminPhraseKeys($root);
$reportLines = [
    'Admin i18n audit',
    'Generated: ' . date('c'),
    'Root: ' . $root,
    'Keys scanned: ' . count($keys),
    '',
];

$hasMissing = false;
foreach ($locales as $locale) {
    $map = i18n_page_phrase_map($locale);
    $missing = array_values(array_filter(
        $keys,
        static fn(string $key): bool => !array_key_exists($key, $map)
    ));

    $reportLines[] = 'Missing in ' . strtoupper($locale) . ': ' . count($missing) . ' keys';
    foreach ($missing as $key) {
        $reportLines[] = '  - ' . $key;
    }
    $reportLines[] = '';

    if ($missing !== []) {
        $hasMissing = true;
    }
}

$reportPath = $reportPath ?? ($root . DIRECTORY_SEPARATOR . 'missing_translations_' . date('Y-m-d') . '.txt');
file_put_contents($reportPath, implode(PHP_EOL, $reportLines) . PHP_EOL);

if (!$quiet) {
    foreach ($reportLines as $line) {
        echo $line . PHP_EOL;
    }
    echo 'Output: ' . $reportPath . PHP_EOL;
}

exit($hasMissing ? 1 : 0);

/**
 * @return string[]
 */
function collectAdminPhraseKeys(string $root): array
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
                        'lib',
                        'mdfiles',
                        'node_modules',
                        'vendor',
                    ], true);
                }

                return shouldScanPhpFile($file);
            }
        )
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        foreach (extractAdminPhraseKeys((string) file_get_contents($file->getPathname())) as $key) {
            $keys[$key] = true;
        }
    }

    $keys = array_keys($keys);
    sort($keys, SORT_NATURAL | SORT_FLAG_CASE);

    return $keys;
}

function shouldScanPhpFile(SplFileInfo $file): bool
{
    if (strtolower($file->getExtension()) !== 'php') {
        return false;
    }

    $path = str_replace('\\', '/', $file->getPathname());
    $name = $file->getFilename();

    return str_starts_with($name, 'admin')
        || $name === 'dashboard.php'
        || str_ends_with($path, '/src/Services/admin-helpers.php');
}

/**
 * @return string[]
 */
function extractAdminPhraseKeys(string $source): array
{
    $tokens = token_get_all($source);
    $keys = [];
    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        if (!is_array($token) || $token[0] !== T_STRING || strcasecmp($token[1], 'adminPhrase') !== 0) {
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

        $keys[] = decodePhpStringToken($tokens[$firstArg][1]);
    }

    return $keys;
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
