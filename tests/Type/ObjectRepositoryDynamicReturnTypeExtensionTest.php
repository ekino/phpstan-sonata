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

use Ekino\PHPStanSonata\Type\ObjectRepositoryDynamicReturnTypeExtension;
use Ekino\PHPStanSonata\Type\ObjectRepositoryType;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sonata\Doctrine\Model\BaseManager;

/**
 * @author Benoit Mazière <benoit.maziere@ekino.com>
 */
class ObjectRepositoryDynamicReturnTypeExtensionTest extends TestCase
{
    /**
     * @var ObjectRepositoryDynamicReturnTypeExtension
     */
    private $extension;

    /**
     * @var MockObject|Broker
     */
    private $broker;

    /**
     * @var MockObject|MethodReflection
     */
    private $methodReflection;

    /**
     * @var MockObject|MethodCall
     */
    private $methodCall;

    /**
     * @var MockObject|Scope
     */
    private $scope;

    /**
     * @var MockObject|Expr
     */
    private $expr;

    /**
     * Initializes the tests.
     */
    protected function setUp(): void
    {
        $this->extension        = new ObjectRepositoryDynamicReturnTypeExtension();
        $this->broker           = $this->createMock(Broker::class);
        $this->methodReflection = $this->createMock(MethodReflection::class);
        $this->methodCall       = $this->createMock(MethodCall::class);
        $this->scope            = $this->createMock(Scope::class);
        $this->expr             = $this->createMock(Expr::class);
        $this->methodCall->var  = $this->expr;
        $this->methodCall->args = [];

        $this->extension->setBroker($this->broker);
    }

    /**
     * Test setBroker method.
     */
    public function testSetBroker(): void
    {
        $property = new \ReflectionProperty($this->extension, 'broker');
        $property->setAccessible(true);
        $this->assertInstanceOf(Broker::class, $property->getValue($this->extension));
    }

    /**
     * Test getClass method.
     */
    public function testGetClass(): void
    {
        $this->assertSame(BaseManager::class, $this->extension->getClass());
    }

    /**
     * Test isMethodSupported method.
     *
     * @dataProvider isMethodSupportedDataProvider
     */
    public function testIsMethodSupported(bool $expected, string $methodName): void
    {
        $methodReflection = $this->createMock(MethodReflection::class);
        $methodReflection->expects($this->once())->method('getName')->willReturn($methodName);

        $this->assertSame($expected, $this->extension->isMethodSupported($methodReflection));
    }

    /**
     * @return \Generator<array>
     */
    public function isMethodSupportedDataProvider(): \Generator
    {
        yield 'findBy' => [true, 'findBy'];
        yield 'findOneBy' => [true, 'findOneBy'];
        yield 'findAll' => [true, 'findAll'];
        yield 'find' => [true, 'find'];
        yield 'findByFoo' => [true, 'findByFoo'];
        yield 'findOneByFoo' => [true, 'findOneByFoo'];
        yield 'xxfindBy' => [false, 'xxfindBy'];
        yield 'xxfindOneBy' => [false, 'xxfindOneBy'];
        yield 'xxfindAll' => [false, 'xxfindAll'];
        yield 'findAllxx' => [false, 'findAllxx'];
        yield 'xxfind' => [false, 'xxfind'];
        yield 'findxx' => [false, 'findxx'];
    }

    /**
     *
     */
    public function testGetTypeFromMethodCallWithoutClassNameType(): void
    {
        $type = $this->createMock(Type::class);
        $this->scope->expects($this->once())->method('getType')->with($this->expr)->willReturn($type);

        $this->assertInstanceOf(MixedType::class, $this->extension->getTypeFromMethodCall($this->methodReflection, $this->methodCall, $this->scope));
    }

    /**
     *
     */
    public function testGetTypeFromMethodCallWithClassNameType(): void
    {
        $type = $this->createMock(TypeWithClassName::class);
        $this->scope->expects($this->once())->method('getType')->with($this->expr)->willReturn($type);

        $this->assertInstanceOf(MixedType::class, $this->extension->getTypeFromMethodCall($this->methodReflection, $this->methodCall, $this->scope));
    }

    /**
     * @param string $methodName
     *
     * @dataProvider getTypeFromMethodCallWithDynamicClassNameTypeDataProvider
     */
    public function testGetTypeFromMethodCallWithDynamicClassNameType(string $methodName): void
    {
        $type = $this->createMock(TypeWithClassName::class);
        $type->expects($this->exactly(2))->method('getClassName')->willReturn('Foo');
        $this->scope->expects($this->once())->method('getType')->with($this->expr)->willReturn($type);
        $this->methodReflection->expects($this->once())->method('getName')->willReturn($methodName);

        $returnedMethodReflexion = $this->createMock(MethodReflection::class);
        $returnedMethodReflexion->expects($this->once())->method('getVariants')->willReturn([new TrivialParametersAcceptor()]);

        $classReflexion = $this->createMock(ClassReflection::class);
        $classReflexion->expects($this->once())->method('hasNativeMethod')->with($methodName)->willReturn(true);
        $classReflexion->expects($this->once())->method('getNativeMethod')->with($methodName)->willReturn($returnedMethodReflexion);
        $this->broker->expects($this->once())->method('hasClass')->with('Foo')->willReturn(true);
        $this->broker->expects($this->once())->method('getClass')->with('Foo')->willReturn($classReflexion);

        $this->assertInstanceOf(MixedType::class, $this->extension->getTypeFromMethodCall($this->methodReflection, $this->methodCall, $this->scope));
    }

    /**
     * @return \Generator<array>
     */
    public function getTypeFromMethodCallWithDynamicClassNameTypeDataProvider(): \Generator
    {
        yield 'findByFoo'    => ['findByFoo'];
        yield 'findOneByFoo' => ['findOneByFoo'];
    }

    /**
     * @param class-string<object> $expected
     * @param string $methodName
     *
     * @dataProvider getTypeFromMethodCallWithObjectRepositoryTypeDataProvider
     */
    public function testGetTypeFromMethodCallWithObjectRepositoryType(string $expected, string $methodName): void
    {
        $type = $this->createMock(ObjectRepositoryType::class);
        $type->expects($this->once())->method('getClassName')->willReturn('Foo');
        $this->scope->expects($this->once())->method('getType')->with($this->expr)->willReturn($type);
        $this->methodReflection->expects($this->once())->method('getName')->willReturn($methodName);

        $classReflexion = $this->createMock(ClassReflection::class);
        $this->broker->expects($this->once())->method('hasClass')->with('Foo')->willReturn(false);

        $this->assertInstanceOf($expected, $this->extension->getTypeFromMethodCall($this->methodReflection, $this->methodCall, $this->scope));
    }

    /**
     * @return \Generator<array>
     */
    public function getTypeFromMethodCallWithObjectRepositoryTypeDataProvider(): \Generator
    {
        yield 'findOneByFoo' => [UnionType::class, 'findOneByFoo'];
        yield 'findByFoo'    => [ArrayType::class, 'findByFoo'];
    }
}
