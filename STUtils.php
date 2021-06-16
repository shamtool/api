<?php

class STUtils {
    /**
     * Normalizes a map code into an integer representation.
     */
    public static function parseMapCode(string|int|null $code) : ?int {
        if ($code == null) return null;
        if (is_int($code) && (int)$code >= 0) return (int)$code;
        $matches = null;
        preg_match("/^@(\d+)$/m", $code, $matches);
        $match = $matches[0] ?? null;
        if ($match == null) return null;
        return (int)$match;
    }

    /**
     * Retrieves query parameter (_GET, _POST, _REQUEST, etc), throwing an error when failed.
     * 
     * @throws Exception On failure
     */
    public static function queryToString(array $queryArr, string $param, bool $allowNull = true) : ?string {
        $value = $queryArr[$param] ?? null;
        if ($value == null) {
            if ($allowNull)
                return null;
            else
                throw new Exception("Expected String for \'{$param}\', got empty instead.");
        }

        if (is_string($value)) {
            return $value;
        }

        throw new Exception("Expected String for \'{$param}\', got '{$value}' instead."); 
    }

    /**
     * Converts a query parameter (_GET, _POST, _REQUEST, etc) to an integer, throwing an error when failed.
     * 
     * @throws Exception On failure
     */
    public static function queryToInt(array $queryArr, string $param, bool $allowNull = true) : ?int {
        $value = $queryArr[$param] ?? null;
        if ($value == null) {
            if ($allowNull)
                return null;
            else
                throw new Exception("Expected Integer for \'{$param}\', got empty instead.");
        }

        if (is_integer($value)) {
            return (int)$value;
        }

        throw new Exception("Expected Integer for \'{$param}\', got '{$value}' instead."); 
    }

    /**
     * Converts a query parameter (_GET, _POST, _REQUEST, etc) to an integer, throwing an error when failed.
     * 
     * @throws Exception On failure
     */
    public static function queryToFloat(array $queryArr, string $param, bool $allowNull = true) : ?float {
        $value = $queryArr[$param] ?? null;
        if ($value == null) {
            if ($allowNull)
                return null;
            else
                throw new Exception("Expected Float for \'{$param}\', got empty instead.");
        }

        if (is_float($value)) {
            return (float)$value;
        }

        throw new Exception("Expected Float for \'{$param}\', got '{$value}' instead."); 
    }
}
