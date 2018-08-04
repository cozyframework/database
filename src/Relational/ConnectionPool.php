<?php

declare(strict_types=1);

namespace Cozy\Database\Relational;

use Cozy\Database\Relational\Configuration\ConfigurationInterface;

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

    public function addConfiguration(ConfigurationInterface $configuration, string $tag = 'main')
    {
        if (!isset($this->pool[$tag])) {
            $this->pool[$tag] = [];
        }

        $this->pool[$tag][] = $configuration;
    }

    public function getConnection(string $tag = 'main'): Connection
    {
        if (isset($this->current[$tag]) && $this->current[$tag] instanceof Connection) {
            /** var Connection $this->current[$tag] */
            if ($this->current[$tag]->isAlive()) {
                return $this->current[$tag];
            } else {
                $this->current[$tag] = null;
            }
        }

        if (empty($this->pool[$tag])) {
            throw new Exception('There are no available configurations in the connection pool.', 'CZ097');
        }

        if ($this->selectionOrder == self::SELECTION_RANDOM) {
            shuffle($this->pool[$tag]);
        }

        $configuration = array_shift($this->pool[$tag]);

        while ($configuration !== null) {
            if ($configuration->isValid()) {
                $this->current[$tag] = $configuration->buildConnection();
                break;
            }

            $configuration = array_shift($this->pool[$tag]);
        }

        if (!isset($this->current[$tag])) {
            throw new Exception('There are no valid configurations to build a database connection.', 'CZ098');
        }

        return $this->current[$tag];
    }
}
