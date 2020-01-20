<?php
declare(strict_types=1);

namespace Webonaute\DoctrineMysqlExtra\Doctrine\DBAL\Connections;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connections\MasterSlaveConnection as BaseMasterSlaveConnection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\SQLParserUtils;
use Doctrine\DBAL\Types\Type;
use function array_key_exists;
use function array_rand;
use function assert;
use function func_get_args;
use function is_int;
use function is_string;
use function key;

/**
 * Master-Slave Connection
 *
 * Connection can be used with master-slave setups.
 *
 * Important for the understanding of this connection should be how and when
 * it picks the slave or master.
 *
 * 1. Slave if master was never picked before and ONLY if 'getWrappedConnection'
 *    or 'executeQuery' is used.
 * 2. Master picked when 'exec', 'executeUpdate', 'insert', 'delete', 'update', 'createSavepoint',
 *    'releaseSavepoint', 'beginTransaction', 'rollback', 'commit', 'query' or
 *    'prepare' is called.
 * 3. If master was picked once during the lifetime of the connection it will always get picked afterwards.
 * 4. One slave connection is randomly picked ONCE during a request.
 *
 * ATTENTION: You can write to the slave with this connection if you execute a write query without
 * opening up a transaction. For example:
 *
 *      $conn = DriverManager::getConnection(...);
 *      $conn->executeQuery("DELETE FROM table");
 *
 * Be aware that Connection#executeQuery is a method specifically for READ
 * operations only.
 *
 * This connection is limited to slave operations using the
 * Connection#executeQuery operation only, because it wouldn't be compatible
 * with the ORM or SchemaManager code otherwise. Both use all the other
 * operations in a context where writes could happen to a slave, which makes
 * this restricted approach necessary.
 *
 * You can manually connect to the master at any time by calling:
 *
 *      $conn->connect('master');
 *
 * Instantiation through the DriverManager looks like:
 *
 * @example
 *
 * $conn = DriverManager::getConnection(array(
 *    'wrapperClass' => 'Doctrine\DBAL\Connections\MasterSlaveConnection',
 *    'driver' => 'pdo_mysql',
 *    'master' => array('user' => '', 'password' => '', 'host' => '', 'dbname' => ''),
 *    'slaves' => array(
 *        array('user' => 'slave1', 'password', 'host' => '', 'dbname' => ''),
 *        array('user' => 'slave2', 'password', 'host' => '', 'dbname' => ''),
 *    )
 * ));
 *
 * You can also pass 'driverOptions' and any other documented option to each of this drivers to pass additional information.
 */
class MasterSlaveConnection extends BaseMasterSlaveConnection
{
    public const WRITE_OPERATION_PATTERN = '/(CREATE\s+USER|GRANT|CREATE\s+TABLE|ALTER\s+TABLE|INSERT\s|UPDATE\s|DROP\s+TABLE|DROP\s+DATABASE|TRUNCATE|CREATE\s+TABLE|CREATE\s+DATABASE|DELETE\s)/i';

    /**
     * {@inheritDoc}
     */
    public function query()
    {
        $this->connect('master');
        assert($this->_conn instanceof DriverConnection);

        $args = func_get_args();

        $logger = $this->getConfiguration()->getSQLLogger();
        if ($logger) {
            $logger->startQuery($args[0]);
        }

        $statement = $this->_conn->query(...$args);

        $statement->setFetchMode($this->defaultFetchMode);

        if ($logger) {
            $logger->stopQuery();
        }

        return $statement;
    }

    /**
     * {@inheritDoc}
     */
    public function prepare($statement)
    {
        if (true === $this->isWriteOperation($statement)) {
            $this->connect('master');
        } else {
            $this->connect();
        }

        return parent::prepare($statement);
    }

    /**
     * Executes an, optionally parametrized, SQL query.
     *
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string $query The SQL query to execute.
     * @param mixed[] $params The parameters to bind to the query, if any.
     * @param int[]|string[] $types The types the previous parameters are in.
     * @param QueryCacheProfile|null $qcp The query cache profile, optional.
     *
     * @return ResultStatement The executed statement.
     *
     * @throws DBALException
     */
    public function executeQuery($query, array $params = [], $types = [], ?QueryCacheProfile $qcp = null)
    {
        if ($qcp !== null) {
            return $this->executeCacheQuery($query, $params, $types, $qcp);
        }

        $this->connect($this->isWriteOperation($query) ? 'master' : null);
        $connection = $this->_conn;

        $logger = $this->_config->getSQLLogger();
        if ($logger) {
            $logger->startQuery($query, $params, $types);
        }

        try {
            if ($params) {
                [$query, $params, $types] = SQLParserUtils::expandListParameters($query, $params, $types);

                $stmt = $connection->prepare($query);
                if ($types) {
                    $this->_bindTypedValues($stmt, $params, $types);
                    $stmt->execute();
                } else {
                    $stmt->execute($params);
                }
            } else {
                $stmt = $connection->query($query);
            }
        } catch (\Throwable $ex) {
            throw DBALException::driverExceptionDuringQuery($this->_driver, $ex, $query, $this->resolveParams($params, $types));
        }

        $stmt->setFetchMode($this->defaultFetchMode);

        if ($logger) {
            $logger->stopQuery();
        }

        return $stmt;
    }

    /**
     * Connects to a specific connection.
     *
     * @param string $connectionName
     *
     * @return DriverConnection
     */
    protected function connectTo($connectionName)
    {
        $params = $this->getParams();

        $driverOptions = $params['driverOptions'] ?? [];

        $connectionParams = $this->chooseConnectionConfiguration($connectionName, $params);

        $user = $connectionParams['user'] ?? null;
        $password = $connectionParams['password'] ?? null;

        return $this->_driver->connect($connectionParams, $user, $password, $driverOptions);
    }

    /**
     * @param string $connectionName
     * @param mixed[] $params
     *
     * @return mixed
     */
    protected function chooseConnectionConfiguration($connectionName, $params)
    {
        if ($connectionName === 'master') {
            return $params['master'];
        }

        $config = $params['slaves'][array_rand($params['slaves'])];

        if (!isset($config['charset']) && isset($params['master']['charset'])) {
            $config['charset'] = $params['master']['charset'];
        }

        return $config;
    }

    /**
     * @param string $statement
     *
     * @return bool
     */
    protected function isWriteOperation(string $statement): bool
    {
        return 1 === preg_match(self::WRITE_OPERATION_PATTERN, $statement);
    }

    /**
     * Binds a set of parameters, some or all of which are typed with a PDO binding type
     * or DBAL mapping type, to a given statement.
     *
     * @param \Doctrine\DBAL\Driver\Statement $stmt The statement to bind the values to.
     * @param mixed[] $params The map/list of named/positional parameters.
     * @param int[]|string[] $types The parameter types (PDO binding types or DBAL mapping types).
     *
     * @return void
     * @internal Duck-typing used on the $stmt parameter to support driver statements as well as
     *           raw PDOStatement instances.
     *
     */
    private function _bindTypedValues($stmt, array $params, array $types)
    {
        // Check whether parameters are positional or named. Mixing is not allowed, just like in PDO.
        if (is_int(key($params))) {
            // Positional parameters
            $typeOffset = array_key_exists(0, $types) ? -1 : 0;
            $bindIndex = 1;
            foreach ($params as $value) {
                $typeIndex = $bindIndex + $typeOffset;
                if (isset($types[$typeIndex])) {
                    $type = $types[$typeIndex];
                    [$value, $bindingType] = $this->getBindingInfo($value, $type);
                    $stmt->bindValue($bindIndex, $value, $bindingType);
                } else {
                    $stmt->bindValue($bindIndex, $value);
                }
                ++$bindIndex;
            }
        } else {
            // Named parameters
            foreach ($params as $name => $value) {
                if (isset($types[$name])) {
                    $type = $types[$name];
                    [$value, $bindingType] = $this->getBindingInfo($value, $type);
                    $stmt->bindValue($name, $value, $bindingType);
                } else {
                    $stmt->bindValue($name, $value);
                }
            }
        }
    }

    /**
     * Gets the binding type of a given type. The given type can be a PDO or DBAL mapping type.
     *
     * @param mixed $value The value to bind.
     * @param int|string|null $type The type to bind (PDO or DBAL).
     *
     * @return mixed[] [0] => the (escaped) value, [1] => the binding type.
     * @throws DBALException
     */
    private function getBindingInfo($value, $type)
    {
        if (is_string($type)) {
            $type = Type::getType($type);
        }
        if ($type instanceof Type) {
            $value = $type->convertToDatabaseValue($value, $this->getDatabasePlatform());
            $bindingType = $type->getBindingType();
        } else {
            $bindingType = $type;
        }

        return [$value, $bindingType];
    }
}
