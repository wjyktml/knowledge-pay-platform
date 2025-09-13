<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// 获取用户信息
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 获取余额变动记录
$sql = "SELECT * FROM balance_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
$stmt = $db->prepare($sql);
$stmt->execute([$user_id]);
$balance_logs = $stmt->fetchAll();

// 处理充值请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'recharge') {
    $amount = floatval($_POST['amount']);
    $payment_method = $_POST['payment_method'];
    
    if ($amount < 1) {
        $error_message = '充值金额不能少于1元';
    } elseif ($amount > 10000) {
        $error_message = '单次充值金额不能超过10000元';
    } else {
        // 创建充值订单
        $order_data = [
            'user_id' => $user_id,
            'items' => [['knowledge_id' => 0, 'price' => $amount, 'quantity' => 1]],
            'payment_method' => $payment_method
        ];
        
        $result = createOrder($user_id, $order_data['items'], $payment_method);
        if ($result['success']) {
            // 重定向到支付页面
            header('Location: payment.php?order_id=' . $result['order_id']);
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
    <title>我的钱包 - 知识付费平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .wallet-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
        }

        .balance-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }

        .balance-amount {
            font-size: 3rem;
            font-weight: bold;
            color: #28a745;
            margin: 20px 0;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .payment-method {
            background: white;
            border-radius: 15px;
            padding: 20px;
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
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .alipay { color: #1677ff; }
        .wechat { color: #1aad19; }
        .unionpay { color: #e60012; }
        .usdt { color: #26a17b; }

        .quick-amounts {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .quick-amount {
            padding: 8px 16px;
            border: 2px solid #e9ecef;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quick-amount:hover,
        .quick-amount.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }

        .log-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .log-amount {
            font-weight: bold;
            font-size: 1.1rem;
        }

        .log-amount.positive {
            color: #28a745;
        }

        .log-amount.negative {
            color: #dc3545;
        }

        .nav-pills .nav-link {
            border-radius: 25px;
            margin: 0 5px;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
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
                <a class="nav-link" href="profile.php">个人中心</a>
            </div>
        </div>
    </nav>

    <!-- 钱包头部 -->
    <div class="wallet-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1><i class="fas fa-wallet me-3"></i>我的钱包</h1>
                    <p class="mb-0">管理您的账户余额和充值记录</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <!-- 余额卡片 -->
        <div class="balance-card">
            <h3>账户余额</h3>
            <div class="balance-amount">¥<?php echo number_format($user['balance'], 2); ?></div>
            <p class="text-muted">可用于购买课程和内容</p>
        </div>

        <!-- 充值区域 -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>账户充值</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="recharge">
                            
                            <!-- 充值金额 -->
                            <div class="mb-4">
                                <label class="form-label">充值金额</label>
                                <div class="quick-amounts">
                                    <div class="quick-amount" data-amount="10">¥10</div>
                                    <div class="quick-amount" data-amount="50">¥50</div>
                                    <div class="quick-amount" data-amount="100">¥100</div>
                                    <div class="quick-amount" data-amount="200">¥200</div>
                                    <div class="quick-amount" data-amount="500">¥500</div>
                                    <div class="quick-amount" data-amount="1000">¥1000</div>
                                </div>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       placeholder="请输入充值金额" min="1" max="10000" step="0.01" required>
                                <div class="form-text">单次充值金额：1-10000元</div>
                            </div>

                            <!-- 支付方式 -->
                            <div class="mb-4">
                                <label class="form-label">选择支付方式</label>
                                <div class="payment-methods">
                                    <div class="payment-method" data-method="alipay">
                                        <div class="payment-icon alipay">
                                            <i class="fab fa-alipay"></i>
                                        </div>
                                        <h6>支付宝</h6>
                                        <small class="text-muted">安全便捷</small>
                                    </div>
                                    
                                    <div class="payment-method" data-method="wechat">
                                        <div class="payment-icon wechat">
                                            <i class="fab fa-weixin"></i>
                                        </div>
                                        <h6>微信支付</h6>
                                        <small class="text-muted">快速支付</small>
                                    </div>
                                    
                                    <div class="payment-method" data-method="unionpay">
                                        <div class="payment-icon unionpay">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <h6>银联支付</h6>
                                        <small class="text-muted">银行卡支付</small>
                                    </div>
                                    
                                    <div class="payment-method" data-method="usdt">
                                        <div class="payment-icon usdt">
                                            <i class="fab fa-bitcoin"></i>
                                        </div>
                                        <h6>USDT</h6>
                                        <small class="text-muted">数字货币</small>
                                    </div>
                                </div>
                                <input type="hidden" id="payment_method" name="payment_method" required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>立即充值
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 余额记录 -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>余额记录</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($balance_logs)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-3"></i>
                                <p>暂无余额变动记录</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($balance_logs as $log): ?>
                                <div class="log-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold"><?php echo $log['description']; ?></div>
                                            <small class="text-muted"><?php echo getRelativeTime($log['created_at']); ?></small>
                                        </div>
                                        <div class="log-amount <?php echo $log['amount'] > 0 ? 'positive' : 'negative'; ?>">
                                            <?php echo $log['amount'] > 0 ? '+' : ''; ?>¥<?php echo number_format($log['amount'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 快速金额选择
        document.querySelectorAll('.quick-amount').forEach(item => {
            item.addEventListener('click', function() {
                // 移除其他active类
                document.querySelectorAll('.quick-amount').forEach(el => el.classList.remove('active'));
                // 添加active类
                this.classList.add('active');
                // 设置金额
                document.getElementById('amount').value = this.dataset.amount;
            });
        });

        // 支付方式选择
        document.querySelectorAll('.payment-method').forEach(item => {
            item.addEventListener('click', function() {
                // 移除其他active类
                document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('active'));
                // 添加active类
                this.classList.add('active');
                // 设置支付方式
                document.getElementById('payment_method').value = this.dataset.method;
            });
        });

        // 表单验证
        document.querySelector('form').addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value);
            const paymentMethod = document.getElementById('payment_method').value;

            if (!amount || amount < 1 || amount > 10000) {
                e.preventDefault();
                alert('请输入有效的充值金额（1-10000元）');
                return false;
            }

            if (!paymentMethod) {
                e.preventDefault();
                alert('请选择支付方式');
                return false;
            }
        });

        // 金额输入框变化时清除快速选择
        document.getElementById('amount').addEventListener('input', function() {
            document.querySelectorAll('.quick-amount').forEach(el => el.classList.remove('active'));
        });
    </script>
</body>
</html>
