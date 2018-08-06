<?php

declare(strict_types=1);

namespace Cozy\Database\Relational;

use Cozy\Database\Relational\Exceptions\Exception;
use Cozy\Database\Relational\Exceptions\StatementException;

/**
 * Represents a connection to a relational database server.
 * It encapsulates a PDO instance to simplify and improve its functionality, in addition to
 * allowing good security practices.
 */
class Connection
{
    /** @var \PDO */
    private $pdo;
    /** @var string */
    private $dsn;
    /** @var string */
    private $username;
    /** @var string */
    private $password;
    /** @var array */
    private $options = [
        \PDO::ATTR_TIMEOUT => 1,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * Creates an instance that represents a connection to a database.
     *
     * @param string $dsn The Data Source Name, or DSN, contains the information required to connect to the database.
     * @param string $username [optional] The user name for the DSN string. It is optional for some PDO drivers.
     * @param string $password [optional] The password for the DSN string. It is optional for some PDO drivers.
     * @param array $options [optional] A key=>value array of driver-specific connection options.
     */
    public function __construct(string $dsn, string $username = null, string $password = null, array $options = null) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;

        if (isset($options)) {
            $this->options = array_replace($this->options, $options);
        }
    }

    /**
     * Creates an instance that represents a connection to a database.
     *
     * @param array $configuration
     * @return Connection
     */
    public static function fromArray(array $configuration): Connection
    {
        if (!isset($configuration['dsn'])) {
            throw new Exception("The required directive 'dsn' is missing.");
        }
        $dsn = $configuration['dsn'];

        $username = null;
        if (isset($configuration['username'])) {
            $username = $configuration['username'];
        }

        $password = null;
        if (isset($configuration['password'])) {
            $password = $configuration['password'];
        }

        $options = null;
        if (isset($options)) {
            $options = $configuration['options'];
        }

        return new self($dsn, $username, $password, $options);
    }

    private function buildPDOConnection(): \PDO
    {
        try {
            return new \PDO($this->dsn, $this->username, $this->password, $this->options);
        } catch (\PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns the wrapped PDO object.
     *
     * @return \PDO
     */
    public function getPDO(): \PDO
    {
        if (!isset($this->pdo)) {
            $this->pdo = $this->buildPDOConnection();
        }

        return $this->pdo;
    }

    public function isAlive(): bool
    {
        try {
            if (@$this->getPDO()->query('SELECT 1') == false) {
                return false;
            }

            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Returns error information about the last operation on the database handle.
     *
     * @return array
     */
    public function getErrorInfo()
    {
        return $this->getPDO()->errorInfo();
    }

    /**
     * Prepares a statement for execution.
     *
     * @param string $sentence A valid and properly escaped SQL sentence.
     * @param array $driver_options Attribute values for the PDOStatement object.
     * @return Statement|bool Returns a Statement object or false in case of failure.
     */
    public function prepare(string $sentence, array $driver_options = [])
    {
        try {
            $statement = $this->getPDO()->prepare($sentence, $driver_options);

            if ($statement === false) {
                return false;
            }

            return new Statement($statement);
        } catch (\PDOException $e) {
            throw new StatementException($sentence, $e->getMessage(), $e->getCode(), $this->pdo->errorInfo(), $e);
        }
    }

    /**
     * Retrieve a database connection attribute from the wrapped PDO.
     *
     * @param int $attribute One of the PDO::ATTR_* constants
     * @return mixed A successful call returns the value of the requested PDO attribute, otherwise returns null.
     */
    public function getAttribute($attribute)
    {
        return $this->getPDO()->getAttribute($attribute);
    }

    /**
     * Sets an attribute in the wrapped PDO.
     *
     * @param int $attribute One of the PDO::ATTR_* constants.
     * @param mixed $value The value to pass.
     * @return bool TRUE on success or FALSE on failure.
     * @throws Exception
     */
    public function setAttribute($attribute, $value)
    {
        if ($attribute === \PDO::ATTR_EMULATE_PREPARES && $value !== false) {
            throw new Exception(
                'Cozy Database does not allow the use of emulated prepared statements, ' .
                'which would be a security downgrade.',
                'CZ096'
            );
        } elseif ($attribute === \PDO::ATTR_ERRMODE && $value !== \PDO::ERRMODE_EXCEPTION) {
            throw new Exception(
                'Cozy Database only allows the safest-by-default error mode (exceptions).',
                'CZ099'
            );
        }

        return $this->getPDO()->setAttribute($attribute, $value);
    }

    /**
     * Quotes a string for use in a query.
     *
     * @param string $string The string to be quoted.
     * @param int $parameter_type Provides a data type hint for drivers that have alternate quoting styles.
     * @return string|bool A quoted string that is theoretically safe to pass into an SQL statement.
     *         Returns FALSE if the driver does not support quoting in this way.
     */
    public function quote($string, $parameter_type = \PDO::PARAM_STR)
    {
        return $this->getPDO()->quote($string, $parameter_type);
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string|null $name Name of the sequence object from which the ID should be returned.
     * @return string
     */
    public function getLastInsertId(string $name = null)
    {
        return $this->getPDO()->lastInsertId($name);
    }

    /**
     * Initiates a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function beginTransaction()
    {
        try {
            return $this->getPDO()->beginTransaction();
        } catch (\PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Commits the current transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function commitTransaction()
    {
        try {
            return $this->getPDO()->commit();
        } catch (\PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Rolls back the current transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function rollBackTransaction()
    {
        try {
            return $this->getPDO()->rollBack();
        } catch (\PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Checks if inside a transaction.
     *
     * @return bool TRUE if a transaction is currently active, and FALSE if not.
     */
    public function inTransaction()
    {
        return $this->getPDO()->inTransaction();
    }
}
