<?php
/*
   Copyright 2016-2019 Thomas Bollmeier <developer@thomas-bollmeier.de>

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

namespace tbollmeier\webappfound\routing;


class Router
{

    private $handlers;
    private $controllerNS;
    private $defaultCtrl;
    private $defaultAction;
    private $baseUrl;

    public function __construct($options)
    {
        $this->controllerNS = $options['controllerNS'] ?? '';
        $defaultCtrlAction = $options['defaultCtrlAction'] ?? 'Index.index';
        list($defaultCtrl, $defaultAction) = explode('.', $defaultCtrlAction);
        $this->defaultCtrl = $this->controllerNS . '\\' . $defaultCtrl;
        $this->defaultAction = $defaultAction;
        $this->baseUrl = $options['baseUrl'] ?? '';
        $this->handlers = [];
    }

    /**
     * Setup handlers
     *
     * @param RouterData $data
     */
    public function setupHandlers(RouterData $data)
    {
        $this->handlers = [];

        foreach ($data->controllers as $controller) {
            foreach ($controller->actions as $action) {
                $handlers = isset($this->handlers[$action->httpMethod]) ?
                    $this->handlers[$action->httpMethod] :
                    [];
                $handlers[] = [
                    $action->pattern,
                    $action->paramNames,
                    $controller->name,
                    $action->name];
                $this->handlers[$action->httpMethod] = $handlers;
            }
        }
    }

    /**
     * Route url to controller action
     *
     * @param string $method HTTP method ('GET, 'PUT', etc.)
     * @param string $url URL to be parsed (including query string)
     * @throws \Exception
     */
    public function route($method, $url)
    {
        // separate url path and query parameters:
        $urlInfo = parse_url($url);
        $url = $urlInfo['path'];
        $query = $urlInfo['query'] ?? '';
        $queryParams = [];
        if (!empty($query)) {
            parse_str($query, $queryParams);
        }

        $handlers = isset($this->handlers[$method]) ?
            $this->handlers[$method] :
            [];

        $urlParams = [];

        foreach ($handlers as $handler) {
            list($pattern, $params, $controller, $action) = $handler;
            $controller = $this->controllerNS . '\\' . $controller;
            if (preg_match($pattern, $url, $matches)) {
                $numParams = count($params);
                for ($paramIdx=0; $paramIdx < $numParams; $paramIdx++) {
                    $urlParams[$params[$paramIdx]] = $matches[$paramIdx+1];
                }
                $this->callAction(
                    new $controller(),
                    $action,
                    $urlParams,
                    $queryParams);
                return;
            }
        }

        // Fallback:
        $this->callAction(
            new $this->defaultCtrl(),
            $this->defaultAction,
            $urlParams,
            $queryParams);
    }

    private function callAction($controller, $action, $urlParams, $queryParams)
    {
        $method = new \ReflectionMethod($controller, $action);
        $numRequired = $method->getNumberOfRequiredParameters();
        if ($numRequired > 2) {
            throw new \Exception('Not all arguments given!');
        }
        $numParams = $method->getNumberOfParameters();
        switch ($numParams) {
            case 0:
                $args = [];
                break;
            case 1:
                $args = [$urlParams];
                break;
            case 2:
                $args = [$urlParams, $queryParams];
                break;
        }

        call_user_func_array([$controller, $action], $args);
    }

    /**
     * Register controller actions for routes
     *
     * @param $filePathOrCode
     * @throws \Exception
     */
    public function registerActionsFromDSL($filePathOrCode)
    {
        $factory = new RouterFactory();

        $router = $factory->createFromDSL(
            $filePathOrCode,
            $this->baseUrl,
            $this->controllerNS);

        $this->defaultCtrl = $router->defaultCtrl;
        $this->defaultAction = $router->defaultAction;
        $this->handlers = $router->handlers;
    }

    /**
     * Register multiple action routes
     *
     * @param $routeData string String of route information
     *                          in format "HTTP_METHOD URL Controller.Action, ..."
     */
    public function registerActions($routeData)
    {
        $lines = explode(',', $routeData);

        foreach ($lines as $line) {

            $args = explode(' ', $line);
            $args = array_map(function($arg) {
                return trim($arg);
            }, $args);
            $args = array_filter($args, function ($arg) {
                return !empty($arg);
            });

            if (count($args) == 3) {
                list($method, $route, $controllerAction) = $args;
                $this->registerAction($method, $route, $controllerAction);
            }

        }
    }

    public function registerAction($method,
                                   $route,
                                   $controllerAction)
    {
        list($controller, $action) = explode('.', $controllerAction);

        $route = '/' . trim($this->baseUrl, '/') . '/' . ltrim($route, '/');
        list($pattern, $params) = $this->parseRoute($route);

        $handlers = isset($this->handlers[$method]) ?
            $this->handlers[$method] :
            [];
        $handlers[] = [$pattern, $params, $controller, $action];
        $this->handlers[$method] = $handlers;
    }

    private function parseRoute($route)
    {
        $segments = [];
        $pattern = '';
        $params = [];

        preg_match_all("/([^\\/]+)*/", $route, $matches);

        if (!empty($matches)) {

            foreach ($matches[0] as $match) {
                if (empty($match)) continue;
                if ($match[0] != ':') {
                    $segments[] = $match;
                } else {
                    $segments[] = "([^\\/]+)";
                    $params[] = substr($match, 1);
                }
            }

            $pattern = '/^\\/?' . implode('\\/', $segments) . '\\/?$/';

        }

        return [$pattern, $params];
    }

}