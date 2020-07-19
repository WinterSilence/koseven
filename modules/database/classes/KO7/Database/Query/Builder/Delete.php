<?php

/**
 * Database query builder for DELETE statements. See [Query Builder](/database/query/builder) for usage and examples.
 *
 * @package    KO7/Database
 * @category   Query
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.dev/LICENSE
 */
class KO7_Database_Query_Builder_Delete extends Database_Query_Builder_Where
{

    // DELETE FROM ...
    protected $_table;

    /**
     * Set the table for a delete.
     *
     * @param mixed $table table name or array($table, $alias) or object
     * @return  void
     */
    public function __construct($table = null)
    {
        if ($table) {
            // Set the inital table name
            $this->_table = $table;
        }

        // Start the query with no SQL
        return parent::__construct(Database::DELETE, '');
    }

    /**
     * Sets the table to delete from.
     *
     * @param mixed $table table name or array($table, $alias) or object
     * @return  $this
     */
    public function table($table)
    {
        $this->_table = $table;

        return $this;
    }

    /**
     * Compile the SQL query and return it.
     *
     * @param mixed $db Database instance or name of instance
     * @return  string
     */
    public function compile($db = null)
    {
        if (! is_object($db)) {
            // Get the database instance
            $db = Database::instance($db);
        }

        // Start a deletion query
        $query = 'DELETE FROM ' . $db->quote_table($this->_table);

        if (! empty($this->_where)) {
            // Add deletion conditions
            $query .= ' WHERE ' . $this->_compile_conditions($db, $this->_where);
        }

        if (! empty($this->_order_by)) {
            // Add sorting
            $query .= ' ' . $this->_compile_order_by($db, $this->_order_by);
        }

        if ($this->_limit !== null) {
            // Add limiting
            $query .= ' LIMIT ' . $this->_limit;
        }

        $this->_sql = $query;

        return parent::compile($db);
    }

    public function reset()
    {
        $this->_table = null;
        $this->_where = [];

        $this->_parameters = [];

        $this->_sql = null;

        return $this;
    }

} // End Database_Query_Builder_Delete
