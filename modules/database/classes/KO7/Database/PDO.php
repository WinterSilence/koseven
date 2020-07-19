<?php

/**
 * PDO database connection.
 *
 * @package    KO7/Database
 * @category   Drivers
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.dev/LICENSE
 */
class KO7_Database_PDO extends Database
{

    // PDO uses no quoting for identifiers
    protected $_identifier = '';

    public function __construct($name, array $config)
    {
        parent::__construct($name, $config);

        if (isset($this->_config['identifier'])) {
            // Allow the identifier to be overloaded per-connection
            $this->_identifier = (string) $this->_config['identifier'];
        }
    }

    public function connect()
    {
        if ($this->_connection) {
            return;
        }

        // Extract the connection parameters, adding required variabels
        extract(
            $this->_config['connection'] + [
                'dsn' => '',
                'username' => null,
                'password' => null,
                'persistent' => false,
            ]
        );

        // Clear the connection parameters for security
        unset($this->_config['connection']);

        // Force PDO to use exceptions for all errors
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

        if (! empty($persistent)) {
            // Make the connection persistent
            $options[PDO::ATTR_PERSISTENT] = true;
        }

        try {
            // Create a new PDO connection
            $this->_connection = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new Database_Exception(
                ':error',
                [':error' => $e->getMessage()],
                $e->getCode()
            );
        }

        if (! empty($this->_config['charset'])) {
            // Set the character set
            $this->set_charset($this->_config['charset']);
        }
    }

    /**
     * Create or redefine a SQL aggregate function.
     * [!!] Works only with SQLite
     *
     * @link http://php.net/manual/function.pdo-sqlitecreateaggregate
     * @param string $name Name of the SQL function to be created or redefined
     * @param callback $step Called for each row of a result set
     * @param callback $final Called after all rows of a result set have been processed
     * @param integer $arguments Number of arguments that the SQL function takes
     * @return  boolean
     */
    public function create_aggregate($name, $step, $final, $arguments = -1)
    {
        $this->_connection or $this->connect();

        return $this->_connection->sqliteCreateAggregate(
            $name,
            $step,
            $final,
            $arguments
        );
    }

    /**
     * Create or redefine a SQL function.
     * [!!] Works only with SQLite
     *
     * @link http://php.net/manual/function.pdo-sqlitecreatefunction
     * @param string $name Name of the SQL function to be created or redefined
     * @param callback $callback Callback which implements the SQL function
     * @param integer $arguments Number of arguments that the SQL function takes
     * @return  boolean
     */
    public function create_function($name, $callback, $arguments = -1)
    {
        $this->_connection or $this->connect();

        return $this->_connection->sqliteCreateFunction(
            $name,
            $callback,
            $arguments
        );
    }

    public function disconnect()
    {
        // Destroy the PDO object
        $this->_connection = null;

        return parent::disconnect();
    }

    public function set_charset($charset)
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        // This SQL-92 syntax is not supported by all drivers
        $this->_connection->exec('SET NAMES ' . $this->quote($charset));
    }

    public function query($type, $sql, $as_object = false, array $params = null)
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        if (KO7::$profiling) {
            // Benchmark this query for the current instance
            $benchmark = Profiler::start("Database ({$this->_instance})", $sql);
        }

        try {
            $result = $this->_connection->query($sql);
        } catch (Exception $e) {
            if (isset($benchmark)) {
                // This benchmark is worthless
                Profiler::delete($benchmark);
            }

            // Convert the exception in a database exception
            throw new Database_Exception(
                ':error [ :query ]',
                [
                    ':error' => $e->getMessage(),
                    ':query' => $sql,
                ],
                $e->getCode()
            );
        }

        if (isset($benchmark)) {
            Profiler::stop($benchmark);
        }

        // Set the last query
        $this->last_query = $sql;

        if ($type === Database::SELECT) {
            // Convert the result into an array, as PDOStatement::rowCount is not reliable
            if ($as_object === false) {
                $result->setFetchMode(PDO::FETCH_ASSOC);
            } elseif (is_string($as_object)) {
                $result->setFetchMode(PDO::FETCH_CLASS, $as_object, $params);
            } else {
                $result->setFetchMode(PDO::FETCH_CLASS, 'stdClass');
            }

            $result = $result->fetchAll();

            // Return an iterator of results
            return new Database_Result_Cached($result, $sql, $as_object, $params);
        } elseif ($type === Database::INSERT) {
            // Return a list of insert id and rows created
            return [
                $this->_connection->lastInsertId(),
                $result->rowCount(),
            ];
        } else {
            // Return the number of rows affected
            return $result->rowCount();
        }
    }

    public function begin($mode = null)
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        return $this->_connection->beginTransaction();
    }

    public function commit()
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        return $this->_connection->commit();
    }

    public function rollback()
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        return $this->_connection->rollBack();
    }

    public function list_tables($like = null)
    {
        throw new KO7_Exception(
            'Database method :method is not supported by :class',
            [':method' => __FUNCTION__, ':class' => __CLASS__]
        );
    }

    public function list_columns($table, $like = null, $add_prefix = true)
    {
        throw new KO7_Exception(
            'Database method :method is not supported by :class',
            [':method' => __FUNCTION__, ':class' => __CLASS__]
        );
    }

    public function escape($value)
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        return $this->_connection->quote($value);
    }

} // End Database_PDO
