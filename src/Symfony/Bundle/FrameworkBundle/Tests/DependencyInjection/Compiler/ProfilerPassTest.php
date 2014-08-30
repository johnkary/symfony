<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\ProfilerPass;

class ProfilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that collectors that specify a template but no "id" will throw
     * an exception (both are needed if the template is specified). Thus,
     * a fully-valid tag looks something like this:
     *
     *     <tag name="data_collector" template="YourBundle:Collector:templatename" id="your_collector_name" />
     */
    public function testTemplateNoIdThrowsException()
    {
        // one service, with a template key, but no id
        $services = array(
            'my_collector_service' => array(0 => array('template' => 'foo')),
        );

        $builder = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $builder->expects($this->atLeastOnce())
            ->method('findTaggedServiceIds')
            ->will($this->returnValue($services));

        $this->setExpectedException('InvalidArgumentException');

        $profilerPass = new ProfilerPass();
        $profilerPass->process($builder);
    }

    public function testValidCollector()
    {
        // one service, with a template key, but no id
        $services = array(
            'my_collector_service' => array(0 => array('template' => 'foo', 'id' => 'my_collector')),
        );

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->atLeastOnce())
            ->method('findTaggedServiceIds')
            ->will($this->returnValue($services));

        // fake the getDefinition() to return a Profiler definition
        $definition = new Definition('ProfilerClass');
        $container->expects($this->atLeastOnce())
            ->method('getDefinition')
            ->will($this->returnValue($definition));

        // assert that the data_collector.templates parameter should be set
        $container->expects($this->once())
            ->method('setParameter')
            ->with('data_collector.templates', array('my_collector_service' => array('my_collector', 'foo')));

        $profilerPass = new ProfilerPass();
        $profilerPass->process($container);

        // grab the method calls off of the "profiler" definition
        $methodCalls = $definition->getMethodCalls();
        $this->assertCount(1, $methodCalls);
        $this->assertEquals('add', $methodCalls[0][0]); // grab the method part of the first call
    }

    public function testTemplatesRegisteredInPriorityOrder()
    {
        $services = array(
            'my_collector_service4' => array(array(
                'template' => 'foo4',
                'id' => 'collector4',
                'priority' => 100,
            )),
            'my_collector_service5' => array(array(
                'template' => 'foo5',
                'id' => 'collector5',
                'priority' => 200,
            )),
            'my_collector_service2' => array(array(
                'template' => 'foo2',
                'id' => 'collector2',
                // no priority defined
            )),
            'my_collector_service1' => array(array(
                'template' => 'foo1',
                'id' => 'collector1',
                'priority' => -100,
            )),
            'my_collector_service3' => array(array(
                'template' => 'foo3',
                'id' => 'collector3',
                'priority' => 100,
            )),
        );

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->atLeastOnce())
            ->method('findTaggedServiceIds')
            ->will($this->returnValue($services));

        $definition = new Definition('ProfilerClass');
        $container->expects($this->atLeastOnce())
            ->method('getDefinition')
            ->will($this->returnValue($definition));

        $container->expects($this->once())
            ->method('setParameter')
            ->with('data_collector.templates', array(
                'my_collector_service5' => array('collector5', 'foo5'),
                'my_collector_service4' => array('collector4', 'foo4'),
                'my_collector_service3' => array('collector3', 'foo3'),
                'my_collector_service2' => array('collector2', 'foo2'),
                'my_collector_service1' => array('collector1', 'foo1'),
            ));

        $profilerPass = new ProfilerPass();
        $profilerPass->process($container);
    }
}
