<?php

declare(strict_types=1);

namespace Cozy\Database\Relational\Configuration;

use Cozy\Database\Relational\Connection;
use Cozy\Database\Relational\Exception;

class SQLiteConfiguration implements ConfigurationInterface
{
    private $dsn;
    private $pdo_options;
    private $pdo;

    public function __construct(string $path, array $pdo_options = [])
    {
        if ($path == 'memory') {
            $this->dsn = 'sqlite::memory:';
        } else {
            $this->dsn = "sqlite:{$path}";
        }

        $this->pdo_options = $pdo_options;
    }

    public function isValid(): bool
    {
        try {
            $this->pdo = new \PDO($this->dsn, null, null, $this->pdo_options);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function buildConnection(): Connection
    {
        try {
            if (!isset($this->pdo) || !($this->pdo instanceof \PDO)) {
                $this->pdo = new \PDO($this->dsn, null, null, $this->pdo_options);
            }

            return new Connection($this->pdo);
        } catch (\PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e->errorInfo);
        }
    }
}
