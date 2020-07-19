<?php

/**
 * Adds basic MPTT functionality to ORM.
 *
 * @package   Kohana/ORM
 * @author    Kohana Team
 * @copyright (c) Kohana Team
 * @license   https://koseven.ga/LICENSE.md
 */
class Kohana_ORM_MPTT extends ORM
{
    const RELATIONSHIP_AFTER = 'after';
    const RELATIONSHIP_FIRST_CHILD = 'first child of';

    /**
     * @var string Current scope
     */
    protected $_scope;

    /**
     * @var string Scope column
     */
    protected $_scope_key = 'scope';

    /**
     * @var string Left key column
     */
    protected $_left_key = 'left_key';

    /**
     * @var string Right key column
     */
    protected $_right_key = 'right_key';

    /**
     * @var string Depth column
     */
    protected $_depth_key = 'depth';

    /**
     * @var array Sibling relationships
     */
    protected $_sibling_relationships = [self::RELATIONSHIP_AFTER];

    /**
     * @var array Child relationships
     */
    protected $_child_relationships = [self::RELATIONSHIP_FIRST_CHILD];
    
    /**
     * Constructs a new model and loads a record if given.
     *
     * @param mixed $id Parameter for find or object to load
     * @param string $scope
     * @return void
     */
    public function __construct($id = null, $scope = '')
    {
        $this->scope($scope);
        parent::__construct($id);
    }

    /**
     * Gets/sets scope.
     *
     * @param string|null $value New scope
     * @return mixed
     */
    public function scope($value = null)
    {
        if ($value !== null) {
            $this->_scope = (string) $value;
            return $this;
        }
        return $this->_scope;
    }

    /**
     * Creates a root node.
     *
     * @param array $data Custom data array(column => value, column2 => value2, ...)
     * @return integer Root id
     * @throws Database_Exception A root node already exists.
     */
    public function create_root(array $data = [])
    {
        // Make sure there isn't already a root node.
        if ($this->has_root()) {
            throw new Database_Exception('A root node already exists.');
        }
        // System data.
        $defaults = [$this->_left_key => 1, $this->_right_key => 2];
        // Add scope to system data.
        $this->_scope !== null and $defaults[$this->_scope_key] = $this->_scope;
        // Merge custom data with system data.
        $data = array_merge($defaults, $data);
        // Create the root node and return the insert_id (root id).
        return DB::insert($this->_table_name, array_keys($data))
            ->values(array_values($data))
            ->execute($this->_db);
    }

    /**
     * Inserts a node structure at a given position.
     * $data accepts two formats:
     * 1 - [column => value, column => value, ...]
     * 2 - [[column => value, column => value], [column => value, ...], ...]
     * When passing numerous nodes in data, `left_key` and `right_key` values
     * must be included to specify the structure.
     * In this case, the `left_key` of the root node is always 1.
     * Their position will automatically be offset when inserting.
     * [
     *   [left_key' => 1, 'right_key' => 6],
     *   ['left_key' => 2, 'right_key' => 5],
     *   ['left_key' => 3, 'right_key' => 4],
     * ];
     * Table specific data is added normally as colum value pairs.
     * Columns that are omitted will fallback to their database default values.
     *
     * @param array    data
     * @param string   relationship to insert with
     * @param int      node id to insert to
     * @return  array    inserted ids
     * @throws  Database_Exception
     */
    public function insert($data, $relationship, $insert_node_id)
    {
        // Make sure we have a root node.
        if (! $this->has_root()) {
            throw new Database_Exception('You must create a root before inserting data.');
        }
        // Make sure the root node doesn't have siblings.
        if ($relationship == self::RELATIONSHIP_AFTER and $insert_node_id == $this->get_root_id()) {
            throw new Database_Exception('The root node cannot have siblings.');
        }
        $inserted_ids = [];

        // Make sure data is an array of arrays.
        ! is_array(reset($data)) and $data = [$data];

        // Make sure we have data, and create the gap for insertion.
        if (count($data) > 0 and $gap_left = $this->_create_gap($relationship, $insert_node_id, count($data) * 2)) {
            $offset = $gap_left - 1;

            foreach ($data as $node) {
                // Add lft and rgt for single inserts.
                if (count($data) == 1) {
                    $node[$this->_left_key] = 1;
                    $node[$this->_right_key] = 2;
                }

                // Add scope.
                $this->_scope !== null and $node[$this->_scope_key] = $this->_scope;

                // Add node offsets.
                $node[$this->_left_key] = $node[$this->_left_key] + $offset;
                $node[$this->_right_key] = $node[$this->_right_key] + $offset;

                // Insert the data.
                $inserted_ids[] = DB::insert($this->_table_name, array_keys($node))
                    ->values(array_values($node))
                    ->execute($this->_db);
            }
        }

        return $inserted_ids;
    }

    /**
     * Moves a node and its children.
     *
     * @param int      node id
     * @param string   relationship to move with [sef::RELATIONSHIP_AFTER, sef::RELATIONSHIP_FIRST_CHILD]
     * @param int      node id to move to
     * @return  bool     moved
     * @throws  Database_Exception
     */
    public function move($node_id, $relationship, $to_node_id)
    {
        $moved = false;

        // Don't allow a node to be moved unto itself.
        if ($node_id == $to_node_id) {
            throw new Database_Exception('A node cannot be moved unto itself.');
        }

        // Get the node we are moving and the one we are moving to.
        if ($node = $this->get_node($node_id) and $to_node = $this->get_node($to_node_id)) {
            // Don't allow the root node to be moved.
            if ($node[$this->_left_key] == 1) {
                throw new Database_Exception('The root node cannot be moved.');
            }
            // Don't allow a parent to become its own child.
            if (
                in_array($relationship, $this->_child_relationships) and
                (
                    $node[$this->_left_key] < $to_node[$this->_left_key] and
                    $node[$this->_right_key] > $to_node[$this->_right_key]
                )
            ) {
                throw new Database_Exception('A parent cannot become a child of its own child.');
            }
            // Database_Exception('The root node cannot have siblings.') is thown in _create_gap().

            // Calculate the size of the gap. (number of node positions we are moving)
            $gap_size = (1 + (($node[$this->_right_key] - ($node[$this->_left_key] + 1)) / 2)) * 2;

            // Create the gap to move to.
            if ($this->_create_gap($relationship, $to_node_id, $gap_size)) {
                // Adjust the node position if it was affected by the gap.
                if ($to_node[$this->_right_key] < $node[$this->_left_key]) {
                    $node[$this->_left_key] = $node[$this->_left_key] + $gap_size;
                    $node[$this->_right_key] = $node[$this->_right_key] + $gap_size;
                }

                // Calculate the increment based on the relationship.
                switch ($relationship) {
                    case self::RELATIONSHIP_FIRST_CHILD:
                        $increment = $to_node[$this->_left_key] + 1 - $node[$this->_left_key];
                        break;
                    case self::RELATIONSHIP_AFTER:
                        $increment = $to_node[$this->_right_key] + 1 - $node[$this->_left_key];
                        break;
                    // Database_Exception(':relationship is not a supported relationship.') is thown in _create_gap().
                }

                // Move the node and its children into the gap.
                $this->_update_position(
                    [$this->_left_key, $this->_right_key],
                    $increment,
                    [
                        [$this->_left_key, '>=', $node[$this->_left_key]],
                        [$this->_right_key, '<=', $node[$this->_right_key]],
                    ]
                );

                // Close the gap created by the moved nodes.
                $limit = $node[$this->_left_key] - 1;
                $increment = $gap_size * -1;
                $this->_update_position($this->_left_key, $increment, [$this->_left_key, '>', $limit]);
                $this->_update_position($this->_right_key, $increment, [$this->_right_key, '>', $limit]);

                $moved = true;
            }
        }

        return $moved;
    }

    /**
     * Deletes a node or nodes, and their children.
     *
     * @param mixed $node_ids node id, or array of node ids to delete
     * @return  array   deleted ids
     */
    public function delete($node_ids)
    {
        $deleted_ids = [];

        // Make sure node_ids is an array.
        ! is_array($node_ids) and $node_ids = [$node_ids];

        // Loop through all the node ids to delete.
        foreach ($node_ids as $node_id) {
            // Get the node to delete.
            $node = $this->get_node($node_id);

            $ids_to_delete = [];

            $tree = $this->get_tree()->as_array();

            // Loop the tree and delete ids.
            foreach ($tree as $key => $val) {
                if ($val[$this->_left_key] >= $node[$this->_left_key] and $val[$this->_right_key] <= $node[$this->_right_key]) {
                    // Save the ids to delete.
                    $ids_to_delete[] = $val[$this->_primary_key];
                    // Remove ids that will be deleted from the tree.
                    unset($tree[$key]);
                }
            }

            // Process the deletions.
            if (! empty($ids_to_delete)) {
                // Delete the node and its children.
                $query = DB::delete($this->_table_name);

                foreach ($ids_to_delete as $id_to_delete) {
                    $query->or_where($this->_primary_key, '=', $id_to_delete);
                }

                $num_deletions = $this->_where_scope($query)->execute($this->_db);

                // We have deletions.
                if ($num_deletions) {
                    // Save the newly deleted ids.
                    $deleted_ids = array_merge($deleted_ids, $ids_to_delete);

                    // Close the gap created by the deletion.
                    $increment = ($num_deletions * 2) * -1;
                    $this->_update_position(
                        $this->_left_key,
                        $increment,
                        [$this->_left_key, '>', $node[$this->_left_key]]
                    );
                    $this->_update_position(
                        $this->_right_key,
                        $increment,
                        [$this->_right_key, '>', $node[$this->_left_key]]
                    );
                }
            }
        }

        $deleted_ids = array_unique($deleted_ids);

        return $deleted_ids;
    }

    /**
     * Gets a node from a node id.
     *
     * @param int $node_id
     * @return mixed node array or FALSE if node does not exist
     */
    public function get_node($node_id)
    {
        $query = DB::select()
            ->from($this->_table_name)
            ->where($this->_primary_key, '=', $node_id);
        return $this->_where_scope($query)
            ->execute($this->_db)
            ->current();
    }

    /**
     * Gets the root node.
     *
     * @return  mixed    root node array, or FALSE if root does not exist
     */
    public function get_root_node()
    {
        $query = DB::select()
            ->from($this->_table_name)
            ->where($this->_left_key, '=', 1);
        return $this->_where_scope($query)
            ->execute($this->_db)
            ->current();
    }

    /**
     * Gets the root id.
     *
     * @return  integer|FALSE  root id or FALSE if root does not exist
     * @uses    self::get_rood_node()
     * @caller  self::insert()
     */
    public function get_root_id()
    {
        $root = $this->get_root_node();
        return isset($root[$this->_primary_key]) ? $root[$this->_primary_key] : false;
    }

    /**
     * Checks if the tree has a root.
     *
     * @return  bool  has root
     */
    public function has_root()
    {
        return (bool) $this->get_root_node();
    }

    /**
     * Gets the tree with an auto calculated depth column.
     *
     * @param integer|NULL     node id (start from a given node)
     * @return  object  tree object
     */
    public function get_tree($node_id = null)
    {
        $query = DB::select('*', [DB::expr('COUNT(p.' . $this->_primary_key . ') - 1'), $this->_depth_key])
            ->from([$this->_table_name, 'p'], [$this->_table_name, 'c'])
            ->where(
                'c.' . $this->_left_key,
                'BETWEEN',
                DB::expr('p.' . $this->_left_key . ' AND p.' . $this->_right_key)
            )
            ->group_by('c.' . $this->_primary_key)
            ->order_by('c.' . $this->_left_key);

        if ($this->_scope !== null) {
            $query->where('p.' . $this->_scope_key, '=', $this->_scope);
            $query->where('c.' . $this->_scope_key, '=', $this->_scope);
        }

        if ($node_id !== null) {
            $subquery = DB::select($this->_left_key)
                ->from($this->_table_name)
                ->where($this->_primary_key, '=', $node_id);
            $query->where('c.' . $this->_left_key, '>=', $subquery);
            
            $subquery = DB::select($this->_right_key)
                ->from($this->_table_name)
                ->where($this->_primary_key, '=', $node_id)
            $query->where('c.' . $this->_right_key, '<=', $subquery);
        }

        $tree = $query->execute($this->_db);

        return $query->execute($this->_db);
    }

    /**
     * Validates a tree. Empty trees are considered valid.
     *
     * @return bool
     */
    public function validate_tree()
    {
        $valid = true;
        $current_depth = 0;
        $ancestors = $positions = [];
        $tree = $this->get_tree()->as_array();

        // Loop through the tree.
        foreach ($tree as $key => $node) {
            // Modify the ancestors on depth change.
            if (isset($current_depth)) {
                if ($node[$this->_depth_key] > $current_depth) {
                    array_push($ancestors, $tree[$key - 1]);
                } elseif ($node[$this->_depth_key] < $current_depth) {
                    for ($i = 0; $i < $current_depth - $node[$this->_depth_key]; $i++) {
                        array_pop($ancestors);
                    }
                }
            }

            // If the node has a parent, set it.
            ! empty($ancestors) and $parent = $ancestors[count($ancestors) - 1];

            /**
             * Perform various checks on the node:
             * 1. left must be smaller than right.
             * 2. left and right cannot be used by other nodes.
             * 3. A child node must be inside its parent.
             */
            if (
                ($node[$this->_left_key] >= $node[$this->_right_key]) or (
                    in_array($node[$this->_left_key], $positions) or
                    in_array($node[$this->_right_key], $positions)
                ) or (
                    isset($parent) and (
                        $node[$this->_left_key] <= $parent[$this->_left_key] or
                        $node[$this->_right_key] >= $parent[$this->_right_key]
                    )
                )
            ) {
                $valid = false;
                break;
            }

            // Set the current depth.
            $current_depth = $node[$this->_depth_key];
            // Save the positions.
            $positions[] = $node[$this->_left_key];
            $positions[] = $node[$this->_right_key];
        }

        // Apply further checks to non-empty trees.
        if (! empty($positions)) {
            // Sort the positions.
            sort($positions);
            // Make sure the last position is not larger than needed.
            if (($positions[count($positions) - 1] - $positions[0] + 1) != count($positions)) {
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Creates a gap in the tree.
     *
     * @param string   relationship to gap with
     * @param int      node id to gap against
     * @param int      gap size
     * @return  mixed    gap left, FALSE on failure
     * @throws  Database_Exception
     */
    protected function _create_gap($relationship, $node_id, $size = 2)
    {
        $gap_left = false;

        // Get the node to move against.
        if ($node = $this->get_node($node_id)) {
            // Don't allow the root node to have siblings.
            if ($node[$this->_left_key] == 1 and in_array($relationship, $this->_sibling_relationships)) {
                throw new Database_Exception('The root node cannot have siblings.');
            }

            // Get parameters depending on the relationship.
            switch ($relationship) {
                case self::RELATIONSHIP_FIRST_CHILD:
                    $limit = $node[$this->_left_key];
                    $gap_left = $node[$this->_left_key] + 1;
                    break;
                case self::RELATIONSHIP_AFTER:
                    $limit = $node[$this->_right_key];
                    $gap_left = $node[$this->_right_key] + 1;
                    break;
                default:
                    // Throw an exception if the relationship doesn't exist.
                    throw new Database_Exception(
                        ':relationship is not a supported relationship.',
                        [':relationship' => $relationship]
                    );
            }

            // Update the node positions to create the gap.
            $this->_update_position($this->_left_key, $size, [$this->_left_key, '>', $limit]);
            $this->_update_position($this->_right_key, $size, [$this->_right_key, '>', $limit]);
        }

        return $gap_left;
    }

    /**
     * Updates lft and/or rgt position columns with where clauses.
     * Columns accepts two formats:
     * 1 - string $this->_left_key or $this->_right_key
     * 2 - [$this->_left_key, $this->_right_key]
     * Where conditions accept two formats:
     * 1 - [column, value, condition]
     * 2 - [[column, value, condition, [...]]
     *
     * @param mixed   column(s) (see above)
     * @param int     increment
     * @param array   where condition(s) (see above)
     * @return  void
     */
    protected function _update_position($columns, $increment, $where)
    {
        // Make sure columns is an array.
        ! is_array($columns) and $columns = [$columns];
        // Make sure where is an array of arrays.
        ! is_array($where[0]) and $where = [$where];
        // Build and run the query.
        $query = DB::update($this->_table_name);

        foreach ($columns as $column) {
            $value = DB::expr($this->_db->quote_column($column) . ' + ' . intval($increment));
            $query->set([$column => $value]);
        }

        foreach ($where as $condition) {
            $query->where($condition[0], $condition[1], $condition[2]);
        }

        $this->_where_scope($query)->execute($this->_db);
    }

    /**
     * Adds a where scope clause in the query.
     *
     * @param object $query
     * @return object
     */
    protected function _where_scope($query)
    {
        if ($this->_scope !== null) {
            $query->where($this->_scope_key, '=', $this->_scope);
        }

        return $query;
    }
}
