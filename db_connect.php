
<?php

class Database {
    private $host = "localhost";
    private $db_name = "ca";
    private $username = "ca_user";
    private $password = "strongpassword123";
    private $dbname = "ca";
    private $conn;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        // Create the connection object using MySQLi OOP
        $this->conn = new mysqli(
            $this->host, 
            $this->username, 
            $this->password, 
            $this->db_name
        );

        // Check for errors
        if ($this->conn->connect_error) {
            die("❌ Connection failed: " . $this->conn->connect_error);
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    // Optional: Close connection
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

?>