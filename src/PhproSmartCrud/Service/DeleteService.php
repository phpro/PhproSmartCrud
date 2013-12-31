<?php
/**
 * Smartcrud for Zend Framework (http://framework.zend.com/)
 *
 * @link http://github.com/veewee/PhproSmartCrud for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license http://framework.zend.com/license/new-bsd New BSD License
 */

namespace PhproSmartCrud\Service;

use PhproSmartCrud\Event\CrudEvent;

/**
 * Class DeleteService
 *
 * @package PhproSmartCrud\Service
 */
class DeleteService extends AbstractCrudService
{

    /**
     * @return bool
     */
    public function run($id, $data)
    {
        $em = $this->getEventManager();
        $entity = $this->loadEntity($id);

        $em->trigger($this->createEvent(CrudEvent::BEFORE_DELETE, $entity));

        $gateway = $this->getGateway();
        $result = $gateway->delete($this->loadEntity($id), $data);

        $em->trigger($this->createEvent(CrudEvent::AFTER_DELETE, $entity));
        return $result;
    }

}
