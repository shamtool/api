<?php

/**
 * Represents the shamtool API router.
 */
class STRouter extends \Bramus\Router\Router {
    /** @var callable */
    private $forbiddenCallback = null;

    private static ?STRouter $instance = null;

    private function __construct() {}

    /**
     * Set the 403 (Forbidden) handling function.
     *
     * @param callable $fn The function to be executed
     */
    public function set403(callable $fn) {
        $this->forbiddenCallback = $fn;
    }

    /**
     * Triggers 403 (Forbidden) response
     * 
     * @param boolean? $quit Whether to additionally exit serving the request.
     */
    public function trigger403(?bool $quit = false) {
        if ($this->forbiddenCallback) {
            call_user_func($this->forbiddenCallback);
        } else {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
        }
        if ($quit == true) {
            exit(403);
        }
    }

    public static function getInstance() : STRouter {
        if (self::$instance == null) {
            self::$instance = new STRouter();
        }
        return self::$instance;
    }
}
