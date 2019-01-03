<?php

/*
 * This file is part of the ekino/phpstan-sonata project.
 *
 * (c) Ekino
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ekino\PHPStanSonata\Type;

use Doctrine\ORM\QueryBuilder;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

/**
 * @author Rémi Marseille <remi.marseille@ekino.com>
 */
class ProxyQueryDynamicReturnTypeExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
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
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return ProxyQuery::class === $classReflection->getName()
            && $this->broker->getClass(QueryBuilder::class)->hasMethod($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->broker
            ->getClass(QueryBuilder::class)
            ->getNativeMethod($methodName);
    }
}
