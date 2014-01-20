<?php
/**
 * Smartcrud for Zend Framework (http://framework.zend.com/)
 *
 * @link http://github.com/phpro/zf-smartcrud for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license http://framework.zend.com/license/new-bsd New BSD License
 */

namespace spec\Phpro\SmartCrud\Exception;

use PhpSpec\ObjectBehavior;

/**
 * Class SmartCrudExceptionSpec
 *
 * @package spec\Phpro\SmartCrud\Exception
 */
class SmartCrudExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Phpro\SmartCrud\Exception\SmartCrudException');
    }

    public function it_should_extend_Exception()
    {
        $this->shouldBeAnInstanceOf('Exception');
    }
}
