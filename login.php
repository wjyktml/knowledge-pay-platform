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
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        $error_message = '请输入用户名和密码';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            // 设置会话
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            $_SESSION['email'] = $result['user']['email'];
            
            // 记住我功能
            if ($remember_me) {
                $token = generateRandomString(32);
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30天
                // 这里可以将token存储到数据库中
            }
            
            // 重定向到原页面或主页
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
            header('Location: ' . $redirect);
            exit;
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
    <title>用户登录 - 知识付费平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
        }

        .login-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .login-right {
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

        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: bold;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
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

        .social-login {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            transform: translateY(-2px);
        }

        .btn-wechat {
            background: #1aad19;
            color: white;
        }

        .btn-qq {
            background: #12b7f5;
            color: white;
        }

        .btn-weibo {
            background: #e6162d;
            color: white;
        }

        .feature-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .login-left {
                padding: 40px 20px;
            }
            
            .login-right {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="login-container">
                    <div class="row g-0">
                        <!-- 左侧介绍 -->
                        <div class="col-lg-5 login-left">
                            <div class="mb-4">
                                <i class="fas fa-graduation-cap feature-icon"></i>
                                <h2>欢迎回来</h2>
                                <p class="mb-4">登录您的账户，继续学习之旅</p>
                            </div>
                            
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <i class="fas fa-book-open mb-2" style="font-size: 1.5rem;"></i>
                                    <div>继续学习</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <i class="fas fa-chart-line mb-2" style="font-size: 1.5rem;"></i>
                                    <div>学习进度</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <i class="fas fa-certificate mb-2" style="font-size: 1.5rem;"></i>
                                    <div>获得证书</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <i class="fas fa-users mb-2" style="font-size: 1.5rem;"></i>
                                    <div>社区交流</div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <p class="small">还没有账户？</p>
                                <a href="register.php" class="btn btn-outline-light">立即注册</a>
                            </div>
                        </div>
                        
                        <!-- 右侧登录表单 -->
                        <div class="col-lg-7 login-right">
                            <div class="text-center mb-4">
                                <h3>登录账户</h3>
                                <p class="text-muted">输入您的账户信息</p>
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
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="username" class="form-label">用户名或邮箱</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo isset($_POST['username']) ? safe_output($_POST['username']) : ''; ?>" 
                                               required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">密码</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                            <label class="form-check-label" for="remember_me">
                                                记住我
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6 text-end">
                                        <a href="forgot-password.php" class="text-decoration-none">忘记密码？</a>
                                    </div>
                                </div>
                                
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-login btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>立即登录
                                    </button>
                                </div>
                            </form>
                            
                            <!-- 第三方登录 -->
                            <div class="text-center">
                                <div class="position-relative">
                                    <hr>
                                    <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted">
                                        或使用以下方式登录
                                    </span>
                                </div>
                                
                                <div class="social-login">
                                    <button class="social-btn btn-wechat" title="微信登录">
                                        <i class="fab fa-weixin"></i>
                                    </button>
                                    <button class="social-btn btn-qq" title="QQ登录">
                                        <i class="fab fa-qq"></i>
                                    </button>
                                    <button class="social-btn btn-weibo" title="微博登录">
                                        <i class="fab fa-weibo"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <p class="text-muted small">
                                    登录即表示您同意我们的服务条款和隐私政策
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
        // 密码显示/隐藏切换
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // 第三方登录
        document.querySelectorAll('.social-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const platform = this.title;
                alert('即将集成' + platform + '功能');
            });
        });

        // 表单验证
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('请填写用户名和密码');
                return false;
            }
        });

        // 自动填充用户名（如果cookie中有）
        window.addEventListener('load', function() {
            const savedUsername = getCookie('saved_username');
            if (savedUsername) {
                document.getElementById('username').value = savedUsername;
                document.getElementById('remember_me').checked = true;
            }
        });

        // Cookie操作函数
        function getCookie(name) {
            const value = "; " + document.cookie;
            const parts = value.split("; " + name + "=");
            if (parts.length === 2) return parts.pop().split(";").shift();
        }
    </script>
</body>
</html>
