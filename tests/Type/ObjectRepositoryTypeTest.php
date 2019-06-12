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

use Ekino\PHPStanSonata\Type\ObjectRepositoryType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

/**
 * @author Benoit Mazière <benoit.maziere@ekino.com>
 */
class ObjectRepositoryTypeTest extends TestCase
{
    /**
     * @var ObjectRepositoryType
     */
    private $type;

    /**
     * Initializes the tests.
     */
    protected function setUp(): void
    {
        $this->type = new ObjectRepositoryType('Foo\entityClass', 'Bar\repositoryClass');
    }

    /**
     * Test getEntityClass method.
     */
    public function testGetEntityClass(): void
    {
        $this->assertSame('Foo\entityClass', $this->type->getEntityClass());
    }

    /**
     * Test describe method.
     */
    public function testDescribe(): void
    {
        $verbosityLevel = $this->createMock(VerbosityLevel::class);
        $this->assertSame('<Foo\entityClass>', $this->type->describe($verbosityLevel));
    }

    /**
     * Test describe method.
     */
    public function testSetState(): void
    {
        $object = $this->type::__set_state([
            'entityClass' => 'foo',
            'className'   => 'baz',
        ]);
        $this->assertInstanceOf(ObjectRepositoryType::class, $object);
        $this->assertSame('foo', $object->getEntityClass());
    }
}
