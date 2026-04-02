<?php
class Connection {
    protected $conn;

    public function __construct()
    {
        $host = "localhost";
        $dbname = "noreco1_mater_inventory";
        $username = "root";
        $password = "";

        try {
            $this->conn = new PDO(
                "mysql:host=$host;dbname=$dbname",
                $username,
                $password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    // ✅ This method returns the PDO connection
    public function connect() {
        return $this->conn;
    }
}
?>
