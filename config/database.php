<?php
// 数据库配置文件
class Database {
    private $host = 'localhost';
    private $db_name = 'knowledge_pay';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "连接失败: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// 创建全局数据库连接
$database = new Database();
$db = $database->getConnection();

// 数据库连接测试
if (!$db) {
    die("数据库连接失败");
}
?>
