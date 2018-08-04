<?php

namespace Cozy\Database\Relational\Configuration;

use Cozy\Database\Relational\Connection;

interface ConfigurationInterface
{
    public function isValid(): bool;
    public function buildConnection(): Connection;
}
