<?php

namespace LoxBerryPlugin\Core\Frontend\Routing;

use LoxBerryPlugin\Core\Exception\RouteNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

/**
 * Class RouteMatcher.
 */
class RouteMatcher
{
    const ROUTING_CONFIGURATION = '/config/routes.yaml';

    /** @var array */
    private $routes;

    /** @var Request */
    private $request;

    /**
     * RouteMatcher constructor.
     *
     * @param $rootPath
     */
    public function __construct($rootPath, RoutingConfigurationParser $routingConfigurationParser)
    {
        $this->routes = $routingConfigurationParser->getConfiguration();
        $this->request = Request::createFromGlobals();
    }

    /**
     * @param string $route
     * @param bool   $isPublic
     *
     * @return PageRouteConfiguration
     */
    public function getMatchedRoute(bool $isPublic): PageRouteConfiguration
    {
        $routes = $this->routes[!$isPublic ? 'admin' : 'public'] ?? [];

        foreach ($routes as $routeName => $configuration) {
            if ($this->isCurrentMatchedRoute($routeName, $isPublic)) {
                $configuration = new PageRouteConfiguration();
                $configuration->setControllerClassName($configuration['controller']);
                $configuration->setMethod($this->request->getMethod());
                $configuration->setAction($configuration['action']);
                $configuration->setRoute($configuration['route']);

                return $configuration;
            }
        }

        throw new RouteNotFoundException('No route configuration matches this request.');
    }

    /**
     * @param string $routeName
     * @param bool $isPublic
     *
     * @return bool
     */
    public function isCurrentMatchedRoute(string $routeName, bool $isPublic = false): bool
    {
        $configuredRoute = $this->routes[!$isPublic ? 'admin' : 'public'][$routeName] ?? null;
        if ($configuredRoute === null) {
            return false;
        }

        $currentRoute = $this->request->query->get('route', '');

        $allowedMethods = explode(',', $configuredRoute['method'] ?? Request::METHOD_GET);
        $requestMethod = strtolower($this->request->getMethod());

        if (
            trim($configuredRoute['route'], '/') === trim($currentRoute, '/') &&
            in_array($requestMethod, array_map('trim', array_map('strtolower', $allowedMethods)))
        ) {
            return true;
        }

        return false;
    }
}