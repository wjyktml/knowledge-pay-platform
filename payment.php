<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/payment.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    header('Location: index.php');
    exit;
}

// 获取订单信息
$sql = "SELECT o.*, u.username, u.email FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

// 获取订单详情
$sql = "SELECT oi.*, ki.title, ki.cover_image FROM order_items oi 
        LEFT JOIN knowledge_items ki ON oi.knowledge_id = ki.id 
        WHERE oi.order_id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

$error_message = '';
$success_message = '';

// 处理支付请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'pay') {
    $payment_method = $_POST['payment_method'];
    
    // 根据支付方式处理支付
    $payment_result = processPayment($order_id, $payment_method);
    
    if ($payment_result['success']) {
        if ($payment_result['redirect_url']) {
            // 重定向到第三方支付页面
            header('Location: ' . $payment_result['redirect_url']);
            exit;
        } else {
            $success_message = '支付成功！';
        }
    } else {
        $error_message = $payment_result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>订单支付 - 知识付费平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .payment-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .order-item {
            border-bottom: 1px solid #e9ecef;
            padding: 20px;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .payment-method {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .payment-method:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .payment-method.active {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .payment-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .alipay { color: #1677ff; }
        .wechat { color: #1aad19; }
        .unionpay { color: #e60012; }
        .usdt { color: #26a17b; }

        .order-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }

        .price-breakdown {
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
            margin-top: 15px;
        }

        .total-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #dc3545;
        }

        .countdown {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }

        .countdown-timer {
            font-size: 1.2rem;
            font-weight: bold;
            color: #856404;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea, #764ba2);">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>知识付费平台
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">返回首页</a>
                <a class="nav-link" href="orders.php">我的订单</a>
            </div>
        </div>
    </nav>

    <!-- 支付头部 -->
    <div class="payment-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1><i class="fas fa-credit-card me-3"></i>订单支付</h1>
                    <p class="mb-0">请选择支付方式完成订单</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="row">
            <!-- 订单信息 -->
            <div class="col-lg-8">
                <div class="order-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>
                            订单信息
                            <span class="badge bg-primary ms-2"><?php echo $order['order_no']; ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <img src="<?php echo $item['cover_image'] ?: 'assets/images/default-cover.jpg'; ?>" 
                                             class="item-image" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    </div>
                                    <div class="col">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                        <small class="text-muted">数量: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <div class="col-auto">
                                        <span class="fw-bold">¥<?php echo number_format($item['price'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 支付方式 -->
                <div class="order-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>选择支付方式</h5>
                    </div>
                    <div class="card-body">
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
                            <input type="hidden" name="action" value="pay">
                            
                            <div class="payment-methods">
                                <div class="payment-method" data-method="alipay">
                                    <div class="payment-icon alipay">
                                        <i class="fab fa-alipay"></i>
                                    </div>
                                    <h6>支付宝</h6>
                                    <p class="text-muted small">安全便捷，支持花呗分期</p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" value="alipay" id="alipay">
                                        <label class="form-check-label" for="alipay">选择支付宝</label>
                                    </div>
                                </div>
                                
                                <div class="payment-method" data-method="wechat">
                                    <div class="payment-icon wechat">
                                        <i class="fab fa-weixin"></i>
                                    </div>
                                    <h6>微信支付</h6>
                                    <p class="text-muted small">快速支付，支持微信零钱</p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" value="wechat" id="wechat">
                                        <label class="form-check-label" for="wechat">选择微信支付</label>
                                    </div>
                                </div>
                                
                                <div class="payment-method" data-method="unionpay">
                                    <div class="payment-icon unionpay">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <h6>银联支付</h6>
                                    <p class="text-muted small">支持各大银行卡</p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" value="unionpay" id="unionpay">
                                        <label class="form-check-label" for="unionpay">选择银联支付</label>
                                    </div>
                                </div>
                                
                                <div class="payment-method" data-method="usdt">
                                    <div class="payment-icon usdt">
                                        <i class="fab fa-bitcoin"></i>
                                    </div>
                                    <h6>USDT支付</h6>
                                    <p class="text-muted small">数字货币支付</p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" value="usdt" id="usdt">
                                        <label class="form-check-label" for="usdt">选择USDT支付</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-lock me-2"></i>确认支付 ¥<?php echo number_format($order['final_amount'], 2); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 订单摘要 -->
            <div class="col-lg-4">
                <div class="order-summary">
                    <h5 class="mb-3">订单摘要</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>商品总价:</span>
                        <span>¥<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>优惠减免:</span>
                            <span>-¥<?php echo number_format($order['discount_amount'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="price-breakdown">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">应付金额:</span>
                            <span class="total-amount">¥<?php echo number_format($order['final_amount'], 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="countdown">
                            <div class="text-muted small">请在以下时间内完成支付</div>
                            <div class="countdown-timer" id="countdown">15:00</div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6>支付说明</h6>
                        <ul class="list-unstyled small text-muted">
                            <li><i class="fas fa-check text-success me-2"></i>支持7天无理由退款</li>
                            <li><i class="fas fa-check text-success me-2"></i>支付完成后立即到账</li>
                            <li><i class="fas fa-check text-success me-2"></i>24小时客服支持</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 支付方式选择
        document.querySelectorAll('.payment-method').forEach(item => {
            item.addEventListener('click', function() {
                // 移除其他active类
                document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('active'));
                // 添加active类
                this.classList.add('active');
                // 选中对应的radio
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
            });
        });

        // 倒计时功能
        let timeLeft = 15 * 60; // 15分钟
        const countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownElement.textContent = 
                (minutes < 10 ? '0' : '') + minutes + ':' + 
                (seconds < 10 ? '0' : '') + seconds;
            
            if (timeLeft <= 0) {
                alert('支付时间已过期，请重新下单');
                window.location.href = 'orders.php';
                return;
            }
            
            timeLeft--;
        }
        
        // 每秒更新倒计时
        setInterval(updateCountdown, 1000);
        updateCountdown(); // 立即执行一次

        // 表单验证
        document.querySelector('form').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (!paymentMethod) {
                e.preventDefault();
                alert('请选择支付方式');
                return false;
            }
        });

        // 页面离开提醒
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = '确定要离开吗？未完成的支付可能会失效。';
        });
    </script>
</body>
</html>
