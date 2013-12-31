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
 * Class CreateService
 *
 * @package PhproSmartCrud\Service
 */
class CreateService extends AbstractCrudService
{

    /**
     * @param $id
     * @param array $data
     *
     * @return bool
     */
    public function run($id = null, $data)
    {
        $em = $this->getEventManager();
        $entity = $this->getEntity();
        $form = $this->getForm($entity)->setData($data);
        $em->trigger($this->createEvent(CrudEvent::BEFORE_DATA_VALIDATION, $form));
        if($form->isValid()) {
            $em->trigger($this->createEvent(CrudEvent::BEFORE_CREATE, $entity));
            $result = $this->getGateway()->create($entity, $data);
            $em->trigger($this->createEvent(CrudEvent::AFTER_CREATE, $entity));
        } else {
            $result = false;
            $em->trigger($this->createEvent(CrudEvent::INVALID_CREATE, $form));
        }
        return $result;
    }

}
