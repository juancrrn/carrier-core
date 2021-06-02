<?php

namespace Carrier\Common\Controller;

use Carrier\Common\Http;

/**
 * Handles HTTP requests (front controller pattern)
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

class Controller
{

    private $pathBase = '';

    public function __construct(string $pathBase)
    {
        $this->pathBase = $pathBase;
    }

    /**
     * Processes any request regardless of the method
     * 
     * Alias for Controller::processRequest.
     * 
     * @param string $route
     * @param callable $handler
     */
    public function any(string $route, callable $handler): void
    {
        $this->processRequest($route, $handler);
    }

    /**
     * Processes a GET request
     * 
     * @param string $route
     * @param callable $handler
     */
    public function get(string $route, callable $handler): void
    {
        if (Http::isRequestMethod(Http::METHOD_GET))
            $this->processRequest($route, $handler);
    }

    /**
     * Processes a POST request
     * 
     * @param string $route
     * @param callable $handler
     */
    public function post(string $route, callable $handler): void
    {
        if (Http::isRequestMethod(Http::METHOD_POST))
            $this->processRequest($route, $handler);
    }

    /**
     * Processes a PUT request
     * 
     * @param string $route
     * @param callable $handler
     */
    public function put(string $route, callable $handler): void
    {
        if (Http::isRequestMethod(Http::METHOD_PUT))
            $this->processRequest($route, $handler);
    }

    /**
     * Processes a DELETE request
     * 
     * @param string $route
     * @param callable $handler
     */
    public function delete(string $route, callable $handler): void
    {
        if (Http::isRequestMethod(Http::METHOD_DELETE))
            $this->processRequest($route, $handler);
    }

    /**
     * Processes a PATCH request
     * 
     * @param string $route
     * @param callable $handler
     */
    public function patch(string $route, callable $handler): void
    {
        if (Http::isRequestMethod(Http::METHOD_PATCH))
            $this->processRequest($route, $handler);
    }

    /**
     * Processes a generic request
     * 
     * @param string $route
     * @param callable $handler
     */
    public function processRequest(string $route, callable $handler): void
    {
        $matches = [];

        if (Http::matchesRequestUri($this->pathBase, $route, $matches)) {
            echo call_user_func_array($handler, $matches);
            
            die();
        }
    }

    /**
     * Runs when no other route has been processed
     */
    public function default(callable $handler): void
    {
        http_response_code(404);
        
        echo call_user_func($handler);
    }
}