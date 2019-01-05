<?php
namespace app;

class Db {

    private $host = "localhost";
    private $username = "root";
    private $password = "admin@123";
    private $database = "excel_import";
    public $connection;

    // get the database connection
    public function connect() {

        $this->connection = null;

        try {
            $this->connection = new \PDO("mysql:host=" . $this->host . ";dbname=" . $this->database, $this->username, $this->password);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
//            $this->connection->exec("set names utf8");
        } catch (PDOException $exception) {
            echo "Error: " . $exception->getMessage();
        }

        return $this->connection;
    }

}
