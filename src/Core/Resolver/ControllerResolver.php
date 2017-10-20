<?php

namespace Core\Resolver;

use Core\Controller\CoreController;
use Core\Exception\InvalidArgumentException;
use Silex\Application;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;

class ControllerResolver extends BaseControllerResolver
{
    protected $app;

    /**
     * Constructor.
     *
     * @param Application     $app    An Application instance
     * @param LoggerInterface $logger A LoggerInterface instance
     */
    public function __construct(Application $app, LoggerInterface $logger = null)
    {
        $this->app = $app;

        parent::__construct($logger);
    }
    /**
     * Returns a callable for the given controller.
     *
     * @param string $controller A Controller string
     *
     * @return mixed A PHP callable
     * @throws InvalidArgumentException
     */
    protected function createController($controller)
    {
        if (false === strpos($controller, '::')) {
            throw new InvalidArgumentException(sprintf('Unable to find controller "%s".', $controller));
        }

        [$class, $method] = explode('::', $controller, 2);

        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }

        $controller = new $class();
        if ($controller instanceof CoreController) {
            $controller->setApp($this->app);
        }

        return [$controller, $method];
    }
}
