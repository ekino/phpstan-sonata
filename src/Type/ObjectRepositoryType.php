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

use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @see PHPStan\Type\Doctrine\ObjectRepositoryType for original implementation.
 *
 * @author Benoit Maziere <benoit.maziere@ekino.com>
 */
class ObjectRepositoryType extends ObjectType
{
    /**
     * @var string
     */
    private $entityClass;

    /**
     * ObjectRepositoryType constructor.
     *
     * @param string $entityClass
     * @param string $repositoryClass
     */
    public function __construct(string $entityClass, string $repositoryClass)
    {
        parent::__construct($repositoryClass);
        $this->entityClass = $entityClass;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function describe(VerbosityLevel $level): string
    {
        return sprintf('%s<%s>', parent::describe($level), $this->entityClass);
    }

    /**
     * {@inheritdoc}
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['entityClass'], $properties['className']);
    }
}
