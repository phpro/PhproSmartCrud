<?php
/**
 * Smartcrud for Zend Framework (http://framework.zend.com/)
 *
 * @link http://github.com/veewee/PhproSmartCrud for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license http://framework.zend.com/license/new-bsd New BSD License
 */

namespace PhproSmartCrud\Controller;

use PhproSmartCrud\Exception\SmartCrudException;
use PhproSmartCrud\Service\CrudServiceInterface;
use Zend\Mvc\Controller\ControllerManager;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Mvc\Exception;

/**
 * Class AbstractCrudControllerFactory
 *
 * @package PhproSmartCrud\Controller
 */
class AbstractCrudControllerFactory
    implements AbstractFactoryInterface, ServiceLocatorAwareInterface
{

    const FACTORY_NAMESPACE = 'phpro-smartcrud-controller';

    const CONFIG_CONTROLLER = 'controller';
    const CONFIG_IDENTIFIER = 'identifier-name';
    const CONFIG_SMART_SERVICE = 'smart-service';

    /**
     * Cache of canCreateServiceWithName lookups
     * @var array
     */
    protected $lookupCache = array();

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var ControllerManager
     */
    protected $controllerManager;

    /**
     * @return array
     */
    public static function getDefaultConfiguration()
    {
        return array(
            self::CONFIG_CONTROLLER => 'PhproSmartCrud\Controller\CrudController',
            self::CONFIG_IDENTIFIER => 'id',
            self::CONFIG_SMART_SERVICE => 'PhproSmartCrud\Service\AbstractCrudService',
        );
    }

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * @param \Zend\Mvc\Controller\ControllerManager $controllerManager
     */
    public function setControllerManager($controllerManager)
    {
        $this->controllerManager = $controllerManager;
    }

    /**
     * @return \Zend\Mvc\Controller\ControllerManager
     */
    public function getControllerManager()
    {
        return $this->controllerManager;
    }

    /**
     * @return \Zend\Mvc\Router\RouteMatch
     * @throws \Zend\Mvc\Exception\DomainException
     */
    public function getRouteMatch()
    {
        $serviceManager = $this->getServiceLocator();
        /** @var \Zend\Mvc\Application $application */
        $application = $serviceManager->get('application');
        $mvcEvent = $application->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();

        if (!$routeMatch) {
            throw new Exception\DomainException('Missing route matches; unsure how to retrieve action');
        }
        return $routeMatch;
    }

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $name
     * @param                         $requestedName
     *
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $controllers, $name, $requestedName)
    {
        $this->setControllerManager($controllers);
        $this->setServiceLocator($controllers->getServiceLocator());
        $serviceLocator = $this->getServiceLocator();


        if (array_key_exists($requestedName, $this->lookupCache)) {
            return $this->lookupCache[$requestedName];
        }

        if (!$serviceLocator->has('Config')) {
            return false;
        }

        // Validate object is set
        $config = $serviceLocator->get('Config');
        $namespace = self::FACTORY_NAMESPACE;
        if (!isset($config[$namespace]) || !is_array($config[$namespace]) || !isset($config[$namespace][$requestedName])) {
            $this->lookupCache[$requestedName] = false;
            return false;
        }

        $this->lookupCache[$requestedName] = true;
        return true;
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $name
     * @param                         $requestedName
     *
     * @return \PhproSmartCrud\Controller\CrudControllerInterface
     * @throws SmartCrudException
     */
    public function createServiceWithName(ServiceLocatorInterface $controllers, $name, $requestedName)
    {
        $this->setControllerManager($controllers);
        $this->setServiceLocator($controllers->getServiceLocator());
        $serviceLocator = $this->getServiceLocator();

        $config   = $serviceLocator->get('Config');
        $config   = $config[self::FACTORY_NAMESPACE][$requestedName];
        $config     = array_merge($this->getDefaultConfiguration(), $config);

        $controller = $this->createController($config[self::CONFIG_CONTROLLER]);
        $this->injectDependencies($controller, $config);

        return $controller;

    }

    /**
     * @param $controllerName
     *
     * @return \PhproSmartCrud\Controller\CrudControllerInterface
     * @throws \PhproSmartCrud\Exception\SmartCrudException
     */
    public function createController($controllerName)
    {
        $controllerManager = $this->getControllerManager();
        if (!$controllerManager->has($controllerName)) {
            throw new SmartCrudException(sprintf('The controller %s could not be found.', $controllerName));
        }

        $controller = $controllerManager->get($controllerName);
        if (!($controller instanceof CrudControllerInterface)) {
            throw new SmartCrudException(sprintf('Invalid controller type for %s. It should implement CrudControllerInterface', $controllerName));
        }

        return $controller;
    }

    /**
     * @param CrudControllerInterface $controller
     * @param                         $config
     *
     * @return $this
     */
    protected function injectDependencies(CrudControllerInterface $controller, $config)
    {
        $this->injectSmartService($controller, $config[self::CONFIG_SMART_SERVICE]);
        $this->injectIdentifierName($controller, $config[self::CONFIG_IDENTIFIER]);

        return $this;
    }

    /**
     * @param CrudControllerInterface $controller
     * @param                         $smartServiceKey
     *
     * @return $this
     * @throws SmartCrudException
     */
    protected function injectSmartService(CrudControllerInterface $controller, $smartServiceKey)
    {
        $routeMatch = $this->getRouteMatch();
        $action = $routeMatch->getParam('action', 'not-found');
        $smartServiceKey = sprintf('%s::%s', $smartServiceKey, $action);

        $serviceManager = $this->getServiceLocator();
        if (!$serviceManager->has($smartServiceKey)) {
            throw new SmartCrudException(sprintf('Invalid smart service %s configured.', $smartServiceKey));
        }

        $smartService = $serviceManager->get($smartServiceKey);
        if (!($smartService instanceof CrudServiceInterface)) {
            throw new SmartCrudException(sprintf('Invalid type of smart service configured. %s needs to be a CrudServiceInterface.', $smartServiceKey));
        }

        $controller->setSmartService($smartService);

        return $this;
    }

    /**
     * @param CrudControllerInterface $controller
     * @param                         $identifier
     *
     * @return $this
     */
    protected function injectIdentifierName(CrudControllerInterface $controller, $identifier)
    {
        $controller->setIdentifierName($identifier);

        return $this;
    }

}
