<?php

/**
 * BitPeer MySQL Database Abstraction
 *
 * Handles calls to a MySQL Database using the PDO functions
 *
 * @category   ArcticDesk
 * @package    Database
 * @copyright  Copyright (c) 2014 BitPeer
 * @version    $Id: database.mysql.php 3514 2014-06-10 09:25:48Z yatesj $
 * @since      File available since 1.0
 */
$className = 'databaseMysql';

class databaseMysql
{

    var $database;
    var $statement;

    /**
     * Used by the engine to start a connection to the database server
     */
    function __construct()
    {


//        $hostname = '192.168.200.123';
        $hostname = 'mysql';
        $database = 'trading';
        $username = 'root';
        $password = 'P455w0rd';

        try {

            $this->database = new PDO('mysql:host=' . $hostname . ';dbname=' . $database, $username, $password);
            $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {

            include 'includes/head.php';
            include 'includes/header.php';
            $error = "BitPeer is over capacity. Please try again later.";
            include 'views/error.php';
            include 'includes/footer.php';
            exit;

            echo 'ERROR: ' . $e->getMessage();
        }
    }

    /**
     * Executes a SQL statement
     *
     * @param  $type        int     Type of SQL statement, 0 for SELECT, 1 for INSERT, 2 for UPDATE, 3 for DELETE
     * @param  $query       string  The SQL query
     * @param  $inputArray  array   An array of input data to be prepared before used in the query
     * @return              mixed   If SELECT - returns an array of the results
     *                              If INSERT - returns the newly inserted row ID, 0 if the insert failed
     *                              Otherwise - returns true if the statement executed successfully, false otherwise
     */
    function query($type = 0, $query, $inputArray = array(), $limitArray = array())
    {

        try {

            // Prepare the statement
            $this->statement = $this->database->prepare($query);

            if (!empty($inputArray)) {
                foreach ($inputArray as $key => $value) {
                    $this->statement->bindValue($key, $value);
                }
            }

            // Bind the limit values if there are any
            if (!empty($limitArray)) {
                foreach ($limitArray as $key => $value) {
                    $this->statement->bindValue($key, (int) $value, PDO::PARAM_INT);
                }
            }

            // Execute the statement
            $result = $this->statement->execute();

            if ($type == 0) {

                // SELECT statement
                // Return an array of the results
                return $this->statement->fetchAll(PDO::FETCH_ASSOC);
            } else if ($type == 1) {

                // INSERT statement
                // Return the ID of the row inserted if successful, 0 otherwise
                return $this->database->lastInsertId();
            } else {

                // UPDATE OR DELETE statement
                // return if successful or not
                return $result;
            }
        } catch (PDOException $e) {
            error_log('SQL-ERROR: ' . $e->getMessage(), 0);
        }
    }

    function rowCount()
    {
        try {
            return $this->statement->rowCount();
        } catch (PDOException $e) {
            error_log('SQL-ERROR: ' . $e->getMessage(), 0);
        }

    }

    function beginTransaction()
    {
        try {
            return $this->database->beginTransaction();
        } catch (PDOException $e) {
            error_log('SQL-ERROR: ' . $e->getMessage(), 0);
        }
    }

    function commit()
    {
        try {
            return $this->database->commit();
        } catch (PDOException $e) {
            // Something failed? Rollback instead
            $this->database->rollBack();
            error_log('SQL-ERROR: ' . $e->getMessage(), 0);
        }
    }

    function rollBack()
    {
        try {
            return $this->database->rollBack();
        } catch (PDOException $e) {
            error_log('SQL-ERROR: ' . $e->getMessage(), 0);
        }
    }

}
