<?php

declare(strict_types=1);

namespace Cozy\Database\Relational\Configuration;

use Cozy\Database\Relational\Connection;
use Cozy\Database\Relational\Exception;

class DSNConfiguration implements ConfigurationInterface
{
    private $dsn;
    private $username;
    private $password;
    private $options = [
        \PDO::ATTR_TIMEOUT => 1,
    ];
    private $pdo;

    /**
     *  Creates a configuration set representing a connection to a database.
     *
     * @param string $dsn The Data Source Name, or DSN, contains the information required to connect to the database.
     * @param string $username The user name for the DSN string. This parameter is optional for some PDO drivers.
     * @param string $password The password for the DSN string. This parameter is optional for some PDO drivers.
     * @param array $options A key=>value array of PDO driver-specific connection options.
     */
    public function __construct(
        string $dsn,
        string $username,
        string $password,
        array $options = []
    )
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = array_merge($this->options, $options);
    }

    public function isValid(): bool
    {
        try {
            $this->pdo = new \PDO($this->dsn, $this->username, $this->password, $this->options);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function buildConnection(): Connection
    {
        try {

            if (!isset($this->pdo) || !($this->pdo instanceof \PDO)) {
                $this->pdo = new \PDO($this->dsn, $this->username, $this->password, $this->options);
            }

            return new Connection($this->pdo);

        } catch (\PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e->errorInfo);
        }
    }
}
