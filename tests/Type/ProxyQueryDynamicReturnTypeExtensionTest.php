<?php

/*
 * This file is part of the phpstan/sonata project.
 *
 * (c) Ekino
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\PHPStan\Type;

use Doctrine\ORM\QueryBuilder;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\ProxyQueryDynamicReturnTypeExtension;
use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

/**
 * @author Rémi Marseille <marseille@ekino.com>
 */
class ProxyQueryDynamicReturnTypeExtensionTest extends TestCase
{
    /**
     * @var ProxyQueryDynamicReturnTypeExtension
     */
    private $extension;

    /**
     * Initializes the tests.
     */
    protected function setUp()
    {
        $this->extension = new ProxyQueryDynamicReturnTypeExtension();
    }

    /**
     * Asserts the extension implements the expected interfaces.
     */
    public function testImplements()
    {
        $this->assertInstanceOf(MethodsClassReflectionExtension::class, $this->extension);
        $this->assertInstanceOf(BrokerAwareExtension::class, $this->extension);
    }

    /**
     * Asserts hasMethod returns FALSE with non-handled class.
     */
    public function testHasMethodWithWrongClass()
    {
        $classReflection = $this->createMock(ClassReflection::class);
        $classReflection->expects($this->once())->method('getName')->willReturn('Foo\Bar');

        $this->assertFalse($this->extension->hasMethod($classReflection, 'leftJoin'));
    }

    /**
     * Asserts hasMethod returns FALSE with non-handled method.
     */
    public function testHasMethodWithWrongMethod()
    {
        $classReflection = $this->createMock(ClassReflection::class);
        $classReflection->expects($this->once())->method('getName')->willReturn(ProxyQuery::class);

        $dummyClassReflection = $this->createMock(ClassReflection::class);
        $dummyClassReflection->expects($this->once())->method('hasMethod')->willReturn(false);

        $broker = $this->createMock(Broker::class);
        $broker->expects($this->once())->method('getClass')->with($this->equalTo(QueryBuilder::class))->willReturn($dummyClassReflection);

        $this->extension->setBroker($broker);

        $this->assertFalse($this->extension->hasMethod($classReflection, 'foo'));
    }

    /**
     * Asserts hasMethod returns TRUE with valid arguments.
     */
    public function testHasMethodWithValidArgs()
    {
        $classReflection = $this->createMock(ClassReflection::class);
        $classReflection->expects($this->once())->method('getName')->willReturn(ProxyQuery::class);

        $dummyClassReflection = $this->createMock(ClassReflection::class);
        $dummyClassReflection->expects($this->once())->method('hasMethod')->willReturn(true);

        $broker = $this->createMock(Broker::class);
        $broker->expects($this->once())->method('getClass')->with($this->equalTo(QueryBuilder::class))->willReturn($dummyClassReflection);

        $this->extension->setBroker($broker);

        $this->assertTrue($this->extension->hasMethod($classReflection, 'leftJoin'));
    }

    /**
     * Tests getMethod.
     */
    public function testGetMethod()
    {
        $methodReflection = $this->createMock(MethodReflection::class);

        $dummyClassReflection = $this->createMock(ClassReflection::class);
        $dummyClassReflection->expects($this->once())->method('getNativeMethod')->willReturn($methodReflection);

        $broker = $this->createMock(Broker::class);
        $broker->expects($this->once())->method('getClass')->with($this->equalTo(QueryBuilder::class))->willReturn($dummyClassReflection);

        $this->extension->setBroker($broker);

        $this->assertSame($methodReflection, $this->extension->getMethod($this->createMock(ClassReflection::class), 'leftJoin'));
    }
}
