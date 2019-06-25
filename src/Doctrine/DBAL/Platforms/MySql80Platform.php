<?php

declare(strict_types=1);

namespace Webonaute\DoctrineMysqlExtra\Doctrine\DBAL\Platforms;

/**
 * Class MySqlPlatform.
 */
class MySql80Platform extends \Doctrine\DBAL\Platforms\MySQL80Platform
{
    /**
     * Returns the SQL snippet that declares a floating point column of arbitrary precision.
     *
     * @param array $columnDef
     *
     * @return string
     */
    public function getDecimalTypeDeclarationSQL(array $columnDef): string
    {
        $columnDef['precision'] = (!isset($columnDef['precision']) || empty($columnDef['precision']))
            ? 10 : $columnDef['precision'];
        $columnDef['scale'] = (!isset($columnDef['scale']) || empty($columnDef['scale']))
            ? 0 : $columnDef['scale'];

        return 'DECIMAL(' . $columnDef['precision'] . ', ' . $columnDef['scale'] . ')';
    }

    /**
     * Returns the SQL snippet that declares a floating point column of arbitrary precision.
     *
     * @param array $columnDef
     *
     * @return string
     */
    public function getNumericTypeDeclarationSQL(array $columnDef): string
    {
        $columnDef['precision'] = (!isset($columnDef['precision']) || empty($columnDef['precision']))
            ? 10 : $columnDef['precision'];
        $columnDef['scale'] = (!isset($columnDef['scale']) || empty($columnDef['scale']))
            ? 0 : $columnDef['scale'];

        return 'NUMERIC(' . $columnDef['precision'] . ', ' . $columnDef['scale'] . ')';
    }

    /**
     * @param $tableName
     * @param array $columns
     * @param array $options
     *
     * @return array
     */
    protected function _getCreateTableSQL($tableName, array $columns, array $options = []): array
    {
        return parent::_getCreateTableSQL($tableName, $columns, $options);
    }

    protected function initializeDoctrineTypeMappings(): void
    {
        parent::initializeDoctrineTypeMappings();

        $this->doctrineTypeMapping['numeric'] = 'numeric';
    }
}
