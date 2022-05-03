<?php

declare(strict_types=1);

namespace Webonaute\DoctrineMysqlExtra\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DecimalType;

/**
 * Type that maps an SQL NUMERIC to a PHP string.
 *
 * @since 2.0
 */
class NumericType extends DecimalType
{
    public const NUMERIC = 'numeric';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NUMERIC;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getNumericTypeDeclarationSQL($fieldDeclaration);
    }
}
