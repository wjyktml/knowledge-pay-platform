<?php
// 知识付费平台安装脚本
error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error_message = '';
$success_message = '';

// 处理安装步骤
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    switch ($step) {
        case 1:
            // 检查环境
            $requirements = [
                'PHP版本 >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                'PDO扩展' => extension_loaded('pdo'),
                'PDO MySQL扩展' => extension_loaded('pdo_mysql'),
                'JSON扩展' => extension_loaded('json'),
                'CURL扩展' => extension_loaded('curl'),
                'GD扩展' => extension_loaded('gd'),
                'MBString扩展' => extension_loaded('mbstring')
            ];
            
            $all_ok = true;
            foreach ($requirements as $req => $status) {
                if (!$status) {
                    $all_ok = false;
                    break;
                }
            }
            
            if ($all_ok) {
                header('Location: install.php?step=2');
                exit;
            } else {
                $error_message = '环境检查失败，请确保满足所有要求';
            }
            break;
            
        case 2:
            // 数据库配置
            $db_host = $_POST['db_host'];
            $db_name = $_POST['db_name'];
            $db_user = $_POST['db_user'];
            $db_pass = $_POST['db_pass'];
            
            try {
                $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // 创建数据库
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$db_name`");
                
                // 读取并执行SQL文件
                $sql = file_get_contents('database/schema.sql');
                $statements = explode(';', $sql);
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }
                
                // 保存配置
                $config_content = "<?php
// 数据库配置文件
class Database {
    private \$host = '$db_host';
    private \$db_name = '$db_name';
    private \$username = '$db_user';
    private \$password = '$db_pass';
    private \$charset = 'utf8mb4';
    public \$conn;

    public function getConnection() {
        \$this->conn = null;
        
        try {
            \$dsn = \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name . \";charset=\" . \$this->charset;
            \$this->conn = new PDO(\$dsn, \$this->username, \$this->password);
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException \$exception) {
            echo \"连接失败: \" . \$exception->getMessage();
        }
        
        return \$this->conn;
    }
}

// 创建全局数据库连接
\$database = new Database();
\$db = \$database->getConnection();

// 数据库连接测试
if (!\$db) {
    die(\"数据库连接失败\");
}
?>";
                
                file_put_contents('config/database.php', $config_content);
                
                header('Location: install.php?step=3');
                exit;
                
            } catch (Exception $e) {
                $error_message = '数据库配置失败: ' . $e->getMessage();
            }
            break;
            
        case 3:
            // 管理员账户设置
            $admin_username = $_POST['admin_username'];
            $admin_email = $_POST['admin_email'];
            $admin_password = $_POST['admin_password'];
            
            if (empty($admin_username) || empty($admin_email) || empty($admin_password)) {
                $error_message = '请填写所有必填字段';
            } else {
                try {
                    require_once 'config/database.php';
                    
                    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                    
                    $sql = "UPDATE admins SET username = ?, email = ?, password = ? WHERE id = 1";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$admin_username, $admin_email, $hashed_password]);
                    
                    // 创建安装完成标记
                    file_put_contents('install.lock', date('Y-m-d H:i:s'));
                    
                    header('Location: install.php?step=4');
                    exit;
                    
                } catch (Exception $e) {
                    $error_message = '管理员账户设置失败: ' . $e->getMessage();
                }
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>知识付费平台 - 安装向导</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .install-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }

        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .install-body {
            padding: 40px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }

        .step.active {
            background: #667eea;
            color: white;
        }

        .step.completed {
            background: #28a745;
            color: white;
        }

        .step.pending {
            background: #e9ecef;
            color: #6c757d;
        }

        .requirement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .requirement-item:last-child {
            border-bottom: none;
        }

        .status-ok {
            color: #28a745;
        }

        .status-error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="install-container">
                    <div class="install-header">
                        <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                        <h2>知识付费平台</h2>
                        <p class="mb-0">安装向导</p>
                    </div>
                    
                    <div class="install-body">
                        <!-- 步骤指示器 -->
                        <div class="step-indicator">
                            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'pending'; ?>">1</div>
                            <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'pending'; ?>">2</div>
                            <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : 'pending'; ?>">3</div>
                            <div class="step <?php echo $step >= 4 ? 'active' : 'pending'; ?>">4</div>
                        </div>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($step == 1): ?>
                            <!-- 步骤1: 环境检查 -->
                            <h4 class="mb-4">环境检查</h4>
                            <div class="mb-4">
                                <?php
                                $requirements = [
                                    'PHP版本 >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                                    'PDO扩展' => extension_loaded('pdo'),
                                    'PDO MySQL扩展' => extension_loaded('pdo_mysql'),
                                    'JSON扩展' => extension_loaded('json'),
                                    'CURL扩展' => extension_loaded('curl'),
                                    'GD扩展' => extension_loaded('gd'),
                                    'MBString扩展' => extension_loaded('mbstring')
                                ];
                                
                                foreach ($requirements as $req => $status):
                                ?>
                                    <div class="requirement-item">
                                        <span><?php echo $req; ?></span>
                                        <i class="fas fa-<?php echo $status ? 'check' : 'times'; ?> <?php echo $status ? 'status-ok' : 'status-error'; ?>"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <form method="POST">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-arrow-right me-2"></i>下一步
                                    </button>
                                </div>
                            </form>
                            
                        <?php elseif ($step == 2): ?>
                            <!-- 步骤2: 数据库配置 -->
                            <h4 class="mb-4">数据库配置</h4>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="db_host" class="form-label">数据库主机</label>
                                    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_name" class="form-label">数据库名称</label>
                                    <input type="text" class="form-control" id="db_name" name="db_name" value="knowledge_pay" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_user" class="form-label">数据库用户名</label>
                                    <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="db_pass" class="form-label">数据库密码</label>
                                    <input type="password" class="form-control" id="db_pass" name="db_pass">
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-database me-2"></i>配置数据库
                                    </button>
                                </div>
                            </form>
                            
                        <?php elseif ($step == 3): ?>
                            <!-- 步骤3: 管理员账户 -->
                            <h4 class="mb-4">管理员账户设置</h4>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="admin_username" class="form-label">管理员用户名</label>
                                    <input type="text" class="form-control" id="admin_username" name="admin_username" value="admin" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="admin_email" class="form-label">管理员邮箱</label>
                                    <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="admin_password" class="form-label">管理员密码</label>
                                    <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-user-shield me-2"></i>创建管理员账户
                                    </button>
                                </div>
                            </form>
                            
                        <?php elseif ($step == 4): ?>
                            <!-- 步骤4: 安装完成 -->
                            <div class="text-center">
                                <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                                <h4 class="mb-3">安装完成！</h4>
                                <p class="text-muted mb-4">知识付费平台已成功安装，您现在可以开始使用了。</p>
                                
                                <div class="d-grid gap-2">
                                    <a href="index.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-home me-2"></i>访问前台
                                    </a>
                                    <a href="admin/index.php" class="btn btn-outline-primary">
                                        <i class="fas fa-cog me-2"></i>管理后台
                                    </a>
                                </div>
                                
                                <div class="mt-4">
                                    <small class="text-muted">
                                        建议删除 install.php 文件以确保安全
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
