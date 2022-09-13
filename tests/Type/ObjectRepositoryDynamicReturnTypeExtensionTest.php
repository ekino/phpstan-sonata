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
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
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
     * @var MockObject|ReflectionProvider
     */
    private $reflectionProvider;

    /**
     * @var MockObject|ExtendedMethodReflection
     */
    private $extendedMethodReflection;

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
        $this->reflectionProvider       = $this->createMock(ReflectionProvider::class);
        $this->extension                = new ObjectRepositoryDynamicReturnTypeExtension($this->reflectionProvider);
        $this->extendedMethodReflection = $this->createMock(ExtendedMethodReflection::class);
        $this->methodCall               = $this->createMock(MethodCall::class);
        $this->scope                    = $this->createMock(Scope::class);
        $this->expr                     = $this->createMock(Expr::class);
        $this->methodCall->var          = $this->expr;
        $this->methodCall->args         = [];
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
        $this->extendedMethodReflection->expects($this->once())->method('getName')->willReturn($methodName);

        $this->assertSame($expected, $this->extension->isMethodSupported($this->extendedMethodReflection));
    }

    /**
     * @return \Generator<array<mixed>>
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

        $this->assertInstanceOf(MixedType::class, $this->extension->getTypeFromMethodCall($this->extendedMethodReflection, $this->methodCall, $this->scope));
    }

    /**
     *
     */
    public function testGetTypeFromMethodCallWithClassNameType(): void
    {
        $type = $this->createMock(TypeWithClassName::class);
        $this->scope->expects($this->once())->method('getType')->with($this->expr)->willReturn($type);

        $this->assertInstanceOf(MixedType::class, $this->extension->getTypeFromMethodCall($this->extendedMethodReflection, $this->methodCall, $this->scope));
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
        $this->extendedMethodReflection->expects($this->once())->method('getName')->willReturn($methodName);

        $returnedExtendedMethodReflection = $this->createMock(ExtendedMethodReflection::class);
        $returnedExtendedMethodReflection->expects($this->once())->method('getVariants')->willReturn([new TrivialParametersAcceptor()]);

        $classReflection = $this->createMock(ClassReflection::class);
        $classReflection->expects($this->once())->method('hasNativeMethod')->with($methodName)->willReturn(true);
        $classReflection->expects($this->once())->method('getNativeMethod')->with($methodName)->willReturn($returnedExtendedMethodReflection);
        $this->reflectionProvider->expects($this->once())->method('hasClass')->with('Foo')->willReturn(true);
        $this->reflectionProvider->expects($this->once())->method('getClass')->with('Foo')->willReturn($classReflection);

        $this->assertInstanceOf(MixedType::class, $this->extension->getTypeFromMethodCall($this->extendedMethodReflection, $this->methodCall, $this->scope));
    }

    /**
     * @return \Generator<array<string>>
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
        $this->extendedMethodReflection->expects($this->once())->method('getName')->willReturn($methodName);

        $this->reflectionProvider->expects($this->once())->method('hasClass')->with('Foo')->willReturn(false);

        $this->assertInstanceOf($expected, $this->extension->getTypeFromMethodCall($this->extendedMethodReflection, $this->methodCall, $this->scope));
    }

    /**
     * @return \Generator<array<string>>
     */
    public function getTypeFromMethodCallWithObjectRepositoryTypeDataProvider(): \Generator
    {
        yield 'findOneByFoo' => [UnionType::class, 'findOneByFoo'];
        yield 'findByFoo'    => [ArrayType::class, 'findByFoo'];
    }
}
