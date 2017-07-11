<?php

namespace Core\Resolver;

use Core\Exception\AppException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RoutesResolver
{
    /**
     * RoutesResolver constructor.
     */
    public function __construct()
    {
        if (!function_exists('yaml_parse')) {
            throw new AppException('Yaml extension not installed');
        }
    }

    /**
     * Parse Routes From Yaml File
     *
     * @param RouteCollection $routes
     * @param string          $yamlFilePath
     *
     * @return RouteCollection
     */
    public function parseRoutesFromYamlFile(RouteCollection $routes, string $yamlFilePath): RouteCollection
    {
        $collection = new RouteCollection();
        $parsedConfig = yaml_parse(file_get_contents($yamlFilePath));
        foreach ($parsedConfig as $name => $config) {
            $this->parseRoute($collection, $name, $config);
        }

        $routes->addCollection($collection);

        return $routes;
    }

    /**
     * Parses a route and adds it to the RouteCollection.
     *
     * @param RouteCollection $collection A RouteCollection instance
     * @param string          $name       Route name
     * @param array           $config     Route definition
     */
    private function parseRoute(RouteCollection $collection, $name, array $config)
    {
        $defaults = isset($config['defaults']) ? $config['defaults'] : array();
        $requirements = isset($config['requirements']) ? $config['requirements'] : array();
        $options = isset($config['options']) ? $config['options'] : array();
        $host = isset($config['host']) ? $config['host'] : '';
        $schemes = isset($config['schemes']) ? $config['schemes'] : array();
        $methods = isset($config['methods']) ? $config['methods'] : array();
        $condition = isset($config['condition']) ? $config['condition'] : null;

        $route = new Route($config['path'], $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $collection->add($name, $route);
    }
}
