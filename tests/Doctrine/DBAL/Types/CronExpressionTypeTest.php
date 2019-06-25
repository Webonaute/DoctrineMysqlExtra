<?php

declare(strict_types=1);

/*
 * (c)Copyright 2007-2019 UGroupMedia Inc. <dev@ugroupmedia.com>
 * This source file is part of PNP Project and is subject to
 * copyright. It can not be copied and/or distributed without
 * the express permission of UGroupMedia Inc.
 * If you get a copy of this file without explicit authorization,
 * please contact us to the email above.
 */

namespace Tests\Webonaute\DoctrineMysqlExtra\Doctrine\DBAL\Types;

use Webonaute\DoctrineMysqlExtra\Doctrine\DBAL\Types\CronExpressionType;
use Cron\CronExpression;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class CronExpressionTypeTest.
 */
final class CronExpressionTypeTest extends TestCase
{
    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * @var Type
     */
    private $type;

    /**
     * @test
     */
    public function convertToPhpReturnsCronExpression(): void
    {
        $val = $this->type->convertToPHPValue('@daily', $this->platform);
        $this->assertInstanceOf(CronExpression::class, $val);
    }

    /**
     * @test
     */
    public function convertToDatabaseReturnsString(): void
    {
        $val = $this->type->convertToDatabaseValue(CronExpression::factory('@daily'), $this->platform);
        $this->assertIsString($val);
    }

    /**
     * @throws DBALException
     */
    protected function setUp(): void
    {
        if (!Type::hasType('cron_expression')) {
            Type::addType('cron_expression', CronExpressionType::class);
        }
        $this->type = Type::getType('cron_expression');
        $this->platform = $this->getPlatform();
    }

    /**
     * @return AbstractPlatform|MockObject
     */
    private function getPlatform(): MockObject
    {
        return $this->getMockBuilder(AbstractPlatform::class)->disableOriginalConstructor()->getMock();
    }
}
