<?php

namespace Cozy\Database;

/**
 * Represents a connection to a relational database server.
 * Wraps a PDO instance to simplify and enhance its functionality.
 */
class Connection
{
    /** @var \PDO */
    protected $pdo;
    protected $statements = [];
    /** @var self */
    protected static $defaultInstance;

    /**
     * Wraps a PDO instance representing a connection to a database.
     *
     * @param \PDO $pdo Instance of PDO.
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        self::$defaultInstance = $this;
    }

    public function __destruct()
    {
        // TODO: Finish and test the __destruct() method.
        foreach ($this->statements as $key => $statement) {
            if ($statement) {
                $this->statements[$key] = null;
                unset($this->statements[$key]);
            }
        }
    }

    /**
     * Set this instance of as the default one.
     */
    public function setDefaultInstance()
    {
        self::$defaultInstance = $this;
    }

    /**
     * Return the default instance of this class.
     *
     * @return self
     */
    public static function getDefaultInstance()
    {
        return self::$defaultInstance;
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
     * Returns a Statement object or false in case of failure.
     *
     * @param string $sqlStatement A valid SQL statement.
     * @param array $driverOptions Attribute values for the PDOStatement object.
     * @return Statement|bool
     */
    public function prepare(string $sqlStatement, array $driverOptions = [])
    {
        $statement = $this->pdo->prepare($sqlStatement, $driverOptions);

        if ($statement === false) {
            return false;
        }

        $this->statements[] = $statement;

        return new Statement($statement);
    }

    /**
     * Execute an SQL statement and return the number of affected rows or false in case of failure.
     *
     * @param string $statement
     * @return int|bool
     */
    public function execute($statement = null)
    {
        return $this->pdo->exec($statement);
    }

    /**
     * Retrieve a database connection attribute from the wrapped PDO.
     *
     * @param int $attribute One of the PDO::ATTR_* constants
     * @return mixed
     */
    public function getAttribute($attribute)
    {
        return $this->pdo->getAttribute($attribute);
    }

    /**
     * Sets an attribute on the database handle (wrapped PDO).
     *
     * @param int $attribute One of the PDO::ATTR_* constants
     * @param mixed $value The value to pass
     * @return bool
     */
    public function setAttribute($attribute, $value)
    {
        return $this->pdo->setAttribute($attribute, $value);
    }

    /**
     * Returns a quoted string that is theoretically safe to pass into an SQL statement.
     * Returns FALSE if the driver does not support quoting in this way.
     *
     * @param string $string The string to be quoted.
     * @param int $parameterType Provides a data type hint for drivers that have alternate quoting styles.
     * @return string
     */
    public function quote($string, $parameterType = \PDO::PARAM_STR)
    {
        return $this->pdo->quote($string, $parameterType);
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
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commits the current transaction.
     *
     * @return bool
     */
    public function commitTransaction()
    {
        return $this->pdo->commit();
    }

    /**
     * Checks if inside a transaction.
     *
     * @return bool
     */
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Rolls back the current transaction.
     *
     * @return bool
     */
    public function rollBackTransaction()
    {
        return $this->pdo->rollBack();
    }

    /**
     * Initiates a fluent Query Builder.
     *
     * @return QueryBuilder
     */
    public function initQueryBuilder()
    {
        return new QueryBuilder($this);
    }
}
