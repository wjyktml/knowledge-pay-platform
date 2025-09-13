<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/payment.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$payment_no = isset($_GET['payment_no']) ? $_GET['payment_no'] : '';
$usdt_amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

if (!$order_id || !$payment_no || !$usdt_amount) {
    header('Location: ../index.php');
    exit;
}

// 获取订单信息
$sql = "SELECT o.*, u.username FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ../index.php');
    exit;
}

// 获取USDT钱包地址
$sql = "SELECT config_value FROM system_config WHERE config_key = 'usdt_wallet_address'";
$stmt = $db->prepare($sql);
$stmt->execute();
$wallet_address = $stmt->fetchColumn();

// 获取USDT汇率
$usdt_rate = 7.2; // 实际项目中从API获取
$cny_amount = $order['final_amount'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USDT支付 - 知识付费平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #26a17b 0%, #1e7e34 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .payment-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }

        .payment-header {
            background: linear-gradient(135deg, #26a17b 0%, #1e7e34 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .payment-body {
            padding: 40px;
        }

        .qr-code {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
        }

        .wallet-address {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            font-family: monospace;
            font-size: 0.9rem;
            word-break: break-all;
            margin: 15px 0;
        }

        .amount-display {
            background: linear-gradient(135deg, #26a17b, #1e7e34);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }

        .amount-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .copy-btn {
            background: #26a17b;
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .copy-btn:hover {
            background: #1e7e34;
            transform: translateY(-2px);
        }

        .status-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ffc107;
            margin-right: 10px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .instructions {
            background: #e7f3ff;
            border-left: 4px solid #26a17b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 10px 10px 0;
        }

        .countdown {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
        }

        .countdown-timer {
            font-size: 1.5rem;
            font-weight: bold;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="payment-container">
                    <div class="payment-header">
                        <i class="fab fa-bitcoin fa-3x mb-3"></i>
                        <h2>USDT支付</h2>
                        <p class="mb-0">请使用USDT完成支付</p>
                    </div>
                    
                    <div class="payment-body">
                        <!-- 支付金额 -->
                        <div class="amount-display">
                            <div>支付金额</div>
                            <div class="amount-number"><?php echo number_format($usdt_amount, 6); ?> USDT</div>
                            <div>≈ ¥<?php echo number_format($cny_amount, 2); ?> CNY</div>
                        </div>
                        
                        <!-- 倒计时 -->
                        <div class="countdown">
                            <div class="text-muted small">请在以下时间内完成支付</div>
                            <div class="countdown-timer" id="countdown">15:00</div>
                        </div>
                        
                        <!-- 钱包地址 -->
                        <div class="mb-4">
                            <h5><i class="fas fa-wallet me-2"></i>收款地址</h5>
                            <div class="wallet-address" id="walletAddress">
                                <?php echo $wallet_address ?: '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa'; ?>
                            </div>
                            <button class="copy-btn" onclick="copyAddress()">
                                <i class="fas fa-copy me-2"></i>复制地址
                            </button>
                        </div>
                        
                        <!-- 二维码 -->
                        <div class="qr-code">
                            <h6>扫码支付</h6>
                            <div id="qrcode"></div>
                            <p class="text-muted small mt-3">使用支持USDT的钱包扫描二维码</p>
                        </div>
                        
                        <!-- 支付说明 -->
                        <div class="instructions">
                            <h6><i class="fas fa-info-circle me-2"></i>支付说明</h6>
                            <ul class="mb-0">
                                <li>请向上述地址转账 <strong><?php echo number_format($usdt_amount, 6); ?> USDT</strong></li>
                                <li>请确保转账金额准确，否则可能无法自动确认</li>
                                <li>支付完成后系统将自动确认，请耐心等待</li>
                                <li>如有问题请联系客服</li>
                            </ul>
                        </div>
                        
                        <!-- 状态指示器 -->
                        <div class="status-indicator">
                            <div class="status-dot"></div>
                            <span>等待支付中...</span>
                        </div>
                        
                        <!-- 操作按钮 -->
                        <div class="d-grid gap-2">
                            <button class="btn btn-success" onclick="checkPayment()">
                                <i class="fas fa-sync-alt me-2"></i>检查支付状态
                            </button>
                            <button class="btn btn-outline-secondary" onclick="cancelPayment()">
                                <i class="fas fa-times me-2"></i>取消支付
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 引入QR码生成库 -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const orderId = <?php echo $order_id; ?>;
        const paymentNo = '<?php echo $payment_no; ?>';
        const usdtAmount = <?php echo $usdt_amount; ?>;
        const walletAddress = '<?php echo $wallet_address ?: '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa'; ?>';
        
        // 生成二维码
        function generateQRCode() {
            const qrText = `usdt:${walletAddress}?amount=${usdtAmount}`;
            QRCode.toCanvas(document.getElementById('qrcode'), qrText, {
                width: 200,
                height: 200,
                color: {
                    dark: '#26a17b',
                    light: '#ffffff'
                }
            }, function (error) {
                if (error) console.error(error);
            });
        }
        
        // 复制钱包地址
        function copyAddress() {
            navigator.clipboard.writeText(walletAddress).then(function() {
                alert('钱包地址已复制到剪贴板');
            }, function(err) {
                console.error('复制失败: ', err);
                // 备用复制方法
                const textArea = document.createElement('textarea');
                textArea.value = walletAddress;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('钱包地址已复制到剪贴板');
            });
        }
        
        // 检查支付状态
        function checkPayment() {
            fetch('../api/check-payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    payment_no: paymentNo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.paid) {
                        alert('支付成功！');
                        window.location.href = '../orders.php';
                    } else {
                        alert('支付尚未确认，请稍后再试');
                    }
                } else {
                    alert('检查支付状态失败: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('网络错误，请稍后再试');
            });
        }
        
        // 取消支付
        function cancelPayment() {
            if (confirm('确定要取消支付吗？')) {
                window.location.href = '../orders.php';
            }
        }
        
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
                window.location.href = '../orders.php';
                return;
            }
            
            timeLeft--;
        }
        
        // 自动检查支付状态
        function autoCheckPayment() {
            checkPayment();
        }
        
        // 页面加载完成后执行
        document.addEventListener('DOMContentLoaded', function() {
            generateQRCode();
            
            // 每秒更新倒计时
            setInterval(updateCountdown, 1000);
            updateCountdown(); // 立即执行一次
            
            // 每30秒自动检查支付状态
            setInterval(autoCheckPayment, 30000);
            
            // 页面离开提醒
            window.addEventListener('beforeunload', function(e) {
                e.preventDefault();
                e.returnValue = '确定要离开吗？未完成的支付可能会失效。';
            });
        });
    </script>
</body>
</html>
