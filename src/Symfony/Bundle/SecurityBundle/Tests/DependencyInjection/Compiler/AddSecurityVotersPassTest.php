<?php

namespace Symfony\Bundle\SecurityBundle\Tests\DependencyInjection\Compiler;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Compiler\AddSecurityVotersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AddSecurityVotersPassTest extends \PHPUnit_Framework_TestCase
{
    public function testVotersRegisteredInPriorityOrder()
    {
        $services = array(
            'voter4' => array(0 => array('priority' => 100)),
            'voter5' => array(0 => array('priority' => 200)),
            'voter2' => array(),
            'voter1' => array(0 => array('priority' => -100)),
            'voter3' => array(0 => array('priority' => 100)),
        );

        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $container->expects($this->atLeastOnce())
            ->method('findTaggedServiceIds')
            ->will($this->returnValue($services));
        $container->expects($this->atLeastOnce())
            ->method('getDefinition')
            ->with('security.access.decision_manager')
            ->will($this->returnValue($definition));
        $container->expects($this->atLeastOnce())
            ->method('hasDefinition')
            ->with('security.access.decision_manager')
            ->will($this->returnValue(true));

        $definition->expects($this->once())
            ->method('replaceArgument')
            ->with(0, array(
                new Reference('voter5'),
                new Reference('voter4'),
                new Reference('voter3'),
                new Reference('voter2'),
                new Reference('voter1'),
            ));

        $addCacheWarmerPass = new AddSecurityVotersPass();
        $addCacheWarmerPass->process($container);
    }

    public function testSecurityWithRealBuilder()
    {
        $container = $this->createContainer();

        $this->registerVoter($container, 'Voter1', -100);
        $this->registerVoter($container, 'Voter2', 0);
        $this->registerVoter($container, 'Voter4', 100);
        $this->registerVoter($container, 'Voter3', 100);
        $this->registerVoter($container, 'Voter5', 200);

        $service = new AddSecurityVotersPass();
        $service->process($container);

        $expectedVoterOrder = array(
            'Voter5',
            'Voter4',
            'Voter3',
            'Voter2',
            'Voter1',
        );
        foreach ($expectedVoterOrder as $key => $class) {
            $actual = get_class($container->get('security.access.decision_manager')->voters[$key]);
            $this->assertSame($this->className($class), $actual);
        }
    }

    private function createContainer()
    {
        $container = new ContainerBuilder();

        $admVoters = array();
        $adm = new Definition($this->className('StubAccessDecisionManager'), array($admVoters));
        $container->setDefinition('security.access.decision_manager', $adm);

        return $container;
    }

    private function registerVoter(ContainerBuilder $container, $voterClass, $priority)
    {
        $attributes = array('priority' => $priority);
        $def = new Definition($this->className($voterClass), $attributes);

        $def->addTag('security.voter', $attributes);

        $container->setDefinition($voterClass, $def);
    }

    private function className($class)
    {
        return __NAMESPACE__ . '\\' . $class;
    }
}

class StubAccessDecisionManager
{
    public $voters;
    public function __construct($voters)
    {
        $this->voters = $voters;
    }
}

class Voter1 {}
class Voter2 {}
class Voter3 {}
class Voter4 {}
class Voter5 {}
