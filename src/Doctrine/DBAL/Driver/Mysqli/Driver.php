<?php
declare(strict_types=1);

namespace Webonaute\DoctrineMysqlExtra\Doctrine\DBAL\Driver\Mysqli;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Mysqli\Driver as MysqliDriver;

class Driver extends MysqliDriver
{
    /**
     * {@inheritdoc}
     */
    public function getDatabase(Connection $conn)
    {
        $params = $conn->getParams();

        return $params['dbname'] ?? $conn->getWrappedConnection()->query('SELECT DATABASE()')->fetchColumn();
    }
}
