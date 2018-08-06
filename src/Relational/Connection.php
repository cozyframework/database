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
    protected $pdo;

    /**
     * Wraps a PDO instance representing a connection to a database.
     *
     * @param \PDO $pdo Instance of PDO.
     */
    public function __construct(\PDO $pdo)
    {
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo = $pdo;
    }

    /**
     * Returns the wrapped PDO object.
     *
     * @return \PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    public function isAlive(): bool
    {
        try {
            if (@$this->pdo->query('SELECT 1') == false) {
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
        return $this->pdo->errorInfo();
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
            $statement = $this->pdo->prepare($sentence, $driver_options);

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
        return $this->pdo->getAttribute($attribute);
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
                'CZ099'
            );
        } elseif ($attribute === \PDO::ATTR_ERRMODE && $value !== \PDO::ERRMODE_EXCEPTION) {
            throw new Exception(
                'Cozy Database only allows the safest-by-default error mode (exceptions).',
                'CZ099'
            );
        }

        return $this->pdo->setAttribute($attribute, $value);
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
        return $this->pdo->quote($string, $parameter_type);
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string|null $name Name of the sequence object from which the ID should be returned.
     * @return string
     */
    public function getLastInsertId(string $name = null)
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * Initiates a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function beginTransaction()
    {
        try {
            return $this->pdo->beginTransaction();
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
            return $this->pdo->commit();
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
            return $this->pdo->rollBack();
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
        return $this->pdo->inTransaction();
    }
}
