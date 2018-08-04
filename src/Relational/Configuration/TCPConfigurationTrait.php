<?php

declare(strict_types=1);

namespace Cozy\Database\Relational\Configuration;

use Cozy\Database\Relational\Connection;
use Cozy\Database\Relational\Exception;

trait TCPConfigurationTrait
{
    /** @var \PDO */
    private $pdo;
    private $dsn;
    private $host;
    private $port;
    private $username;
    private $password;
    private $options = [
        \PDO::ATTR_TIMEOUT => 1,
    ];

    public function isValid(): bool
    {
        $op = @fsockopen($this->host, $this->port, $errno, $errstr, 0.5);

        if (!$op) {
            return false;
        }

        fclose($op);

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
