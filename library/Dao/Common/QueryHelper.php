<?php
/**
 * The PDO query helper classes
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2005 - 2017, Fitz Zhang <alacner@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: QueryHelper.php 8915 2017-11-05 03:38:45Z alacner $
 */

final class Tii_Dao_Common_QueryHelper
{
    /**
     * @var Tii_Dao_Common_PropelPDO
     */
    private $connection;
    private $queryKey;
    private $dbname;

    public function __construct($connection, $dbname = '')
    {
        $this->connection = $connection;
        $this->dbname = $dbname;
    }

    /**
     * @param string $queryKey index field of fetch-result-array
     * @return Tii_Dao_Common_QueryHelper
     */
    public function setQueryKey($queryKey)
    {
        $queryKey = trim($queryKey);
        if ('' != $queryKey) $this->queryKey = $queryKey;

        return $this;
    }

    private function _unsetQueryKey()
    {
        $this->queryKey = NULL;
        return $this;
    }

    /**
     * Query data
     *
     * @param string $tableName
     * @param string $columns 'columnName1,columnName2,...' If it was array, then extract to assign ...
     * @param string $selection 'columnName1=?, columnName2=?, ...'
     * @param array $selectionArgs ['value1', 'value2', ...]
     * @param string $groupBy 'columnName1'
     * @param string $having
     * @param string $orderBy
     * @param int $limit
     * @param int $offset
     * @return multitype:|multitype:unknown
     */
    public function query($tableName, $columns = NULL, $selection = NULL, $selectionArgs = NULL, $groupBy = NULL, $having = NULL, $orderBy = NULL, $limit = NULL, $offset = NULL)
    {
        if (is_array($columns)) {
            $_option = $columns;
            $columns = NULL;
            isset($_option['columns']) && $columns = $_option['columns'];
            isset($_option['selection']) && $selection = $_option['selection'];
            isset($_option['selection_args']) && $selectionArgs = $_option['selection_args'];
            isset($_option['group_by']) && $groupBy = $_option['group_by'];
            isset($_option['having']) && $having = $_option['having'];
            isset($_option['order_by']) && $orderBy = $_option['order_by'];
            isset($_option['limit']) && $limit = $_option['limit'];
            isset($_option['offset']) && $offset = $_option['offset'];
            unset($_option);
        }

        $sql = $this->_buildQuerySql($tableName, $columns, $selection, $groupBy, $having, $orderBy, $limit, $offset);
        return $this->doQuery($sql, $selectionArgs);
    }

    /**
     * Query data via sql
     *
     * @param $sql 'select * from table where columnName1=? and columnName2=? ...'
     * @param NULL $values ['value1', 'value2', ...]
     * @return array
     */
    public function doQuery($sql, $values = NULL)
    {
        $stmt = $this->connection->prepare($sql);
        $this->bindParams($stmt, $values);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$this->queryKey) return $records;
        if (!isset($records[0]) || !isset($records[0][$this->queryKey])) {
            $this->_unsetQueryKey();
            return $records;
        }

        $newRecords = [];
        foreach ($records as $record) {
            $newRecords[$record[$this->queryKey]] = $record;
        }

        $this->_unsetQueryKey();
        return $newRecords;
    }

    /**
     * Get all column values
     *
     * @param string $tableName
     * @param string $columns 'columnName1,columnName2,...' If it was array, then extract to assign ...
     * @param string $selection 'columnName1=?, columnName2=?, ...'
     * @param array $selectionArgs ['value1', 'value2', ...]
     * @param string $groupBy
     * @param string $having
     * @param string $orderBy
     * @param int $limit
     * @param int $offset
     * @return multitype:
     */
    public function listColumn($tableName, $columns = NULL, $selection = NULL, $selectionArgs = NULL, $groupBy = NULL, $having = NULL, $orderBy = NULL, $limit = NULL, $offset = NULL)
    {
        if (is_array($columns)) {
            $_option = $columns;
            $columns = NULL;
            isset($_option['columns']) && $columns = $_option['columns'];
            isset($_option['selection']) && $selection = $_option['selection'];
            isset($_option['selection_args']) && $selectionArgs = $_option['selection_args'];
            isset($_option['group_by']) && $groupBy = $_option['group_by'];
            isset($_option['having']) && $having = $_option['having'];
            isset($_option['order_by']) && $orderBy = $_option['order_by'];
            isset($_option['limit']) && $limit = $_option['limit'];
            isset($_option['offset']) && $offset = $_option['offset'];
            unset($_option);
        }

        $sql = $this->_buildQuerySql($tableName, $columns, $selection, $groupBy, $having, $orderBy, $limit, $offset);
        $stmt = $this->connection->prepare($sql);
        $this->bindParams($stmt, $selectionArgs);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get column value
     *
     * @param string $tableName
     * @param string $columns 'columnName1,columnName2,...' If it was array, then extract to assign ...
     * @param string $selection 'columnName1=?, columnName2=?, ...'
     * @param array $selectionArgs ['value1', 'value2', ...]
     * @param string $groupBy
     * @param string $having
     * @param string $orderBy
     * @return string
     */
    public function getColumn($tableName, $columns = NULL, $selection = NULL, $selectionArgs = NULL, $groupBy = NULL, $having = NULL, $orderBy = NULL)
    {
        $isGroupByCount = false;
        if (is_array($columns)) {
            $_option = $columns;
            $columns = NULL;
            isset($_option['columns']) && $columns = $_option['columns'];
            isset($_option['selection']) && $selection = $_option['selection'];
            isset($_option['selection_args']) && $selectionArgs = $_option['selection_args'];
            isset($_option['group_by']) && $groupBy = $_option['group_by'];
            isset($_option['having']) && $having = $_option['having'];
            isset($_option['order_by']) && $orderBy = $_option['order_by'];
            isset($_option['is_group_by_count']) && $isGroupByCount = $_option['is_group_by_count'];
            unset($_option);
        }

        $limit = $isGroupByCount ? NULL : 1;
        $sql = $this->_buildQuerySql($tableName, $columns, $selection, $groupBy, $having, $orderBy, $limit);
        $isGroupByCount && $sql = "SELECT COUNT(*) FROM ($sql) t";
        $stmt = $this->connection->prepare($sql);
        $this->bindParams($stmt, $selectionArgs);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * @param string $tableName
     * @param string $columns 'columnName1,columnName2,...' If it was array, then extract to assign ...
     * @param string $selection 'columnName1=?, columnName2=?, ...'
     * @param array $selectionArgs ['value1', 'value2', ...]
     * @param string $groupBy
     * @param string $having
     * @param string $orderBy
     * @return Ambigous <NULL, mixed>
     */
    public function get($tableName, $columns = NULL, $selection = NULL, $selectionArgs = NULL, $groupBy = NULL, $having = NULL, $orderBy = NULL)
    {
        if (is_array($columns)) {
            $_option = $columns;
            $columns = NULL;
            isset($_option['columns']) && $columns = $_option['columns'];
            isset($_option['selection']) && $selection = $_option['selection'];
            isset($_option['selection_args']) && $selectionArgs = $_option['selection_args'];
            isset($_option['group_by']) && $groupBy = $_option['group_by'];
            isset($_option['having']) && $having = $_option['having'];
            isset($_option['order_by']) && $orderBy = $_option['order_by'];
            unset($_option);
        }

        $sql = $this->_buildQuerySql($tableName, $columns, $selection, $groupBy, $having, $orderBy, 1);
        $stmt = $this->connection->prepare($sql);
        $this->bindParams($stmt, $selectionArgs);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($row) ? $row : NULL;
    }

    /**
     * Insert data
     *
     * @param string $tableName
     * @param array $values ['columnName1' => 'value1', 'columnName2' => 'value2', ...]
     * @return number
     */
    public function insert($tableName, array $values)
    {
        $sql = $this->_buildInsertSql($tableName, $values);
        $stmt = $this->connection->prepare($sql);
        $this->bindParams($stmt, $values);
        $stmt->execute();
        $lastInsertId = $this->connection->lastInsertId();
        return $lastInsertId ? $lastInsertId : $stmt->rowCount();
    }

    /**
     * Update data
     *
     * @param string $tableName
     * @param array $values ['columnName1' => 'value1', 'columnName2' => 'value2', ...]
     * @param string $whereClause 'columnName1 = ? and columnName2 = ?'
     * @param string $whereArgs ['value1', 'value2', ...]
     * @param array $literalValues ['columnName1' => 1, 'columnName2' => -1, ...]
     *
     * @return number
     */
    public function update($tableName, array $values, $whereClause = NULL, $whereArgs = NULL, array $literalValues = [])
    {
        $sql = $this->_buildUpdateSql($tableName, $values, $literalValues, $whereClause);
        $stmt = $this->connection->prepare($sql);
        $this->bindParams($stmt, $values);
        $this->bindParams($stmt, $whereArgs, count($values));
        if (!$stmt->execute()) return -1;
        return $stmt->rowCount();
    }

    /**
     * Delete
     *
     * @param string $tableName
     * @param string $whereClause 'columnName1 = ? and columnName2 = ?'
     * @param array $whereArgs [['columnName1' => 'value1', ['columnName2' => 'value2', ...]]
     * @return number
     */
    public function delete($tableName, $whereClause = NULL, $whereArgs = NULL)
    {
        $sql = $this->_buildDeleteSql($tableName, $whereClause);
        $stmt = $this->connection->prepare($sql);
        $this->bindParams($stmt, $whereArgs);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * count
     *
     * @param string $tableName
     * @param string $column 'columnName1,columnName2,...'
     * @param string $whereClause 'columnName1 = ? and columnName2 = ?'
     * @param array $whereArgs ['columnName1' => 'value1', 'columnName2' => 'value2', ...]
     * @return number
     */
    public function count($tableName, $column = NULL, $whereClause = NULL, $whereArgs = NULL)
    {
        $sql = $this->_buildCountSql($tableName, $column, $whereClause);
        $stmt = $this->connection->prepare($sql);
        $this->bindParams($stmt, $whereArgs);
        $stmt->execute();
        return intval($stmt->fetchColumn());
    }

    /**
     * If you specify ON DUPLICATE KEY UPDATE, and a row is inserted that would cause a duplicate
     * value in a UNIQUE index or PRIMARY KEY, an UPDATE of the old row is performed.
     *
     * @param string $tableName
     * @param array $values ['columnName1' => 'value1', 'columnName2' => 'value2', ...]
     * @param array $updateValues ['columnName1' => 'value1', 'columnName2' => 'value2', ...]
     * @param array $literalValues ['columnName1' => 1, 'columnName2' => -1, 'columnName3' => '*3']
     * @return number
     */
    public function duplicate($tableName, array $values, array $updateValues = [], array $literalValues = [])
    {
        $sql = $this->_buildDuplicateSql($tableName, $values, $updateValues, $literalValues);
        $stmt = $this->connection->prepare($sql);
        $this->bindParams($stmt, $values);
        $this->bindParams($stmt, $updateValues, count($values));
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Execute sql
     * @param string $sql
     * @return PDOStatement
     */
    public function execute($sql)
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Get dbname
     */
    public function getSchema()
    {
        return $this->dbname;
    }

    /**
     * Get INFORMATION_SCHEMA.*
     *
     * @return array
     */
    public function getTableSchema()
    {
        $sql = "SELECT * FROM INFORMATION_SCHEMA.%s WHERE TABLE_SCHEMA='" . $this->getSchema() . "'";

        $columns = [];
        foreach ($this->doQuery(sprintf($sql, 'COLUMNS')) as $column) {
            $columns[$column['TABLE_NAME']][$column['ORDINAL_POSITION'] - 1] = $column;
        }

        $indexes = [];
        foreach ($this->doQuery(sprintf($sql, 'STATISTICS')) as $index) {
            $indexes[$index['TABLE_NAME']][$index['INDEX_NAME']]
            [$index['SEQ_IN_INDEX'] - 1] = $index;
        }

        $tables = [];
        foreach ($this->doQuery(sprintf($sql, 'TABLES')) as $table) {
            $table['TABLE_COLUMNS'] = $columns[$table['TABLE_NAME']];
            $table['TABLE_INDEXES'] = $indexes[$table['TABLE_NAME']];
            $tables[] = $table;
        }

        return $tables;
    }

    /**
     * Get all the table name in a db
     * @return array
     */
    public function getTableNames()
    {
        $arr = [];
        foreach ($this->doQuery("SHOW TABLES") as $v) {
            $arr[] = $v['Tables_in_' . $this->getSchema()];
        }
        return $arr;
    }

    /**
     * To test whether a certain table
     *
     * @param string $tableName
     * @return boolean
     */
    public function isTableExist($tableName)
    {
        $sql = "SHOW TABLES LIKE " . $this->connection->quote($tableName);
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Get all the field name of a table
     * @param string $tableName
     * @return array
     */
    public function getFieldNames($tableName)
    {
        $sql = "SHOW COLUMNS FROM " . $tableName;
        $result = $this->execute($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (!$result) [];
        $arr = [];
        foreach ($result as $v) {
            $arr[] = $v['Field'];
        }
        return $arr;
    }

    public function dropTable($tableName)
    {
        return $this->execute("DROP TABLE $tableName");
    }

    /**
     * Assembly int SQL operator calculation
     *
     * @param int $bit The start bit Binary
     * @param bool|int $val The status value，0-false, 1-true, other
     * @param int $span Bit of digits
     * @return string SQL fragment
     */
    public static function buildBinary($bit, $val = true, $span = 1)
    {
        if ($bit <= 0 || $span <= 0 || (intval($val) > (pow(2, $span) - 1))) return false;

        $val = sprintf('%0' . $span . 'b', $val); // to binary

        --$bit;
        $res = [];
        $res[] = '&~((pow(2, ' . $span . ') - 1)<<' . $bit . ')'; //clean all bits
        for ($i = $span - 1; $i >= 0; $i--) {
            if (isset($val[$i]) && $val[$i]) {
                $res[] = '|(1<<' . $bit . ')';
            } else {
                $res[] = '&~(1<<' . $bit . ')';
            }
            ++$bit;
        }

        return implode('', $res);
    }

    /**
     * Build a SELECT statement
     *
     * @param string $tableName
     * @param string $columns 'columnName1,columnName2,...'
     * @param string $selection
     * @param string $groupBy 'columnName1'
     * @param string $having
     * @param string $orderBy
     * @param int $limit
     * @param int $offset
     * @return string
     */
    private function _buildQuerySql($tableName, $columns = NULL, $selection = NULL, $groupBy = NULL, $having = NULL, $orderBy = NULL, $limit = NULL, $offset = NULL)
    {
        $sql = 'SELECT ';
        $sql .= empty($columns) ? '* ' : $columns . ' ';
        $sql .= 'FROM ' . $tableName . ' ';
        $sql .= empty($selection) ? '' : 'WHERE ' . $selection . ' ';
        $sql .= empty($groupBy) ? '' : 'GROUP BY ' . $groupBy . ' ';
        //TODO HAVING
        $sql .= empty($having) ? '' : 'HAVING ' . $having . ' ';
        $sql .= empty($orderBy) ? '' : 'ORDER BY ' . $orderBy . ' ';
        if (!empty($limit) && $limit > 0) {
            $offset = (empty($offset) || $offset < 0) ? 0 : $offset;
            $sql .= 'LIMIT ' . $offset . ', ' . $limit;
        }
        return $sql;
    }

    /**
     * Build the INSERT statement
     *
     * @param string $tableName
     * @param array $values ['columnName1' => 'value1', 'columnName2' => 'value2', ...]
     * @return string
     */
    private function _buildInsertSql($tableName, $values)
    {
        $sql = "INSERT INTO {$tableName} (";
        foreach (array_keys($values) as $key) {
            $sql .= "`{$key}`,";
        }
        $sql = rtrim($sql, ',') . ') VALUES (';
        $sql .= implode(array_fill(0, count($values), '?'), ',');
        $sql .= ')';
        return $sql;
    }

    /**
     * Build the commas statement
     *
     * @param array $values ['columnName1' => 'value1', 'columnName2' => 'value2', ...]
     * @param array $literalValues ['columnName1' => 1, 'columnName2' => -1, 'columnName3=2*columnName4']
     * @return string
     */
    private function __buildUpdateSql(array $values, array $literalValues = [])
    {
        $fields = [];
        foreach (array_keys($values) as $columnName) {
            list($alias, $columnName) = explode('.', $columnName);
            $fields[] = $columnName ? "$alias.`$columnName`=?" : "`$alias`=?";
        }

        foreach ($literalValues as $columnName => $literal) {
            if (is_numeric($columnName)) {//literal string
                $fields[] = $literal;
                continue;
            }

            if (is_numeric($literal)) { //increase
                if ($literal >= 0) {
                    $literal = '+' . $literal;
                }
            }

            $literal = strval($literal);
            list($alias, $columnName) = explode('.', $columnName);
            $fields[] = $columnName ? "$alias.`$columnName`=$alias.`$columnName`$literal" : "`$alias`=`$alias`$literal";
        }

        return implode(',', $fields) . ' ';
    }

    /**
     * Build the UPDATE statement
     *
     * @param string $tableName
     * @param array $values ['columnName1' => 'value1', 'columnName2' => 'value2', ...]
     * @param array $literalValues ['columnName1' => 1, 'columnName2' => -1]
     * @param string $whereClause
     * @return string
     */
    private function _buildUpdateSql($tableName, array $values, array $literalValues, $whereClause)
    {
        $sql = 'UPDATE ' . $tableName . ' ';
        $sql .= 'SET ';
        $sql .= $this->__buildUpdateSql($values, $literalValues);
        $sql .= empty($whereClause) ? '' : 'WHERE ' . $whereClause;

        return $sql;
    }

    /**
     * Build ON DUPLICATE KEY UPDATE statement
     *
     * @param string $tableName
     * @param array $values ['columnName1' => 'value1', 'columnName2' => 'value2', ...]
     * @param array $updateValues ['columnName1' => 'value1', 'columnName2' => 'value2', ...]
     * @param array $literalValues ['columnName1' => 1, 'columnName2' => -1]
     * @return string
     */
    private function _buildDuplicateSql($tableName, array $values, array $updateValues = [], array $literalValues = [])
    {
        $sql = $this->_buildInsertSql($tableName, $values);
        $sql .= ' ON DUPLICATE KEY UPDATE ';
        $sql .= $this->__buildUpdateSql($updateValues, $literalValues);
        return $sql;
    }

    /**
     * Build DELETE statement
     *
     * @param string $tableName
     * @param string $whereClause
     * @return string
     */
    private function _buildDeleteSql($tableName, $whereClause)
    {
        $sql = 'DELETE FROM ' . $tableName . ' ';
        $sql .= empty($whereClause) ? '' : 'WHERE ' . $whereClause;
        return $sql;
    }

    /**
     * Build COUNT statement
     * @param string $tableName
     * @param string $column
     * @param string $whereClause
     * @return string
     */
    private function _buildCountSql($tableName, $column, $whereClause)
    {
        $sql = 'SELECT COUNT(';
        $sql .= empty($column) ? '*' : $column;
        $sql .= ') FROM ' . $tableName . ' ';
        $sql .= empty($whereClause) ? '' : 'WHERE ' . $whereClause;
        return $sql;
    }

    /**
     * Build AND Conditional statements to query
     *
     * @param array $where ['columnName1[=?]' => 'value1', 'columnName2>?' => 'value2',...]
     * @param array $inArr ['columnName3' => ['val3.1','val3.2','val3.3'],...]
     * @return array [$selection, $selectionArgs]
     * @throws Tii_Exception
     */
    public static function buildWhere(array $where, array $inArr = [])
    {
        foreach ($inArr as $field => $value) {
            if (!$value) continue;
            list($in, $values) = self::__buildInFragment($value);
            list($alias, $field) = explode('.', $field);
            $where[($field ? "$alias.`$field`" : "`$alias`") . $in] = $values;
        }

        if ($where) {
            $selection = "";
            $selectionArgs = [];

            foreach ($where as $key => $value) {
                (strpos($key, '?') === false) && $key = $key . "=?";
                is_array($value) || $value = [$value];

                $keyCount = substr_count($key, '?');
                $valueCount = count($value);

                if ($keyCount != $valueCount) {
                    throw new Tii_Exception("inconsistent with [?/value]:%s/%s", $keyCount, $valueCount);
                }

                $selection .= $selection == "" ? $key : " AND " . $key;
                foreach ($value as $v) {
                    $selectionArgs[] = $v;
                }
            }
            return [$selection, $selectionArgs];
        }
        return [NULL, NULL];
    }


    /**
     * Build IN Conditional statements to query
     *
     * @param array $dataArr
     * @return array
     */
    private static function __buildInFragment(array $dataArr)
    {
        if ($dataArr) {
            $selectionArr = array_fill(0, count($dataArr), '?');
            return [" IN (" . implode(',', $selectionArr) . ")", $dataArr];
        }
        return [NULL, NULL];
    }

    /**
     * To the PDO assignment
     *
     * @param resource $stmt
     * @param array $values ['columnName1' => 'value1', 'columnName2' => 'value2', ...]
     * @param int $startIndex
     */
    public function bindParams(&$stmt, $values, $startIndex = 0)
    {
        $values = empty($values) ? [] : array_values($values);
        for ($i = 0; $i < count($values); $i++) {
            $stmt->bindParam($startIndex + $i + 1, $values[$i]);
        }
    }

    /**
     * Return the offset of the paging
     *
     * @param int $page The page number
     * @param int $ppp per page size
     * @param int $totalnum The total number of records
     * @return number
     */
    public static function getPageOffset($page, $ppp, $totalnum)
    {
        $totalpage = ceil($totalnum / $ppp);
        $page = max(1, min($totalpage, intval($page)));
        return ($page - 1) * $ppp;
    }
}