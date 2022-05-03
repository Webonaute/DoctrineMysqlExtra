<?php

declare(strict_types=1);

namespace Webonaute\DoctrineMysqlExtra\Doctrine\DBAL\Types;

use Cron\CronExpression;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Class CronExpressionType.
 */
class CronExpressionType extends Type
{
    public const CRON_EXPRESSION_TYPE = 'cron_expression';

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * @param string $value
     * @param AbstractPlatform $platform
     *
     * @return CronExpression
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): CronExpression
    {
        return CronExpression::factory($value);
    }

    /**
     * @param CronExpression $value
     * @param AbstractPlatform $platform
     *
     * @return mixed The database representation of the value.
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        return (string)$value;
    }

    public function getName(): string
    {
        return self::CRON_EXPRESSION_TYPE; // modify to match your constant name
    }
}
