<?php

declare(strict_types=1);

namespace Cozy\Database\Relational;

use Cozy\Database\Relational\Exceptions\StatementException;

class Statement
{
    public const PARAM_INT = \PDO::PARAM_INT;
    public const PARAM_STR = \PDO::PARAM_STR;
    public const PARAM_NULL = \PDO::PARAM_NULL;
    public const PARAM_BOOL = \PDO::PARAM_BOOL;

    /** @var \PDOStatement */
    protected $pdoStatement;
    /** @var Connection */
    protected $connection;
    protected $wasExecuted = false;
    protected $wasExecutedSuccessfully = false;
    protected $autoExecuteEnabled = true;

    /**
     * Statement constructor that wraps a PDOStatement object.
     *
     * @param \PDOStatement $pdoStatement
     * @param Connection $connection
     */
    public function __construct(\PDOStatement $pdoStatement, Connection $connection)
    {
        $this->pdoStatement = $pdoStatement;
        $this->connection = $connection;
    }

    /**
     * Returns the parent Connection object.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Returns the wrapped PDO statement object.
     *
     * @return \PDOStatement
     */
    public function getPdoStatement(): \PDOStatement
    {
        return $this->pdoStatement;
    }

    /**
     * Set a statement attribute.
     *
     * @param int $attribute
     * @param mixed $value
     * @return bool TRUE on success or FALSE on failure.
     */
    public function setAttribute(int $attribute, $value)
    {
        return $this->pdoStatement->setAttribute($attribute, $value);
    }

    /**
     * Retrieve a statement attribute.
     *
     * @param int $attribute
     * @return mixed The attribute value.
     */
    public function getAttribute(int $attribute)
    {
        return $this->pdoStatement->getAttribute($attribute);
    }

    /**
     * Returns error information about the last operation performed by this statement.
     *
     * @return array
     */
    public function getErrorInfo(): array
    {
        return $this->pdoStatement->errorInfo();
    }

    /**
     * Binds a value to a parameter.
     *
     * @param mixed $parameter Parameter identifier.
     * @param mixed $value The value to bind to the parameter.
     * @param mixed $type [optional] Explicit data type for the parameter.
     * @return $this
     * @throws StatementException
     */
    public function bindValue($parameter, $value, $type = 'str')
    {
        $pdo_type = \PDO::PARAM_STR;

        if ($type === 'int' || $type === 'integer' || $type === \PDO::PARAM_INT) {
            $pdo_type = \PDO::PARAM_INT;
        } elseif ($type === 'bool' || $type === 'boolean' || $type === \PDO::PARAM_BOOL) {
            $pdo_type = \PDO::PARAM_BOOL;
        } elseif ($type === 'lob' || $type === 'blob' || $type === \PDO::PARAM_LOB) {
            $pdo_type = \PDO::PARAM_LOB;
        } elseif ($type === 'null' || $type === \PDO::PARAM_NULL || $value === null) {
            $pdo_type = \PDO::PARAM_NULL;
        }

        if (!$this->pdoStatement->bindValue($parameter, $value, $pdo_type)) {
            throw new StatementException(
                $this->pdoStatement->queryString,
                "Error binding invalid parameter [{$parameter}], it was not defined.",
                $this->pdoStatement->errorCode(),
                $this->pdoStatement->errorInfo()
            );
        }

        return $this;
    }

    /**
     * Bind a column to a PHP variable.
     *
     * @param mixed $column Number of the column (1-indexed) or name of the column in the result set. If using the
     *                      column name, be aware that the name should match the case of the column, as returned by
     *                      the driver.
     * @param mixed $param Name of the PHP variable to which the column will be bound.
     * @param mixed $type [optional] Data type of the parameter, specified by the PDO::PARAM_* constants.
     * @return $this
     * @throws StatementException
     */
    public function bindColumn($column, &$param, $type = null)
    {
        $pdo_type = \PDO::PARAM_STR;

        if ($type === 'int' || $type === 'integer' || $type === \PDO::PARAM_INT) {
            $pdo_type = \PDO::PARAM_INT;
        } elseif ($type === 'bool' || $type === 'boolean' || $type === \PDO::PARAM_BOOL) {
            $pdo_type = \PDO::PARAM_BOOL;
        } elseif ($type === 'lob' || $type === 'blob' || $type === \PDO::PARAM_LOB) {
            $pdo_type = \PDO::PARAM_LOB;
        }

        if (!$this->pdoStatement->bindColumn($column, $param, $pdo_type)) {
            throw new StatementException(
                $this->pdoStatement->queryString,
                "Error binding invalid column [{$column}], it was not defined.",
                $this->pdoStatement->errorCode(),
                $this->pdoStatement->errorInfo()
            );
        }

        return $this;
    }
    /**
     * Define if the statement will execute automatically when trying to fetch data.
     *
     * @param bool $flag
     * @return $this
     */
    public function setAutoExecute(bool $flag)
    {
        $this->autoExecuteEnabled = $flag;

        return $this;
    }

    /**
     * Execute the statement.
     *
     * @return bool TRUE on success or FALSE on failure.
     * @throws StatementException
     */
    public function execute(): bool
    {
        try {
            $this->wasExecuted = true;

            if ($this->pdoStatement->execute()) {
                $this->wasExecutedSuccessfully = true;
                return true;
            }

            return false;
        } catch (\PDOException $e) {
            throw new StatementException(
                $this->pdoStatement->queryString,
                $e->getMessage(),
                $e->getCode(),
                $this->pdoStatement->errorInfo(),
                $e
            );
        }
    }

    /**
     * Returns the number of rows affected by the SQL statement.
     * If there is no result set, returns 0.
     *
     * @return int
     */
    public function getRowCount(): int
    {
        return $this->pdoStatement->rowCount();
    }

    /**
     * Returns the number of columns in the result set.
     * If there is no result set, returns 0.
     *
     * @return int
     */
    public function getColumnCount(): int
    {
        return $this->pdoStatement->columnCount();
    }

    /**
     * Returns metadata for a column in a result set.
     * Returns FALSE if the requested column does not exist in the result set, or if no result set exists.
     *
     * @param int $columnNumber
     * @return array|false
     */
    public function getColumnMeta(int $columnNumber)
    {
        return $this->pdoStatement->getColumnMeta($columnNumber);
    }

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @return bool
     * @throws StatementException
     */
    public function closeCursor(): bool
    {
        try {
            if ($this->pdoStatement->closeCursor()) {
                $this->wasExecuted = false;

                return true;
            }

            return false;
        } catch (\PDOException $e) {
            throw new StatementException(
                $this->pdoStatement->queryString,
                $e->getMessage(),
                $e->getCode(),
                $this->pdoStatement->errorInfo(),
                $e
            );
        }
    }

    /**
     * Advances to the next rowset in a multi-rowset statement handle.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function nextRowset()
    {
        try {
            return $this->pdoStatement->nextRowset();
        } catch (\PDOException $e) {
            throw new StatementException(
                $this->pdoStatement->queryString,
                $e->getMessage(),
                $e->getCode(),
                $this->pdoStatement->errorInfo(),
                $e
            );
        }
    }

    /**
     * Fetches the next row from a result set according to cursor.
     *
     * @return mixed|null
     * @throws StatementException
     */
    private function internalFetch()
    {
        try {
            // Auto execute block

            if ($this->autoExecuteEnabled && !$this->wasExecuted) {
                $this->execute();
            }

            // Validate previous execution

            if (!$this->wasExecutedSuccessfully) {
                throw new StatementException(
                    $this->pdoStatement->queryString,
                    'Fetching without previous successful execution.',
                    'CZ001'
                );
            }

            // Fetch the row

            $row = $this->pdoStatement->fetch();

            // Return result

            if ($row === false && $this->pdoStatement->errorCode() === '00000') {
                return null;
            }

            return $row;
        } catch (\PDOException $e) {
            throw new StatementException(
                $this->pdoStatement->queryString,
                $e->getMessage(),
                $e->getCode(),
                $this->pdoStatement->errorInfo(),
                $e
            );
        }
    }

    // CUSTOM FETCH METHODS

    /**
     * Fetches a row from the result set and assigns the values of the columns to the PHP variables to which
     * they were bound with the bindColumn() method.
     *
     * @return bool
     * @throws StatementException
     */
    public function fetchBound()
    {
        $this->pdoStatement->setFetchMode(\PDO::FETCH_BOUND);

        return $this->internalFetch();
    }

    /**
     * Fetches a row from the result set and returns the following:
     * - An associative array, if data was found.
     * - Null, if there is no data.
     * - False, if there was an error.
     *
     * @return mixed|null
     * @throws StatementException
     */
    public function fetchAsArray()
    {
        $this->pdoStatement->setFetchMode(\PDO::FETCH_ASSOC);

        return $this->internalFetch();
    }

    /**
     * Fetches a row from the result set and returns the following:
     * - An object, if data was found.
     * - Null, if there is no data.
     * - False, if there was an error.
     *
     * @param string $className Name of the created class.
     * @param array|null $classArguments Elements of this array are passed to the constructor.
     * @return mixed|null
     * @throws StatementException
     */
    public function fetchAsObject(string $className = 'stdClass', array $classArguments = null)
    {
        // Validate arguments

        if (!class_exists($className)) {
            throw new \InvalidArgumentException('The argument $className points to a nonexistent class.');
        }

        // Fetch the row as object

        $this->pdoStatement->setFetchMode(\PDO::FETCH_CLASS, $className, (array)$classArguments);

        return $this->internalFetch();
    }

    /**
     * Fetches a row from the result set and updates an existing object, mapping the columns as named properties.
     *
     * @param object $object Object to update.
     * @return object|bool
     * @throws StatementException
     */
    public function fetchIntoObject($object)
    {
        // Validations

        if (!is_object($object)) {
            throw new \InvalidArgumentException('The argument $object is not a valid object.');
        }

        // Fetch the row as object

        $this->pdoStatement->setFetchMode(\PDO::FETCH_INTO, $object);

        return $this->internalFetch();
    }

    /**
     * Returns the value of a single column from the next row of the result set.
     *
     * @param string $column Name of column you wish to retrieve.
     * @return mixed
     * @throws StatementException
     */
    public function fetchAsColumn(string $column)
    {
        // Validate arguments

        if ($column === '') {
            throw new \InvalidArgumentException('The argument $column is empty.');
        }

        // Fetch the row

        $row = $this->fetchAsArray();

        // More validations

        if ($row === false || $row === null) {
            return $row;
        }

        if (!isset($row[$column])) {
            throw new StatementException(
                $this->pdoStatement->queryString,
                "The column '{$column}' is not present in the result set.",
                'CZ002'
            );
        }

        // Return result

        return $row[$column];
    }

    /**
     * Returns an array containing values of a single column retrieved from the result set rows.
     *
     * @param string $column Name of column you wish to retrieve.
     * @param string $index_by Name of the column you want to assign as a row key.
     * @return array|bool
     * @throws StatementException
     */
    public function fetchAllAsColumn(string $column, string $index_by = null)
    {
        try {
            // Validations

            if ($column == '') {
                throw new \InvalidArgumentException('The argument $column is not a valid string.');
            }

            if (isset($index_by) && $index_by == '') {
                throw new \InvalidArgumentException('The argument $index_by is not a valid string.');
            }

            // Auto execute block

            if ($this->autoExecuteEnabled && !$this->wasExecuted) {
                $this->execute();
            }

            // Set initial values

            $result = [];
            $row = $this->pdoStatement->fetch(\PDO::FETCH_ASSOC);

            // More validations

            if ($row === false) {
                $this->pdoStatement->closeCursor();

                if ($this->pdoStatement->errorCode() === '00000') {
                    return null;
                }

                return false;
            }

            if (!isset($row[$column])) {
                throw new StatementException(
                    $this->pdoStatement->queryString,
                    "The column '{$column}' is not present in the result set.",
                    'CZ002'
                );
            }

            if ($index_by && !isset($row[$index_by])) {
                throw new StatementException(
                    $this->pdoStatement->queryString,
                    "The column '{$index_by}' is not present in the result set.",
                    'CZ002'
                );
            }

            // Traversing the remaining rows

            while ($row) {
                if ($index_by) {
                    $result[$row[$index_by]] = $row[$column];
                } else {
                    $result[] = $row[$column];
                }

                $row = $this->pdoStatement->fetch(\PDO::FETCH_ASSOC);
            }

            // Clear state and return result

            $this->pdoStatement->closeCursor();

            return $result;
        } catch (\PDOException $e) {
            throw new StatementException(
                $this->pdoStatement->queryString,
                $e->getMessage(),
                $e->getCode(),
                $this->pdoStatement->errorInfo(),
                $e
            );
        }
    }

    /**
     * Returns an associative array containing all of the result set.
     *
     * @param string $index_by Name of the column you want to assign as a row key.
     * @param string $group_by Name of the columns with which you want to group the result. You can include
     *                         maximum 3 columns by separating them with commas.
     * @return array|bool
     * @throws StatementException
     */
    public function fetchAllAsArray(string $index_by = null, string $group_by = null)
    {
        try {
            // Validations

            if (isset($index_by) && $index_by == '') {
                throw new \InvalidArgumentException('The argument $index_by is not a valid string.');
            }

            if (isset($group_by) && $group_by == '') {
                throw new \InvalidArgumentException('The argument $group_by is not a valid string.');
            }

            // Auto execute block

            if ($this->autoExecuteEnabled && !$this->wasExecuted) {
                $this->execute();
            }

            // Set initial values

            $result = [];
            $group_by_count = 0;
            $row = $this->pdoStatement->fetch(\PDO::FETCH_ASSOC);

            // More validations

            if ($row === false) {
                $this->pdoStatement->closeCursor();

                if ($this->pdoStatement->errorCode() === '00000') {
                    return null;
                }

                return false;
            }

            if ($index_by && !array_key_exists($index_by, $row)) {
                throw new StatementException(
                    $this->pdoStatement->queryString,
                    "The column '{$index_by}' is not present in the result set.",
                    'CZ002'
                );
            }

            if ($group_by) {
                $group_by = explode(',', str_replace(' ', '', $group_by));
                $group_by_count = count($group_by);

                if ($group_by_count > 3) {
                    throw new \InvalidArgumentException('You have exceeded the limit of 3 columns to group-by.');
                }

                foreach ($group_by as $column) {
                    $column_err = [];

                    if (!array_key_exists($column, $row)) {
                        $column_err[] = $column;
                    }

                    if ($column_err) {
                        throw new StatementException(
                            $this->pdoStatement->queryString,
                            'Some columns to group-by (' . implode(', ', $column_err) .
                            ') are not present in the result set.',
                            'CZ002'
                        );
                    }
                }
            }

            // Traversing the remaining rows

            while ($row) {
                if (!$index_by && !$group_by) {
                    $result[] = $row;
                } elseif ($index_by && !$group_by) {
                    $result[$row[$index_by]] = $row;
                } elseif ($index_by && $group_by) {
                    switch ($group_by_count) {
                        case 3:
                            $result[$row[$group_by[0]]][$row[$group_by[1]]][$row[$group_by[2]]][$row[$index_by]] = $row;
                            break;
                        case 2:
                            $result[$row[$group_by[0]]][$row[$group_by[1]]][$row[$index_by]] = $row;
                            break;
                        case 1:
                            $result[$row[$group_by[0]]][$row[$index_by]] = $row;
                            break;
                    }
                } elseif (!$index_by && $group_by) {
                    switch ($group_by_count) {
                        case 3:
                            $result[$row[$group_by[0]]][$row[$group_by[1]]][$row[$group_by[2]]][] = $row;
                            break;
                        case 2:
                            $result[$row[$group_by[0]]][$row[$group_by[1]]][] = $row;
                            break;
                        case 1:
                            $result[$row[$group_by[0]]][] = $row;
                            break;
                    }
                }

                $row = $this->pdoStatement->fetch(\PDO::FETCH_ASSOC);
            }

            // Clear state and return result

            $this->pdoStatement->closeCursor();

            return $result;
        } catch (\PDOException $e) {
            throw new StatementException(
                $this->pdoStatement->queryString,
                $e->getMessage(),
                $e->getCode(),
                $this->pdoStatement->errorInfo(),
                $e
            );
        }
    }

    /**
     * Returns an array of objects containing all of the result set.
     *
     * @param string $class_name Name of the class you want to instantiate.
     * @param array $class_arguments Elements of this array are passed to the constructor of the class instantiated.
     * @param string $index_by Name of the column you want to assign as a row key.
     * @param string $group_by Name of the columns with which you want to group the result. You can include
     *                         maximum 3 columns by separating them with commas.
     * @return array|bool
     * @throws StatementException
     */
    public function fetchAllAsObject(
        string $class_name = 'stdClass',
        array $class_arguments = null,
        string $index_by = null,
        string $group_by = null
    ) {
        try {
            // Validations

            if ($class_name === '') {
                throw new \InvalidArgumentException('The argument $class_name is not a valid string.');
            }

            if (isset($index_by) && $index_by == '') {
                throw new \InvalidArgumentException('The argument $index_by is not a valid string.');
            }

            if (isset($group_by) && $group_by == '') {
                throw new \InvalidArgumentException('The argument $group_by is not a valid string.');
            }

            // Auto execute block

            if ($this->autoExecuteEnabled && !$this->wasExecuted) {
                $this->execute();
            }

            // Set initial values

            $result = [];
            $groupByCount = 0;
            $row = $this->pdoStatement->fetchObject($class_name, (array)$class_arguments);

            // More validations

            if ($row === false) {
                $this->pdoStatement->closeCursor();

                if ($this->pdoStatement->errorCode() === '00000') {
                    return null;
                }

                return false;
            }

            if ($index_by && !property_exists($row, $index_by)) {
                throw new StatementException(
                    $this->pdoStatement->queryString,
                    "The column '{$index_by}' is not present in the result set.",
                    'CZ002'
                );
            }

            $getProperty = function ($object, $property) {
                return $object->{$property};
            };

            $checkProperty = function ($object, $property) {
                return property_exists($object, $property);
            };

            if ($group_by) {
                $group_by = explode(',', str_replace(' ', '', $group_by));
                $groupByCount = count($group_by);

                if ($groupByCount > 3) {
                    throw new \InvalidArgumentException(
                        'You have exceeded the limit of 3 columns to group-by.'
                    );
                }

                $column_err = [];
                if ($class_name != 'stdClass') {
                    $checkProperty = \Closure::bind($checkProperty, null, $row);
                }

                foreach ($group_by as $column) {
                    if (!$checkProperty($row, $column)) {
                        $column_err[] = $column;
                    }
                }

                if ($column_err) {
                    throw new StatementException(
                        $this->pdoStatement->queryString,
                        'Some columns to group-by (' . implode(', ', $column_err) .
                        ') are not present in the result set.',
                        'CZ002'
                    );
                }
            }

            // Traversing the remaining rows

            while ($row) {
                if ($class_name != 'stdClass') {
                    $getProperty = \Closure::bind($getProperty, null, $row);
                }

                if (!$index_by && !$group_by) {
                    $result[] = $row;
                } elseif ($index_by && !$group_by) {
                    $index = $getProperty($row, $index_by);
                    $result[$index] = $row;
                } elseif ($index_by && $group_by) {
                    $index = $getProperty($row, $index_by);
                    switch ($groupByCount) {
                        case 3:
                            $group_by_0 = $getProperty($row, $group_by[0]);
                            $group_by_1 = $getProperty($row, $group_by[1]);
                            $group_by_2 = $getProperty($row, $group_by[2]);
                            $result[$group_by_0][$group_by_1][$group_by_2][$index] = $row;
                            break;
                        case 2:
                            $group_by_0 = $getProperty($row, $group_by[0]);
                            $group_by_1 = $getProperty($row, $group_by[1]);
                            $result[$group_by_0][$group_by_1][$index] = $row;
                            break;
                        case 1:
                            $group_by_0 = $getProperty($row, $group_by[0]);
                            $result[$group_by_0][$index] = $row;
                            break;
                    }
                } elseif (!$index_by && $group_by) {
                    switch ($groupByCount) {
                        case 3:
                            $group_by_0 = $getProperty($row, $group_by[0]);
                            $group_by_1 = $getProperty($row, $group_by[1]);
                            $group_by_2 = $getProperty($row, $group_by[2]);
                            $result[$group_by_0][$group_by_1][$group_by_2][] = $row;
                            break;
                        case 2:
                            $group_by_0 = $getProperty($row, $group_by[0]);
                            $group_by_1 = $getProperty($row, $group_by[1]);
                            $result[$group_by_0][$group_by_1][] = $row;
                            break;
                        case 1:
                            $group_by_0 = $getProperty($row, $group_by[0]);
                            $result[$group_by_0][] = $row;
                            break;
                    }
                }

                $row = $this->pdoStatement->fetchObject($class_name, (array)$class_arguments);
            }

            // Clear state and return result

            $this->pdoStatement->closeCursor();

            return $result;
        } catch (\PDOException $e) {
            throw new StatementException(
                $this->pdoStatement->queryString,
                $e->getMessage(),
                $e->getCode(),
                $this->pdoStatement->errorInfo(),
                $e
            );
        }
    }
}
