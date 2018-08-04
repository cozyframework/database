<?php

declare(strict_types=1);

namespace Cozy\Database\Relational\Configuration;

class MSSQLConfiguration implements ConfigurationInterface
{
    use TCPConfigurationTrait;

    /**
     *  Creates a configuration set representing a connection to a database.
     *
     * @param string $host The hostname on which the database server resides.
     * @param int $port The port number where the database server is listening (default is 1433).
     * @param string $database The name of the database.
     * @param string $username The user name for the DSN string. This parameter is optional for some PDO drivers.
     * @param string $password The password for the DSN string. This parameter is optional for some PDO drivers.
     * @param array $options A key=>value array of PDO driver-specific connection options.
     */
    public function __construct(
        string $host,
        int $port,
        string $database,
        string $username,
        string $password,
        array $options = []
    ) {
        $this->dsn = "sqlsrv:Server={$host},{$port};Database={$database};LoginTimeout=1;QuotedId=1";
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->options = array_merge($this->options, $options);
    }
}
