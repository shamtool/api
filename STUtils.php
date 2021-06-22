<?php

class STUtils {
    /** @var array<string> */
    static private $acceptedImgHosts = [
        "imgur.com", "shamtool.com"
    ];

    /**
     * Normalizes a map code into an integer representation.
     */
    public static function parseMapCode(string|int|null $code) : ?int {
        if ($code === null) return null;
        if (is_numeric($code) && is_int((int)$code) && (int)$code >= 0) return (int)$code;
        $matches = null;
        preg_match("/^@(\d+)$/m", $code, $matches);
        $match = $matches[1] ?? null;
        if ($match === null) return null;
        return (int)$match;
    }

    /**
     * Retrieves query parameter (_GET, _POST, _REQUEST, etc), throwing an error when failed.
     *
     * @throws Exception On failure
     */
    public static function queryToString(array $queryArr, string $param, bool $allowNull = true) : ?string {
        $value = $queryArr[$param] ?? null;
        if ($value === null) {
            if ($allowNull)
                return null;
            else
                throw new Exception("Expected String for '{$param}', got empty instead.");
        }

        if (is_string($value)) {
            return $value;
        }

        throw new Exception("Expected String for '{$param}', got '{$value}' instead.");
    }

    /**
     * Converts a query parameter (_GET, _POST, _REQUEST, etc) to a boolean, throwing an error when failed.
     *
     * @throws Exception On failure
     */
    public static function queryToBool(array $queryArr, string $param, bool $allowNull = true) : ?bool {
        $value = $queryArr[$param] ?? null;
        if ($value === null) {
            if ($allowNull)
                return null;
            else
                throw new Exception("Expected Boolean for '{$param}', got empty instead.");
        }

        $result = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        if ($result === null)
            throw new Exception("Expected Boolean for '{$param}', got '{$value}' instead.");

        return $result;
    }

    /**
     * Converts a query parameter (_GET, _POST, _REQUEST, etc) to an integer, throwing an error when failed.
     *
     * @throws Exception On failure
     */
    public static function queryToInt(array $queryArr, string $param, bool $allowNull = true,
                                      int|null $min = null, int|null $max = null) : ?int {
        $value = $queryArr[$param] ?? null;
        if ($value === null) {
            if ($allowNull)
                return null;
            else
                throw new Exception("Expected Integer for '{$param}', got empty instead.");
        }

        if (is_numeric($value) && is_int((int)$value)) {
            $ret = (int)$value;
            if ($min !== null && $ret < $min)
                throw new Exception("Expected Integer with a minimum of {$min} for '{$param}', got '{$value}' instead.");
            if ($max !== null && $ret > $max)
                throw new Exception("Expected Integer with a maximum of {$min} for '{$param}', got '{$value}' instead.");

            return $ret;
        }

        throw new Exception("Expected Integer for '{$param}', got '{$value}' instead.");
    }

    /**
     * Converts a query parameter (_GET, _POST, _REQUEST, etc) to an integer, throwing an error when failed.
     *
     * @throws Exception On failure
     */
    public static function queryToFloat(array $queryArr, string $param, bool $allowNull = true) : ?float {
        $value = $queryArr[$param] ?? null;
        if ($value === null) {
            if ($allowNull)
                return null;
            else
                throw new Exception("Expected Float for '{$param}', got empty instead.");
        }

        if (is_float($value)) {
            return (float)$value;
        }

        throw new Exception("Expected Float for '{$param}', got '{$value}' instead.");
    }

    /**
     * Converts a query parameter (_GET, _POST, _REQUEST, etc) to an integer mapcode, throwing an error when failed.
     *
     * @throws Exception On failure
     */
    public static function queryToMapCode(array $queryArr, string $param, bool $allowNull = true) : ?int {
        $value = self::queryToString($queryArr, $param, $allowNull);
        if ($value === null)
            return null;

        $mapcode = self::parseMapCode($value);
        if ($mapcode === null)
            throw new Exception("Expected valid mapcode (e.g. @123456, 123456) for '{$param}', got '{$value}' instead.");

        return $mapcode;
    }

    /**
     * Converts a query parameter (_GET, _POST, _REQUEST, etc) to an accepted image URL string, throwing an error when failed.
     * Accepts only:
     * - `https` as protocol
     * - `shamtool.com`, `imgur.com` as host
     * - `.png` or `.jpg` file extensions.
     *
     * @throws Exception On failure
     */
    public static function queryToImgUrl(array $queryArr, string $param, bool $allowNull = true) : ?string {
        $value = self::queryToString($queryArr, $param, $allowNull);
        if ($value === null)
            return null;

        /** @var array|false */
        $url = parse_url($value);
        if (!$url)
            throw new Exception("Expected valid image URL for '{$param}', got malformed URL '{$value}' instead.");

        // Validate URL requirements
        if ($url['scheme'] != "https") {
            $scheme = $url['scheme'];
            throw new Exception("Expected https protocol for '{$param}', got '{$scheme}' instead.");
        }

        $matched_host = false;
        foreach (self::$acceptedImgHosts as $accepted) {
            if (self::endsWith($url['host'], $accepted)) {
                $matched_host = true;
                break;
            }
        }
        if (!$matched_host) {
            $host = $url['host'];
            $allowed = implode(",", self::$acceptedImgHosts);
            throw new Exception("Expected allowed hosts ({$allowed}) for '{$param}', got '{$host}' instead.");
        }

        if ($url['path'] === null ||
                (!self::endsWith($url['path'], '.png') &&
                !self::endsWith($url['path'], '.jpg'))) {
            throw new Exception("Expected '.png' or '.jpg' extension for '{$param}'.");
        }

        // URL has been validated
        return $value;
    }

    /**
     * Performs a case-sensitive check indicating if `haystack` ends with `needle`.
     * Provided for compatibility with pre-PHP 8.0 environments.
     */
    public static function endsWith(string $haystack, string $needle) : bool {
        if (version_compare(phpversion(), '8.0', '<')) {
            $length = strlen($needle);
            return $length > 0 ? substr($haystack, -$length) === $needle : true;
        }
        return str_ends_with($haystack, $needle);
    }
}
