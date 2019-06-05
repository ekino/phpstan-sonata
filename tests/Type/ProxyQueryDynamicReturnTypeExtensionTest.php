<?php

declare(strict_types=1);

/*
 * This file is part of the ekino/phpstan-sonata project.
 *
 * (c) Ekino
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Ekino\PHPStanSonata\Type;

use Doctrine\ORM\QueryBuilder;
use Ekino\PHPStanSonata\Type\ProxyQueryDynamicReturnTypeExtension;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface as AdminProxyQueryInterface;
use Sonata\DatagridBundle\ProxyQuery\ProxyQueryInterface as DatagridProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

/**
 * @author Rémi Marseille <remi.marseille@ekino.com>
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
    protected function setUp(): void
    {
        $this->extension = new ProxyQueryDynamicReturnTypeExtension();
    }

    /**
     * Asserts the extension implements the expected interfaces.
     */
    public function testImplements(): void
    {
        $this->assertInstanceOf(MethodsClassReflectionExtension::class, $this->extension);
        $this->assertInstanceOf(BrokerAwareExtension::class, $this->extension);
    }

    /**
     * @param bool   $expected
     * @param string $className
     * @param bool   $validMethod
     * @param string $methodName
     * @param int    $hasMethodCallCount
     *
     * @dataProvider hasMethodDataProvider
     */
    public function testHasMethod(bool $expected, string $className, bool $validMethod, string $methodName, int $hasMethodCallCount): void
    {
        $classReflection = $this->createMock(ClassReflection::class);
        $classReflection->expects($this->any())->method('getName')->willReturn($className);

        $dummyClassReflection = $this->createMock(ClassReflection::class);
        $dummyClassReflection->expects($this->exactly($hasMethodCallCount))->method('hasMethod')->willReturn($validMethod);

        $broker = $this->createMock(Broker::class);
        $broker->expects($this->exactly($hasMethodCallCount))->method('getClass')->with($this->equalTo(QueryBuilder::class))->willReturn($dummyClassReflection);

        $this->extension->setBroker($broker);

        $this->assertSame($expected, $this->extension->hasMethod($classReflection, $methodName));
    }

    /**
     * @return \Generator
     */
    public function hasMethodDataProvider(): \Generator
    {
        yield 'wrong class & method' => [false, 'Foo\Bar', true, 'foo', 0];
        yield 'wrong class & valid method' => [false, 'Foo\Bar', true, 'leftJoin', 0];
        yield 'proxy query & valid method' => [true, ProxyQuery::class, true, 'leftJoin', 1];
        yield 'proxy query & wrong method' => [false, ProxyQuery::class, false, 'foo', 1];
        yield 'admin proxy query & valid method' => [true, AdminProxyQueryInterface::class, true, 'leftJoin', 1];
        yield 'admin proxy query & wrong method' => [false, AdminProxyQueryInterface::class, false, 'foo', 1];
        yield 'datagrid proxy query & valid method' => [true, DatagridProxyQueryInterface::class, true, 'leftJoin', 1];
        yield 'datagrid proxy query & wrong method' => [false, DatagridProxyQueryInterface::class, false, 'foo', 1];
    }

    /**
     * Tests getMethod.
     */
    public function testGetMethod(): void
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
