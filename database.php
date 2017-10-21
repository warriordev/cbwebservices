<?php

/*
  Generic functions for DB operations
 */

class db {

    public static function open() { // opens the db connection. Specify different databases for local and live server and it will automatically select the correct one
        $servers = array('localhost', '127.0.0.1', 'aromeremix', 'warrior');
        if (in_array($_SERVER['HTTP_HOST'], $servers)) { //for localhost
            $dbuser = 'root';
            $dbpwd = '';
            $dbname = 'cbwebservices';
            $dbserver = 'localhost';
        } else {
            $dbuser = 'mrageco_user27';
            $dbpwd = '7kgKCEeabZC]';
            $dbname = 'mrageco_db27';
            $dbserver = 'localhost';
        }
        mysql_connect($dbserver, $dbuser, $dbpwd) or die(mysql_error());

        mysql_select_db($dbname) or die(mysql_error());
    }

    public static function close() {
        //mysql_close();
    }

    public static function getRecords($query, $cursor = NULL, $pageSize = NULL) {  // Gets multiple records and returns associative array
        db::open();
        if (!is_null($cursor) && !is_null($pageSize)) {
            $query .= " LIMIT " . $cursor . ", " . $pageSize;
        }

        $result = mysql_query($query) or die(mysql_error());
        if (!mysql_num_rows($result) == 0) {
            $i = 0;
            while ($row = mysql_fetch_array($result)) {
                $recordset[$i] = $row;
                $i++;
            }
        } else {
            $recordset = false;
        }

        db::close();
        return ($recordset);
    }

    public static function getRecord($query) { // Gets single record and returns single associative array
        db::open();
        $result = mysql_query($query) or die(mysql_error());
        if (!mysql_num_rows($result) == 0) {
            $recordset = mysql_fetch_assoc($result);
        } else {
            $recordset = false;
        }
        db::close();
        return ($recordset);
    }
    
    public static function executeQry($query){
        db::open();
        return mysql_query($query);
    }

    public static function getCell($query) { // Returns single value
        db::open();
        $result = mysql_query($query) or die(mysql_error());
        if (!mysql_num_rows($result) == 0) {
            $cell = mysql_result($result, 0);
        } else {
            $cell = false;
        }
        return $cell;
    }

    public static function updateRecord($query) {  // Gets a formatted query and returns the true/false
        db::open();
        $result = mysql_query($query) or die(mysql_error());
        db::close();
        return ($result);
    }

    public static function insertRecord($query) { // Gets a formatted query to insert a row and returns the ID of last added record
        db::open();
        mysql_query($query) or die(mysql_error());
        $result = mysql_insert_id();
        db::close();
        return ($result);
    }

    public static function deleteRecord($table, $pk, $id) { // Gets the Id of row to be deleted and table name
        db::open();
        $query = "delete from " . $table . " where " . $pk . "=" . $id;
        $result = mysql_query($query) or die(mysql_error());
        db::close();
        return ($result);
    }

    public static function prepUpdateQuery(&$obj, $table, $pk, $id) { // Gets the associative array of field-name=>value, table name and id and returns the formatted update query
        $query = "update " . $table . " set ";
        $total = count($obj);
        $current = 1;
        foreach ($obj as $key => $value) {
            $query = $query . " `" . $key . "`='" . $value . "' ";
            if ($current < $total) {
                $query = $query . ", ";
            }
            $current++;
        }
        $query = $query . " where " . $pk . "=" . $id;
        return $query;
    }

    public static function prepUpdateQuerystring(&$obj, $table, $pk, $id) { // Gets the associative array of field-name=>value, table name and id and returns the formatted update query
        $query = "update " . $table . " set ";
        $total = count($obj);
        $current = 1;
        foreach ($obj as $key => $value) {
            $query = $query . " `" . $key . "`='" . $value . "' ";
            if ($current < $total) {
                $query = $query . ", ";
            }
            $current++;
        }
        $query = $query . " where " . $pk . "='" . $id . "'";
    }

    public static function prepInsertQuery(&$obj, $table) { // Gets the associative array of field-name=>value and table name and returns the formatted insert query
        $query = "Insert into " . $table;
        $total = count($obj);
        $current = 1;
        foreach ($obj as $key => $value) {
            $fields = $fields . " `" . $key . "`";
            $values = $values . " '" . $value . "'";
            if ($current < $total) {
                $fields = $fields . ", ";
                $values = $values . ", ";
            }
            $current++;
        }
        $query = $query . " (" . $fields . ") VALUES (" . $values . ")";
        return $query;
    }

    public static function markAsRead($table, $pk, $id, $field) {
        db::open();
        $query = "update " . $table . " set " . $field . "=1 where " . $pk . "=" . $id;
        $result = mysql_query($query) or die(mysql_error());
        db::close();
        return ($result);
    }

}

?>