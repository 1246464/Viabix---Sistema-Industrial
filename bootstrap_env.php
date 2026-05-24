<?php

if (!function_exists('viabix_put_env')) {
    function viabix_put_env($name, $value) {
        $normalizedValue = (string) $value;

        $_ENV[$name] = $normalizedValue;
        $_SERVER[$name] = $normalizedValue;

        if (getenv($name) === false) {
            putenv($name . '=' . $normalizedValue);
        }
    }

    function viabix_load_env_file($filePath) {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '' || $trimmedLine[0] === '#') {
                continue;
            }

            $separatorPosition = strpos($trimmedLine, '=');
            if ($separatorPosition === false) {
                continue;
            }

            $name = trim(substr($trimmedLine, 0, $separatorPosition));
            $value = trim(substr($trimmedLine, $separatorPosition + 1));

            if ($name === '' || getenv($name) !== false || isset($_ENV[$name]) || isset($_SERVER[$name])) {
                continue;
            }

            $valueLength = strlen($value);
            if ($valueLength >= 2) {
                $firstChar = $value[0];
                $lastChar = $value[$valueLength - 1];
                if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            viabix_put_env($name, $value);
        }
    }

    function viabix_bootstrap_env() {
        static $loaded = false;

        if ($loaded) {
            return;
        }

        $rootDir = __DIR__;
        viabix_load_env_file($rootDir . DIRECTORY_SEPARATOR . '.env');
        viabix_load_env_file($rootDir . DIRECTORY_SEPARATOR . '.env.local');

        $loaded = true;
    }

    function viabix_env($name, $default = null) {
        viabix_bootstrap_env();

        if (array_key_exists($name, $_ENV) && $_ENV[$name] !== '') {
            return $_ENV[$name];
        }

        if (array_key_exists($name, $_SERVER) && $_SERVER[$name] !== '') {
            return $_SERVER[$name];
        }

        $value = getenv($name);
        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }

    function viabix_env_bool($name, $default = false) {
        $value = viabix_env($name, null);

        if ($value === null) {
            return (bool) $default;
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return (bool) $default;
    }

    function viabix_request_is_https() {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        if ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443) {
            return true;
        }

        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
}

viabix_bootstrap_env();