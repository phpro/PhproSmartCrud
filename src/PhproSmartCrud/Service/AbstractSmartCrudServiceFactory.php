<?php

namespace PhproSmartCrud\Service;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use \PhproSmartCrud\Service\CrudServiceInterface;
use Zend\Stdlib\ArrayObject;

/**
 * Class CrudServiceFactory
 *
 * @package PhproSmartCrud\Service
 */
class AbstractSmartCrudServiceFactory
    implements AbstractFactoryInterface, ServiceLocatorAwareInterface
{

    /**
     * The config key in the service manager
     */
    const CONFIG_KEY = 'phpro-smartcrud-service';

    const CONFIG_ENTITY_CLASS   = 'entity-class';
    const CONFIG_PARAMETERS_KEY = 'parameters';
    const CONFIG_GATEWAY_KEY    = 'gateway';
    const CONFIG_FORM_KEY       = 'form';
    const CONFIG_OUTPUT_MODEL   = 'output-model';
    const CONFIG_LISTENERS_KEY  = 'listeners';
    const CONFIG_SERVICE_KEY    = 'service';

    const CONFIG_DEFAULT        = 'default';
    const CONFIG_CREATE         = 'create';
    const CONFIG_UPDATE         = 'update';
    const CONFIG_DELETE         = 'delete';
    const CONFIG_LIST           = 'list';
    const CONFIG_READ           = 'read';

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    public static function getDefaultConfiguration()
    {
        return array(
            self::CONFIG_DEFAULT => array(
                self::CONFIG_GATEWAY_KEY => null,
                self::CONFIG_ENTITY_CLASS => null,
                self::CONFIG_FORM_KEY     => null,
                self::CONFIG_OUTPUT_MODEL => 'PhproSmartCrud\View\Model\ViewModel',
                AbstractSmartCrudServiceFactory::CONFIG_LISTENERS_KEY => array()
            ),
            self::CONFIG_LIST => array(
                self::CONFIG_SERVICE_KEY => '\PhproSmartCrud\Service\ListService'
            ),
            self::CONFIG_CREATE => array(
                self::CONFIG_SERVICE_KEY => '\PhproSmartCrud\Service\CreateService'
            ),
            self::CONFIG_READ => array(
                self::CONFIG_SERVICE_KEY => '\PhproSmartCrud\Service\ReadService'
            ),
            self::CONFIG_UPDATE => array(
                self::CONFIG_SERVICE_KEY => '\PhproSmartCrud\Service\UpdateService'
            ),
            self::CONFIG_DELETE => array(
                self::CONFIG_SERVICE_KEY => '\PhproSmartCrud\Service\DeleteService'
            ),
        );
    }

    /**
     * Load smartcrud config from the getServiceLocator
     *
     * @param string|null $key
     *
     * @return array|object|string
     * @throws \PhproSmartCrud\Exception\SmartCrudException
     */
    public function getConfig($service, $action)
    {
        $serviceLocator = $this->getServiceLocator();
        $config = $serviceLocator->get('Config');
        $smartCrudConfig = null;
        if (!isset($config[self::CONFIG_KEY])) {
            return null;
        }
        $smartCrudConfig = $config[self::CONFIG_KEY];
        if (!isset($smartCrudConfig[$service])) {
            return null;
        }


        $defaultConfiguration = $this::getDefaultConfiguration();

        $result = array_merge(
            $defaultConfiguration[$this::CONFIG_DEFAULT],
            isset($defaultConfiguration[$action]) ? $defaultConfiguration[$action] : array(),
            isset($smartCrudConfig[$this::CONFIG_DEFAULT]) ? $smartCrudConfig[$this::CONFIG_DEFAULT] : array(),
            isset($smartCrudConfig[$service][$this::CONFIG_DEFAULT]) ? $smartCrudConfig[$service][$this::CONFIG_DEFAULT] : array(),
            isset($smartCrudConfig[$service][$action]) ? $smartCrudConfig[$service][$action] : array()
        );
        return $result;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $name
     * @param                         $requestedName
     *
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $this->setServiceLocator($serviceLocator);
        $serviceAndAction = explode('::',$requestedName);

        if(count($serviceAndAction) != 2) {
            return false;
        }
        $config = $this->getConfig($serviceAndAction[0], $serviceAndAction[1]);
        return !is_null($config);
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $name
     * @param                         $requestedName
     *
     * @return array|mixed|object
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {

        $this->setServiceLocator($serviceLocator);
        $serviceAndAction = explode('::',$requestedName);

        if(count($serviceAndAction) != 2) {
            return false;
        }
        $config = new ArrayObject($this->getConfig($serviceAndAction[0], $serviceAndAction[1]));

        $smartCrudService = $serviceLocator->get($config->offsetGet('service'));
        $this->injectDependencies($smartCrudService, $config);
        return $smartCrudService;
    }

    /**
     * @param CrudServiceInterface $smartCrudService
     * @param ArrayObject         $config
     *
     * @return $this
     */
    private function injectDependencies(CrudServiceInterface $smartCrudService,ArrayObject $config)
    {
        $this
            ->injectEntityClass($smartCrudService,$config)
            ->injectOutputModel($smartCrudService,$config)
            ->injectParameterService($smartCrudService, $config)
            ->injectGateway($smartCrudService, $config)
            ->injectForm($smartCrudService, $config)
            ->injectListeners($smartCrudService, $config);
        return $this;
    }

    /**
     * @param CrudServiceInterface $smartCrudService
     *
     * @return $this
     */
    private function injectOutputModel(CrudServiceInterface $smartCrudService, ArrayObject $config)
    {
        if(!$config->offsetExists($this::CONFIG_OUTPUT_MODEL)) {
            return $this;
        }

        $smartCrudService->setOutputModel($config->offsetGet($this::CONFIG_OUTPUT_MODEL));
        return $this;
    }
    /**
     * @param CrudServiceInterface $smartCrudService
     *
     * @return $this
     */
    private function injectEntityClass(CrudServiceInterface $smartCrudService, ArrayObject $config)
    {
        if(!$config->offsetExists($this::CONFIG_ENTITY_CLASS)) {
            return $this;
        }

        $smartCrudService->setEntityKey($config->offsetGet($this::CONFIG_ENTITY_CLASS));
        return $this;
    }

    /**
     * @param CrudServiceInterface $smartCrudService
     *
     * @return $this
     */
    private function injectParameterService(CrudServiceInterface $smartCrudService, ArrayObject $config)
    {
        if(!$config->offsetExists($this::CONFIG_PARAMETERS_KEY)) {
            return $this;
        }

        $serviceLocator = $this->getServiceLocator();
        $smartCrudService->setParameters($serviceLocator->get($config->offsetGet($this::CONFIG_PARAMETERS_KEY)));
        return $this;
    }


    /**
     * @param CrudServiceInterface $smartCrudService
     * @param ArrayObject         $config
     *
     * @return $this
     */
    private function injectForm(CrudServiceInterface $smartCrudService, ArrayObject $config)
    {
        if(!$config->offsetExists($this::CONFIG_FORM_KEY)) {
            return $this;
        }

        $serviceLocator = $this->getServiceLocator();
        $smartCrudService->setForm($serviceLocator->get($config->offsetGet($this::CONFIG_FORM_KEY)));
        return $this;
    }

    /**
     * @param CrudServiceInterface $smartCrudService
     * @param ArrayObject         $config
     *
     * @return $this
     */
    private function injectGateway(CrudServiceInterface $smartCrudService,ArrayObject $config)
    {
        if(!$config->offsetExists($this::CONFIG_GATEWAY_KEY)) {
            return $this;
        }

        $serviceLocator = $this->getServiceLocator();
        $smartCrudService->setGateway($serviceLocator->get($config->offsetGet($this::CONFIG_GATEWAY_KEY)));
        return $this;
    }

    /**
     * @param CrudServiceInterface $smartCrudService
     * @param ArrayObject         $config
     *
     * @return $this
     */
    private function injectListeners(CrudServiceInterface $smartCrudService,ArrayObject $config)
    {
        if($config->offsetExists($this::CONFIG_LISTENERS_KEY) && count($config[$this::CONFIG_LISTENERS_KEY]) < 1) {
            return $this;
        }

        $serviceLocator = $this->getServiceLocator();
        foreach ($config->offsetGet($this::CONFIG_LISTENERS_KEY) as $listener) {
            $smartCrudService->getEventManager()->attach($serviceLocator->get($listener));
        }
        return $this;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return $this
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    /**
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

}
