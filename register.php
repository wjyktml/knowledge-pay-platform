<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// 如果已登录，重定向到主页
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $agree_terms = isset($_POST['agree_terms']);
    
    // 验证输入
    if (empty($username) || empty($email) || empty($password)) {
        $error_message = '请填写所有必填字段';
    } elseif (!validateEmail($email)) {
        $error_message = '请输入有效的邮箱地址';
    } elseif ($phone && !validatePhone($phone)) {
        $error_message = '请输入有效的手机号码';
    } elseif (strlen($password) < 6) {
        $error_message = '密码长度至少6位';
    } elseif ($password !== $confirm_password) {
        $error_message = '两次输入的密码不一致';
    } elseif (!$agree_terms) {
        $error_message = '请同意服务条款和隐私政策';
    } else {
        // 注册用户
        $result = registerUser($username, $email, $password, $phone);
        if ($result['success']) {
            $success_message = '注册成功！请登录您的账户。';
            // 可以在这里发送欢迎邮件
        } else {
            $error_message = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册 - 知识付费平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }

        .register-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .register-right {
            padding: 60px 40px;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-register {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: bold;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }

        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .feature-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .register-left {
                padding: 40px 20px;
            }
            
            .register-right {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="register-container">
                    <div class="row g-0">
                        <!-- 左侧介绍 -->
                        <div class="col-lg-5 register-left">
                            <div class="mb-4">
                                <i class="fas fa-graduation-cap feature-icon"></i>
                                <h2>加入知识付费平台</h2>
                                <p class="mb-4">开启您的学习之旅，获取优质知识内容</p>
                            </div>
                            
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <i class="fas fa-book-open mb-2" style="font-size: 1.5rem;"></i>
                                    <div>海量内容</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <i class="fas fa-users mb-2" style="font-size: 1.5rem;"></i>
                                    <div>专业讲师</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <i class="fas fa-mobile-alt mb-2" style="font-size: 1.5rem;"></i>
                                    <div>移动学习</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <i class="fas fa-shield-alt mb-2" style="font-size: 1.5rem;"></i>
                                    <div>安全支付</div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <p class="small">已有账户？</p>
                                <a href="login.php" class="btn btn-outline-light">立即登录</a>
                            </div>
                        </div>
                        
                        <!-- 右侧注册表单 -->
                        <div class="col-lg-7 register-right">
                            <div class="text-center mb-4">
                                <h3>创建新账户</h3>
                                <p class="text-muted">填写以下信息完成注册</p>
                            </div>
                            
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success_message): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                                    <div class="mt-2">
                                        <a href="login.php" class="btn btn-success btn-sm">立即登录</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">用户名 *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   value="<?php echo isset($_POST['username']) ? safe_output($_POST['username']) : ''; ?>" 
                                                   required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">邮箱地址 *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo isset($_POST['email']) ? safe_output($_POST['email']) : ''; ?>" 
                                                   required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">密码 *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   minlength="6" required>
                                        </div>
                                        <div class="form-text">密码长度至少6位</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">确认密码 *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" minlength="6" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">手机号码</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo isset($_POST['phone']) ? safe_output($_POST['phone']) : ''; ?>"
                                               placeholder="请输入11位手机号码">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="agree_terms" 
                                               name="agree_terms" required>
                                        <label class="form-check-label" for="agree_terms">
                                            我已阅读并同意 <a href="terms.php" target="_blank">服务条款</a> 和 
                                            <a href="privacy.php" target="_blank">隐私政策</a>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-register btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>立即注册
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-4">
                                <p class="text-muted small">
                                    注册即表示您同意我们的服务条款和隐私政策
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 密码强度检查
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            updatePasswordStrength(strength);
        });

        // 确认密码验证
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('两次输入的密码不一致');
            } else {
                this.setCustomValidity('');
            }
        });

        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            return strength;
        }

        function updatePasswordStrength(strength) {
            const strengthText = ['很弱', '弱', '一般', '强', '很强'];
            const strengthColors = ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#28a745'];
            
            // 这里可以添加密码强度显示UI
            console.log('密码强度:', strengthText[strength - 1] || '很弱');
        }

        // 表单验证
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('两次输入的密码不一致');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('密码长度至少6位');
                return false;
            }
        });
    </script>
</body>
</html>
