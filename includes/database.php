<?php
// includes/database.php
class Database {
    private $host = '127.0.0.1';
    private $user = 'root';
    private $pass = '';
    private $dbname = 'web_dongho';

    private $dbh; // Database Handler
    private $stmt; // Statement
    private $error;

    public function __construct() {
        // Set DSN (Data Source Name)
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" // Đảm bảo UTF8
        );

        // Create a new PDO instance
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            echo "Lỗi kết nối CSDL: " . $this->error;
            // Log the error to a file in production
            error_log("Database connection error: " . $this->error);
            die(); // Dừng ứng dụng nếu không kết nối được CSDL
        }
    }

    // Prepare statement with query
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }

    // Bind values
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    // Execute the prepared statement
    public function execute() {
        return $this->stmt->execute();
    }

    // Get result set as array of objects
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Get single record as object
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    // Get the ID of the last inserted row
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

    // Thêm phương thức này vào đây
    public function singleColumn() {
        $this->execute(); // Cần execute trước khi fetchColumn
        return $this->stmt->fetchColumn();
    }

    // Bắt đầu giao dịch
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    // Kết thúc giao dịch (commit)
    public function commit() {
        return $this->dbh->commit();
    }

    // Hoàn tác giao dịch (rollback)
    public function rollBack() {
        return $this->dbh->rollBack();
    }
}