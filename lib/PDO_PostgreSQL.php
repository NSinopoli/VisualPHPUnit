<?php

/* VisualPHPUnit
 *
 * Copyright (c) 2011, Nick Sinopoli <nsinopoli@gmail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Nick Sinopoli nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

class PDO_PostgreSQL {

   /**
    *  The db handle. 
    *
    *  @var object
    *  @access protected
    */
    protected $_dbh;

   /**
    *  Number of rows affected by MySQL query.
    *
    *  @var int
    *  @access protected
    */
    protected $_affected_rows = 0;

   /**
    *  The result set associated with a prepared statement.
    *
    *  @var PDOStatement
    *  @access protected
    */
    protected $_statement;

   /**
    *  Loads the configuration settings for a MySQL connection and connects
    *  to the database.
    *
    *  @param array $config                     The configuration settings, which can take four options:
    *                                           `database` - The name of the database.
    *                                           `host`     - The database host.
    *                                           `username` - The database username.
    *                                           `password` - The database password.
    *                                           (By default, instances are destroyed at the end of the request.)
    *  @access public
    *  @return void
    */
    public function __construct(array $config = array()) {
        $defaults = array(
            'database' => DATABASE_NAME,
            'host'     => DATABASE_HOST, 
            'username' => DATABASE_USER,
            'password' => DATABASE_PASS
        );
        $config += $defaults;
        $this->connect($config);
    }

   /**
    *  Connects and selects database.
    *
    *  @param array $options       Contains the connection information.  
    *                              Takes the following options:
    *  `database` - The name of the database.
    *  `host`     - The database host.
    *  `username` - The database username.
    *  `password` - The database password.
    *  @access public
    *  @return bool
    */
    public function connect($options) {
        // $dsn = 'mysql:host=' . $options['host'] . ';dbname=' . $options['database']; 
    	$dsn = 'pgsql:host=' . $options['host'] . ';dbname=' . $options['database']; 
        try {
            $this->_dbh = new \PDO($dsn, $options['username'], $options['password']);
            $this->_dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return true;
        } catch ( PDOException $e ) {
            echo 'An error occurred: ' . $e->getMessage();
            echo '<br /><br />Traceback:<pre>';
            echo var_dump($e->getTrace());
            die('</pre>');
        }
    }

   /**
    *  Returns the number of rows affected by the last DELETE, INSERT, or UPDATE query.
    *
    *  @access public
    *  @return int
    */
    public function affected_rows() {
    	return $this->_affected_rows;
    }

   /**
    *  Closes the connection.
    *
    *  @access public
    *  @return bool
    */
    public function close() {
        $this->_dbh = null;
        return true;
    }
    
   /**
    *  Deletes a record from the database.
    *
    *  @see PDO_MySQL->_format_where()
    *  @param string $table              The table containing the record to be deleted.
    *  @param string|array $where        The WHERE clause to be included in the DELETE query.
    *  @access public
    *  @return bool       
    */
    public function delete($table, $where = null) {
    	$sql = 'DELETE FROM ' . $table . '';
        $sql .= $this->_format_where($where);

        if ( is_string($where) ) {
            return $this->query($sql);
        } elseif ( is_array($where) ) {
            return $this->query($sql, $where);
        }
    }
    
   /**
    *  Fetches the next row from the result set in memory (i.e., the one
    *  that was created after running query()).
    *
    *  @param string $fetch_style        Controls how the rows will be returned.
    *  @access public
    *  @return mixed
    */
    public function fetch($fetch_style = null) {
        $this->_set_fetch_mode($fetch_style);
        $row = $this->_statement->fetch();
        $this->_statement->closeCursor();
        return $row; 
    }

   /**
    *  Returns an array containing all of the result set rows.
    *
    *  @param string $fetch_style        Controls how the rows will be returned.
    *  @access public
    *  @return mixed
    */
    public function fetch_all($fetch_style = null) {
        $this->_set_fetch_mode($fetch_style);
        $rows = $this->_statement->fetchAll();
        $this->_statement->closeCursor();
        return $rows; 
    }

   /**
    *  Returns a single column from the next row of a result set or false if there are no more rows.
    *
    *  @param int $column_number         Zero-index number of the column to retrieve from the row.
    *  @access public
    *  @return mixed
    */
    public function fetch_column($column_number = 0) {
        $column = $this->_statement->fetchColumn($column_number);
        $this->_statement->closeCursor();
        return $column; 
    }

   /**
    *  Performs a `SELECT FROM` query.
    *
    *  @see PDO_MySQL->query()
    *  @see PDO_MySQL->fetch() 
    *  @see PDO_MySQL->_format_where()
    *  @param string|array $fields       The fields to be retrieved.
    *  @param string $table              The table to SELECT from.
    *  @param string|array $where        The WHERE clause of the SQL query.
    *  @param string $additional         Any additional SQL to be added at the end of the query.
    *  @access public
    *  @return void
    */
    public function find($fields, $table, $where = null, $additional = null) {
        $sql = 'SELECT ';
        if ( is_array($fields) ) {
        	$sql .= '' . implode(', ', $fields) . '';
        } else {
            $sql .= $fields;
        }

        $sql .= ' FROM ' . $table . '';
        $sql .= $this->_format_where($where);
        if ( !is_null($additional) ) {
            $sql .= ' ' . $additional;
        }
        $this->query($sql, $where); 
    }

   /**
    *  Parses a WHERE clause, which can be of any of the following formats:
    *  $where = 'id = 3';
    *  (produces ` WHERE id = 3`)
    *  $where = array(
    *      'id'       => 3,
    *      'username' => 'test'
    *  );
    *  (produces ` WHERE id = 3 and username = 'test'`)
    *  $where = array(
    *      'id' => array(
    *          'gte' => 20,
    *          'lt' => 30
    *      ),
    *      'username' => 'test'
    *  );
    *  (produces ` WHERE id >= 20 and id < 30 and username = 'test'`)
    *
    *  @param string|array $where        The clause to be parsed.
    *  @access protected
    *  @return string
    */
    protected function _format_where(&$where = null) {
        $sql = '';

        if ( is_null($where) ) {
            return $sql;
        }

        $sql = ' WHERE ';
        if ( is_string($where) ) {
            $sql .= $where;
        } elseif ( is_array($where) ) {
            foreach ( $where as $name => $val ) {
                if ( is_string($val) ) {
                	$sql .= '' . $name . '=:' . $name . ' and ';
                }
                elseif ( is_array($val) ) {
                    foreach ( $val as $sign => $constraint ) {
                        do {
                            $new_name = $name .  '__' . rand();
                        } while ( isset($where[$new_name]) );
                        $sql .=  '' . $name . ' ';
                        switch ( $sign ) {
                            case 'gt':
                                $sql .= '>';
                                break;
                            case 'gte':
                                $sql .= '>=';
                                break;
                            case 'lt':
                                $sql .= '<';
                                break;
                            case 'lte':
                                $sql .= '<=';
                                break;
                            case 'e':
                            default:
                                $sql .= '=';
                                break;
                        }
                        $sql .= ':' . $new_name . ' and ';
                        $where[$new_name] = $constraint;
                        unset($where[$name]);
                    }
                }
            }
            $sql = substr($sql, 0, strlen($sql) - strlen(' and '));
        }

        return $sql;
    }

   /**
    *  Inserts a record into the database. 
    *
    *  @param string $table        The table containing the record to be inserted.
    *  @param array $data          An array containing the data to be inserted. Format
    *                              should be as follows:
    *                              array('column_name' => 'column_value');
    *  @access public
    *  @return bool
    */
    public function insert($table, $data) {
    	$sql = 'INSERT INTO ' . $table . ' ';
        
        $key_names = array_keys($data);
        $fields = '' . implode(', ', $key_names) . '';
        $values = ':' . implode(', :', $key_names);
            
    	$sql .= '(' . $fields . ') VALUES (' . $values . ')';

    	$statement = $this->_dbh->prepare($sql);

        try {
            $statement->execute($data);
    	} catch ( \PDOException $e ) {
            echo 'An error occurred: ' . $e->getMessage();
            echo '<br /><br />Traceback:<pre>';
            echo var_dump($e->getTrace());
            die('</pre>');
        }

    	$this->_affected_rows = $statement->rowCount();
        return true;
    }

   /**
    *  Returns the ID of the last inserted row or sequence value.
    *
    *  @access public
    *  @return int
    */
    public function insert_id() {
        return $this->_dbh->lastInsertId();
    }

   /**
    *  Returns the number of rows affected by the last SELECT query.
    *
    *  @access public
    *  @return int       
    */
    public function num_rows() {
        $this->query('SELECT FOUND_ROWS()');
        $rows = $this->fetch_column();
        return $rows;
    }

   /**
    *  Executes SQL query.
    *
    *  @param string $sql           The SQL query to be executed.
    *  @param array $parameters     An array containing the parameters to be bound.
    *  @access public
    *  @return bool 
    */
    public function query($sql, $parameters = null) {
        $statement = $this->_dbh->prepare($sql);

        if ( is_array($parameters) ) {
            foreach ( $parameters as $field => &$value ) {
                $statement->bindParam(':' . $field, $value);
            }
        }
        try {
            $statement->execute();
    	} catch ( \PDOException $e ) {
            echo 'An error occurred: ' . $e->getMessage();
            echo '<br /><br />Traceback:<pre>';
            echo var_dump($e->getTrace());
            die('</pre>');
        }
    
    	$this->_affected_rows = $statement->rowCount();
        $this->_statement = $statement;
    	return true;
    }

   /**
    *  Executes SQL query and returns the first row of the results.
    *
    *  @param string $sql                The SQL query to be executed.
    *  @param array $parameters          An array containing the parameters to be bound.
    *  @access public
    *  @return mixed       
    */
    public function query_first($sql, $parameters = null) {
        $this->query($sql . ' LIMIT 1', $parameters);
    }

   /**
    *  Sets the fetch mode.
    *
    *  @param string $fetch_style        Controls how the rows will be returned.
    *  @access protected
    *  @return int 
    */
    protected function _set_fetch_mode($fetch_style) {
        switch ( $fetch_style ) {
            case 'assoc':
                $this->_statement->setFetchMode(\PDO::FETCH_ASSOC);
                break;
            case 'both':
                $this->_statement->setFetchMode(\PDO::FETCH_BOTH);
                break;
            case 'lazy':
                $this->_statement->setFetchMode(\PDO::FETCH_LAZY);
                break;
            case 'num':
                $this->_statement->setFetchMode(\PDO::FETCH_NUM);
                break;
            case 'obj':
                $this->_statement->setFetchMode(\PDO::FETCH_OBJ);
                break;
            default:
                $this->_statement->setFetchMode(\PDO::FETCH_ASSOC);
                break;
        }
    }

   /**
    *  Updates a record in the database.
    *
    *  @param string $table        The table containing the record to be inserted.
    *  @param array $data          An array containing the data to be inserted. Format
    *                              should be as follows:
    *                              array('column_name' => 'column_value');
    *  @param array $where         The WHERE clause of the SQL query. Format should
    *                              be as follows:
    *                              array('column_name' => 'column_value');
    *  @access public
    *  @return bool 
    */
    public function update($table, $data, $where = null) {
    	$sql = 'UPDATE ' . $table . ' SET ';

        $key_names = array_keys($data);
    	foreach ( $key_names as $name ) {
            $sql .= '' . $name . '=:' . $name . ', ';
    	}

        $sql = rtrim($sql, ', ');

        if ( !is_null($where) ) {
    	    $sql .= ' WHERE ';
            foreach ( $where as $name => $val ) {
            	$sql .= '' . $name . '=:' . $name . '_where, ';
                $data[$name . '_where'] = $val;
            }
        }
    	$statement = $this->_dbh->prepare($sql);

        try {
            $statement->execute($data);
    	} catch ( \PDOException $e ) {
            echo 'An error occurred: ' . $e->getMessage();
            echo '<br /><br />Traceback:<pre>';
            echo var_dump($e->getTrace());
            die('</pre>');
        }
    
    	$this->_affected_rows = $statement->rowCount();
    	return true;
    }

   /**
    *  Inserts or updates (if exists) a record in the database.
    *
    *  @param string $table        The table containing the record to be inserted.
    *  @param array $data          An array containing the data to be inserted. Format
    *                              should be as follows:
    *                              array('column_name' => 'column_value');
    *  @access public
    *  @return bool 
    */
    public function upsert($table, $data) {
        $sql = 'INSERT INTO `' . $table . '` ';

        $key_names = array_keys($data);
    	$fields = '`' . implode('`, `', $key_names) . '`';
        $values = ':' . implode(', :', $key_names);

    
        $sql .= '(' . $fields . ') VALUES (' . $values . ') ON DUPLICATE KEY UPDATE ';

    	foreach ( $key_names as $name ) {
            $sql .= '`' . $name . '`=:' . $name . ', ';
    	}

        $sql = rtrim($sql, ', ');
    	$statement = $this->_dbh->prepare($sql);

        try {
            $statement->execute($data);
    	} catch ( \PDOException $e ) {
            echo 'An error occurred: ' . $e->getMessage();
            echo '<br /><br />Traceback:<pre>';
            echo var_dump($e->getTrace());
            die('</pre>');
        }

    	$this->_affected_rows = $statement->rowCount();
        return true; 
    }
}

?>
