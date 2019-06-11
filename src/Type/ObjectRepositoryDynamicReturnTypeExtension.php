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

namespace Ekino\PHPStanSonata\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;
use Sonata\Doctrine\Model\BaseManager;

/**
 * @see PHPStan\Type\Doctrine\ObjectRepositoryDynamicReturnTypeExtension for original implementation.
 *
 * @author Benoit Maziere <benoit.maziere@ekino.com>
 */
class ObjectRepositoryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension, BrokerAwareExtension
{
    /**
     * @var Broker
     */
    private $broker;

    /**
     * {@inheritdoc}
     */
    public function setBroker(Broker $broker): void
    {
        $this->broker = $broker;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return BaseManager::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $methodName = $methodReflection->getName();

        return strpos($methodName, 'findBy') === 0
            || strpos($methodName, 'findOneBy') === 0
            || $methodName === 'findAll'
            || $methodName === 'find';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type
    {
        $calledOnType = $scope->getType($methodCall->var);

        if (!$calledOnType instanceof TypeWithClassName) {
            return new MixedType();
        }

        $methodName = $methodReflection->getName();

        if ($this->broker->hasClass($calledOnType->getClassName())) {
            $repositoryClassReflection = $this->broker->getClass($calledOnType->getClassName());
            if (
                (
                    (
                        strpos($methodName, 'findBy') === 0
                        && \strlen($methodName) > \strlen('findBy')
                    ) || (
                        strpos($methodName, 'findOneBy') === 0
                        && \strlen($methodName) > \strlen('findOneBy')
                    )
                )
                && $repositoryClassReflection->hasNativeMethod($methodName)
            ) {
                return ParametersAcceptorSelector::selectFromArgs(
                    $scope,
                    $methodCall->args,
                    $repositoryClassReflection->getNativeMethod($methodName)->getVariants()
                )->getReturnType();
            }
        }

        if (!$calledOnType instanceof ObjectRepositoryType) {
            return new MixedType();
        }

        $entityType = new ObjectType($calledOnType->getEntityClass());

        if ($methodName === 'find' || strpos($methodName, 'findOneBy') === 0) {
            return TypeCombinator::addNull($entityType);
        }

        return new ArrayType(new IntegerType(), $entityType);
    }
}
