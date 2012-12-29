<?php

class DatabaseConnection {

    var $cl;
    var $prefix;

    function DatabaseConnection() {
        global $dbHost, $dbUser, $dbPassword, $dbName, $dbPrefix;

        $this->cl = mysql_connect($dbHost, $dbUser, $dbPassword);
        if ($this->cl) {
            if (!mysql_select_db($dbName, $this->cl)) {
                $this->logError();
                $this->closeConnection();
                die("MySQL Error: Can't select DB, see logs for more information.");
            }
            $this->prefix = $dbPrefix;
        }
        else {
            die("MySQL Error: Can't connect to DB, see logs for more information.");
        }
    }

    function closeConnection() {
        if ($this->cl) return mysql_close($this->cl);
        else return false;
    }

    function query($query, $logError = true) {
        $result = mysql_query($query, $this->cl);
        if ($result === false && $logError) {
            $this->logError($query);
        }
        return $result;
    }

    function numRows($resource) {
        return mysql_num_rows($resource);
    }

    function fetchRow($resource) {
        return mysql_fetch_row($resource);
    }

    function fetchArray($resource, $result_type = MYSQL_BOTH) {
        return mysql_fetch_array($resource, $result_type);
    }

    function insertID() {
        return mysql_insert_id($this->cl);
    }

    function escapeString($text) {
        return mysql_real_escape_string($text, $this->cl);
    }

    function unescapeString($text) {
        return (get_magic_quotes_gpc()?stripslashes($text):$text);
    }

    function logError($query = null) {
        $backtrace = debug_backtrace();

        $message = 'MySQL Error '.mysql_errno($this->cl).': '.mysql_error($this->cl);
        if (!is_null($query))
            $message .= ' in '.$backtrace[1]['file'].' on line '.$backtrace[1]['line'].'; SQL query: '.$query;
        error_log($message);
    }

}
