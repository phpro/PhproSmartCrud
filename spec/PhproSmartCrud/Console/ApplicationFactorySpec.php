<?php
/**
 * Smartcrud for Zend Framework (http://framework.zend.com/)
 *
 * @link http://github.com/veewee/PhproSmartCrud for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license http://framework.zend.com/license/new-bsd New BSD License
 */

namespace spec\PhproSmartCrud\Console;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * Class ApplicationSpec
 *
 * @package spec\PhproSmartCrud\Console
 */
class ApplicationFactorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('PhproSmartCrud\Console\ApplicationFactory');
    }

    public function it_should_extend_Symfony_Console_Application()
    {
        $this->shouldHaveType('\Zend\ServiceManager\FactoryInterface');
    }

}
