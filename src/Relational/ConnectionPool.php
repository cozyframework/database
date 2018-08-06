<?php

declare(strict_types=1);

namespace Cozy\Database\Relational;

use Cozy\Database\Relational\Exceptions\Exception;

class ConnectionPool
{
    /** @var array */
    private $pool = [];
    /** @var Connection */
    private $current;
    private $selectionOrder = 1;
    const SELECTION_RANDOM = 1;
    const SELECTION_SEQUENTIAL = 2;

    public function __construct(int $selection_order = self::SELECTION_RANDOM)
    {
        $this->selectionOrder = $selection_order;
    }

    public function addConnection(Connection $connection, string $tag = 'main')
    {
        if (!isset($this->pool[$tag])) {
            $this->pool[$tag] = [];
        }

        $this->pool[$tag][] = $connection;
    }

    public function getConnection(string $tag = 'main'): Connection
    {
        /** @var Connection $connection */

        if (isset($this->current[$tag]) && $this->current[$tag]->isAlive()) {
            return $this->current[$tag];
        }

        $this->current[$tag] = null;

        if (empty($this->pool[$tag])) {
            throw new Exception('There are no available connections in the pool.', 'CZ097');
        }

        if ($this->selectionOrder == self::SELECTION_RANDOM) {
            shuffle($this->pool[$tag]);
        }

//        $connection = array_shift($this->pool[$tag]);
        while (null !== ($connection = array_shift($this->pool[$tag]))) {
            try {
                if ($connection->isAlive()) {
                    return $this->current[$tag] = $connection;
                }
            } catch (\Throwable $e) {
            }
        }

        throw new Exception('There are no live connections available.', 'CZ098');
    }
}
