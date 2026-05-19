<?php
declare(strict_types=1);

/**
 * Load key=value pairs from a .env file.
 *
 * @param bool $overwriteExisting When true, values from this file replace existing getenv/$_ENV (for .env.local).
 */
function loadEnvFile(?string $path = null, bool $overwriteExisting = false): void
{
    $path ??= dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        if ($name === '') {
            continue;
        }
        $value = trim($value);
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if ($overwriteExisting && $value === '') {
            continue;
        }

        if (!$overwriteExisting && getenv($name) !== false) {
            continue;
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
    }
}

function envString(string $key, string $default = ''): string
{
    $v = $_ENV[$key] ?? getenv($key);
    if ($v === false || $v === null) {
        return $default;
    }
    return (string) $v;
}

function envBool(string $key, bool $default = false): bool
{
    $raw = strtolower(trim(envString($key, $default ? '1' : '0')));
    if (in_array($raw, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }
    if (in_array($raw, ['0', 'false', 'no', 'off', ''], true)) {
        return false;
    }
    return $default;
}
