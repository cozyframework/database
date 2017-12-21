<?php

namespace Cozy\Database;

class Statement
{
    /** @var \PDOStatement */
    protected $pdoStatement;
    protected $wasExecuted = false;
    protected $autoExecute = true;
    protected $paramsMap = [];  // ['param_name' => 'type']

    /**
     * Statement constructor that wraps a PDOStatement object.
     *
     * @param \PDOStatement $pdoStatement
     */
    public function __construct(\PDOStatement $pdoStatement)
    {
        $this->pdoStatement = $pdoStatement;
    }

    /**
     * Returns the wrapped PDO statement object.
     *
     * @return \PDOStatement
     */
    public function getPdoStatement()
    {
        return $this->pdoStatement;
    }

    /**
     * Returns error information about the last operation performed by this statement.
     *
     * @return array
     */
    public function getErrorInfo()
    {
        return $this->pdoStatement->errorInfo();
    }

    /**
     * Map out the parameters of this statement.
     *
     * @param array $params A key pair array where the key is parameter's name and the value is parameter's type.
     * @return $this
     */
    public function mapParams(array $params)
    {
        $this->paramsMap = [];

        foreach ($params as $name => $type) {
            // The parameters with named placeholders must start with character ':'
            if (is_string($name) && strpos($name, ':') !== 0) {
                $name = ':' . $name;
            }

            // The parameters with positional ? placeholders must start with number 1
            if (array_key_exists(0, $params)) {
                $name++;
            }

            if ($type === 'int' || $type === 'integer' || $type === \PDO::PARAM_INT) {
                $this->paramsMap[$name] = \PDO::PARAM_INT;
            } elseif ($type === 'bool' || $type === 'boolean' || $type === \PDO::PARAM_BOOL) {
                $this->paramsMap[$name] = \PDO::PARAM_BOOL;
            } elseif ($type === 'lob' || $type === 'blob' || $type === \PDO::PARAM_LOB) {
                $this->paramsMap[$name] = \PDO::PARAM_LOB;
            } elseif ($type === 'null' || $type === \PDO::PARAM_NULL) {
                $this->paramsMap[$name] = \PDO::PARAM_NULL;
            } else {
                $this->paramsMap[$name] = \PDO::PARAM_STR;
            }
        }

        return $this;
    }

    /**
     * Binds values to mapped parameters.
     *
     * @param array $values A key pair array where the key is parameter's name.
     * @return $this
     */
    public function bindValues(array $values)
    {
        // Validations

        if (!$this->paramsMap) {
            throw new \RuntimeException('No mapped parameters found.');
        }

        if (!$values) {
            throw new \InvalidArgumentException('No values were passed.');
        }

        foreach ($values as $name => $value) {
            // The parameters with named placeholders must start with character ':'
            if (is_string($name) && strpos($name, ':') !== 0) {
                $name = ':' . $name;
            }

            // The parameters with positional ? placeholders must start with number 1
            if (array_key_exists(0, $values)) {
                $name++;
            }

            if (!isset($this->paramsMap[$name])) {
                throw new \InvalidArgumentException("The parameter [{$name}] was not previously mapped.");
            }

            if ($value === null) {
                if (!$this->pdoStatement->bindValue($name, null, \PDO::PARAM_NULL)) {
                    throw new \RuntimeException("Error binding the parameter [{$name}].");
                }
            } else {
                if (!$this->pdoStatement->bindValue($name, $value, $this->paramsMap[$name])) {
                    throw new \RuntimeException("Error binding the parameter [{$name}].");
                }
            }
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
        $this->autoExecute = $flag;

        return $this;
    }

    /**
     * Execute the statement and return the number of rows that were modified or deleted.
     * If no rows were affected, this method returns 0. This method may return Boolean FALSE, but may also return a
     * non-Boolean value which evaluates to FALSE, so use the === operator for testing the return value of this method.
     *
     * @param array|null $valuesToBind A key pair array where the key is parameter's name.
     * @return int|false
     */
    public function execute(array $valuesToBind = null)
    {
        if ($valuesToBind) {
            $this->bindValues($valuesToBind);
        }

        $this->wasExecuted = true;

        if ($this->pdoStatement->execute()) {
            return $this->pdoStatement->rowCount();
        }

        return false;
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
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
        // Validations

        if ($columnNumber < 0) {
            throw new \InvalidArgumentException('The argument $columnNumber is not a valid integer.');
        }

        return $this->pdoStatement->getColumnMeta($columnNumber);
    }

    // CUSTOM FETCH METHODS

    /**
     * Fetches the first row from the result set and returns it as an associative array.
     *
     * @return bool|array
     */
    public function fetchFirstAsArray()
    {
        // Auto execute block

        if (!$this->wasExecuted || $this->autoExecute) {
            if ($this->execute() === false) {
                return false;
            }
        }

        // Fetch the first row

        $row = $this->pdoStatement->fetch(\PDO::FETCH_ASSOC);

        // Clear state and return result

        $this->pdoStatement->closeCursor();

        if ($row === false && $this->pdoStatement->errorCode() === '00000') {
            return null;
        }

        return $row;
    }

    /**
     * Fetches the first row from the result set and returns it as an object.
     *
     * @param string $className Name of the created class.
     * @param array|null $arguments Elements of this array are passed to the constructor.
     * @return bool|object
     */
    public function fetchFirstAsObject($className = 'stdClass', array $arguments = null)
    {
        // Validations

        if (!is_string($className) || $className == '') {
            throw new \InvalidArgumentException('The argument $className is not a valid string.');
        }

        // Auto execute block

        if (!$this->wasExecuted || $this->autoExecute) {
            if ($this->execute() === false) {
                return false;
            }
        }

        // Fetch the first row as object

        $row = $this->pdoStatement->fetchObject($className, $arguments);

        // Clear state and return result

        $this->pdoStatement->closeCursor();

        if ($row === false && $this->pdoStatement->errorCode() === '00000') {
            return null;
        }

        return $row;
    }

    /**
     * Fetches the first row from the result set and updates an existing object, mapping the columns as named properties.
     *
     * @param string $object Object to update.
     * @return bool|object
     */
    public function fetchFirstIntoObject($object)
    {
        // Validations

        if (!is_object($object)) {
            throw new \InvalidArgumentException('The argument $object is not a valid object.');
        }

        // Auto execute block

        if (!$this->wasExecuted || $this->autoExecute) {
            if ($this->execute() === false) {
                return false;
            }
        }

        // Fetch the first fow as object

        $this->pdoStatement->setFetchMode(\PDO::FETCH_INTO, $object);
        $row = $this->pdoStatement->fetch();

        // Clear state and return result

        $this->pdoStatement->closeCursor();

        if ($row === false && $this->pdoStatement->errorCode() === '00000') {
            return null;
        }

        return $row;
    }

    /**
     * Returns the value of a single column from the first row of the result set.
     *
     * @param string $column Name of column you wish to retrieve.
     * @return mixed
     */
    public function fetchFirstAsColumn($column)
    {
        // Validations

        if (!is_string($column) || $column == '') {
            throw new \InvalidArgumentException('The argument $column is not a valid string.');
        }

        // Auto execute block

        if (!$this->wasExecuted || $this->autoExecute) {
            if ($this->execute() === false) {
                return false;
            }
        }

        // Fetch the first row
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
            throw new \InvalidArgumentException('The column to fetch is not present in the result set.');
        }

        // Clear state and return result

        $this->pdoStatement->closeCursor();

        return $row[$column];
    }

    /**
     * Returns an array containing values of a single column retrieved from the result set rows.
     *
     * @param string $column Name of column you wish to retrieve.
     * @param string $indexBy Name of the column you want to assign as a row key.
     * @return array|bool
     */
    public function fetchAllAsColumn($column, $indexBy = null)
    {
        // Validations

        if (!is_string($column) || $column == '') {
            throw new \InvalidArgumentException('The argument $column is not a valid string.');
        }

        if (isset($indexBy) && (!is_string($indexBy) || $indexBy == '')) {
            throw new \InvalidArgumentException('The argument $indexBy is not a valid string.');
        }

        // Auto execute block

        if (!$this->wasExecuted || $this->autoExecute) {
            if ($this->execute() === false) {
                return false;
            }
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
            throw new \InvalidArgumentException('The column to fetch is not present in the result set.');
        }

        if ($indexBy && !isset($row[$indexBy])) {
            throw new \InvalidArgumentException('The column to index by is not present in the result set.');
        }

        // Traversing the remaining rows

        while ($row) {
            if ($indexBy) {
                $result[$row[$indexBy]] = $row[$column];
            } else {
                $result[] = $row[$column];
            }

            $row = $this->pdoStatement->fetch(\PDO::FETCH_ASSOC);
        }

        // Clear state and return result

        $this->pdoStatement->closeCursor();

        return $result;
    }

    /**
     * Returns an associative array containing all of the result set.
     *
     * @param string $indexBy Name of the column you want to assign as a row key.
     * @param string $groupBy Name of the columns with which you want to group the result. You can include max. 3 columns by separating them with commas.
     * @return array|bool
     */
    public function fetchAllAsArray($indexBy = null, $groupBy = null)
    {
        // Validations

        if (isset($indexBy) && (!is_string($indexBy) || $indexBy == '')) {
            throw new \InvalidArgumentException('The argument $indexBy is not a valid string.');
        }

        if (isset($groupBy) && (!is_string($groupBy) || $groupBy == '')) {
            throw new \InvalidArgumentException('The argument $groupBy is not a valid string.');
        }

        // Auto execute block

        if (!$this->wasExecuted || $this->autoExecute) {
            if ($this->execute() === false) {
                return false;
            }
        }

        // Set initial values

        $result = [];
        $groupByCount = 0;
        $row = $this->pdoStatement->fetch(\PDO::FETCH_ASSOC);

        // More validations

        if ($row === false) {
            $this->pdoStatement->closeCursor();

            if ($this->pdoStatement->errorCode() === '00000') {
                return null;
            }

            return false;
        }

        if ($indexBy && !isset($row[$indexBy])) {
            throw new \InvalidArgumentException('The column to index-by is not present in the result set.');
        }

        if ($groupBy) {
            $groupBy = explode(',', str_replace(' ', '', $groupBy));
            $groupByCount = count($groupBy);

            if ($groupByCount > 3) {
                throw new \InvalidArgumentException('You have exceeded the limit of 3 columns to group-by.');
            }

            foreach ($groupBy as $column) {
                $columnErr = [];

                if (!isset($row[$column])) {
                    $columnErr[] = $column;
                }

                if ($columnErr) {
                    throw new \InvalidArgumentException('Some columns to group-by (' .
                        implode(', ', $columnErr)
                        . ') are not present in the result set.');
                }
            }
        }

        // Traversing the remaining rows

        while ($row) {
            if (!$indexBy && !$groupBy) {
                $result[] = $row;
            } elseif ($indexBy && !$groupBy) {
                $result[$row[$indexBy]] = $row;
            } elseif ($indexBy && $groupBy) {
                switch ($groupByCount) {
                    case 3:
                        $result[$row[$groupBy[0]]][$row[$groupBy[1]]][$row[$groupBy[2]]][$row[$indexBy]] = $row;
                        break;
                    case 2:
                        $result[$row[$groupBy[0]]][$row[$groupBy[1]]][$row[$indexBy]] = $row;
                        break;
                    case 1:
                        $result[$row[$groupBy[0]]][$row[$indexBy]] = $row;
                        break;
                }
            } elseif (!$indexBy && $groupBy) {
                switch ($groupByCount) {
                    case 3:
                        $result[$row[$groupBy[0]]][$row[$groupBy[1]]][$row[$groupBy[2]]][] = $row;
                        break;
                    case 2:
                        $result[$row[$groupBy[0]]][$row[$groupBy[1]]][] = $row;
                        break;
                    case 1:
                        $result[$row[$groupBy[0]]][] = $row;
                        break;
                }
            }

            $row = $this->pdoStatement->fetch(\PDO::FETCH_ASSOC);
        }

        // Clear state and return result

        $this->pdoStatement->closeCursor();

        return $result;
    }

    /**
     * Returns an array of objects containing all of the result set.
     *
     * @param string $className Name of the class you want to instantiate.
     * @param array $classArgs Elements of this array are passed to the constructor of the class instantiated.
     * @param string $indexBy Name of the column you want to assign as a row key.
     * @param string $groupBy Name of the columns with which you want to group the result. You can include max. 3 columns by separating them with commas.
     * @return array|bool
     */
    public function fetchAllAsObject($className = 'stdClass', array $classArgs = null, $indexBy = null, $groupBy = null)
    {
        // Validations

        if (!is_string($className) || $className === '') {
            throw new \InvalidArgumentException('The argument $className is not a valid string.');
        }

        if (isset($indexBy) && (!is_string($indexBy) || $indexBy == '')) {
            throw new \InvalidArgumentException('The argument $indexBy is not a valid string.');
        }

        if (isset($groupBy) && (!is_string($groupBy) || $groupBy == '')) {
            throw new \InvalidArgumentException('The argument $groupBy is not a valid string.');
        }

        // Auto execute block

        if (!$this->wasExecuted || $this->autoExecute) {
            if ($this->execute() === false) {
                return false;
            }
        }

        // Set initial values

        $result = [];
        $groupByCount = 0;
        $row = $this->pdoStatement->fetchObject($className, (array)$classArgs);

        // More validations

        if ($row === false) {
            $this->pdoStatement->closeCursor();

            if ($this->pdoStatement->errorCode() === '00000') {
                return null;
            }

            return false;
        }

        if ($indexBy && !property_exists($row, $indexBy)) {
            throw new \InvalidArgumentException('The column to index-by is not present in the result set.');
        }

        if ($groupBy) {
            $groupBy = explode(',', str_replace(' ', '', $groupBy));
            $groupByCount = count($groupBy);

            if ($groupByCount > 3) {
                throw new \InvalidArgumentException('You have exceeded the limit of 3 columns to group-by.');
            }

            foreach ($groupBy as $column) {
                $columnErr = [];

                if (!property_exists($row, $column)) {
                    $columnErr[] = $column;
                }

                if ($columnErr) {
                    throw new \InvalidArgumentException('Some columns to group-by (' .
                        implode(', ', $columnErr)
                        . ') are not present in the result set.');
                }
            }
        }

        // Traversing the remaining rows

        while ($row) {
            if (!$indexBy && !$groupBy) {
                $result[] = $row;
            } elseif ($indexBy && !$groupBy) {
                $result[$row->{$indexBy}] = $row;
            } elseif ($indexBy && $groupBy) {
                switch ($groupByCount) {
                    case 3:
                        $result[$row->{$groupBy[0]}][$row->{$groupBy[1]}][$row->{$groupBy[2]}][$row->{$indexBy}] = $row;
                        break;
                    case 2:
                        $result[$row->{$groupBy[0]}][$row->{$groupBy[1]}][$row->{$indexBy}] = $row;
                        break;
                    case 1:
                        $result[$row->{$groupBy[0]}][$row->{$indexBy}] = $row;
                        break;
                }
            } elseif (!$indexBy && $groupBy) {
                switch ($groupByCount) {
                    case 3:
                        $result[$row->{$groupBy[0]}][$row->{$groupBy[1]}][$row->{$groupBy[2]}][] = $row;
                        break;
                    case 2:
                        $result[$row->{$groupBy[0]}][$row->{$groupBy[1]}][] = $row;
                        break;
                    case 1:
                        $result[$row->{$groupBy[0]}][] = $row;
                        break;
                }
            }

            $row = $this->pdoStatement->fetchObject($className, (array)$classArgs);
        }

        // Clear state and return result

        $this->pdoStatement->closeCursor();

        return $result;
    }
}
