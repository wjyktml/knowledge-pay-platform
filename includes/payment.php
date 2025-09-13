<?php
// 支付处理类
class PaymentProcessor {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // 处理支付
    public function processPayment($order_id, $payment_method) {
        try {
            // 获取订单信息
            $order = $this->getOrder($order_id);
            if (!$order) {
                return ['success' => false, 'message' => '订单不存在'];
            }
            
            if ($order['payment_status'] == 'paid') {
                return ['success' => false, 'message' => '订单已支付'];
            }
            
        // 根据支付方式处理
        switch ($payment_method) {
            case 'balance':
                return $this->processBalance($order);
            case 'alipay':
                return $this->processAlipay($order);
            case 'wechat':
                return $this->processWechat($order);
            case 'unionpay':
                return $this->processUnionpay($order);
            case 'usdt':
                return $this->processUSDT($order);
            default:
                return ['success' => false, 'message' => '不支持的支付方式'];
        }
        } catch (Exception $e) {
            return ['success' => false, 'message' => '支付处理失败: ' . $e->getMessage()];
        }
    }
    
    // 获取订单信息
    private function getOrder($order_id) {
        $sql = "SELECT * FROM orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$order_id]);
        return $stmt->fetch();
    }
    
    // 余额支付
    private function processBalance($order) {
        try {
            $this->db->beginTransaction();
            
            // 检查用户余额
            $sql = "SELECT balance FROM users WHERE id = ? FOR UPDATE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$order['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('用户不存在');
            }
            
            if ($user['balance'] < $order['final_amount']) {
                throw new Exception('余额不足');
            }
            
            // 扣除余额
            $new_balance = $user['balance'] - $order['final_amount'];
            $sql = "UPDATE users SET balance = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$new_balance, $order['user_id']]);
            
            // 记录余额变动
            $sql = "INSERT INTO balance_logs (user_id, type, amount, balance_before, balance_after, description, related_order_id) 
                    VALUES (?, 'purchase', ?, ?, ?, '购买内容', ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$order['user_id'], -$order['final_amount'], $user['balance'], $new_balance, $order['id']]);
            
            // 更新订单状态
            $sql = "UPDATE orders SET payment_status = 'paid', payment_time = NOW(), order_status = 'completed' WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$order['id']]);
            
            // 记录用户购买
            $this->recordUserPurchasesByOrderId($order['id']);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => '支付成功'
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // 支付宝支付
    private function processAlipay($order) {
        // 这里集成支付宝SDK
        // 实际项目中需要引入支付宝官方SDK
        
        $payment_no = 'ALI' . time() . rand(1000, 9999);
        
        // 记录支付记录
        $this->createPaymentRecord($order['id'], 'alipay', $payment_no, $order['final_amount']);
        
        // 模拟支付宝支付页面URL
        $alipay_url = $this->generateAlipayUrl($order, $payment_no);
        
        return [
            'success' => true,
            'redirect_url' => $alipay_url,
            'payment_no' => $payment_no
        ];
    }
    
    // 微信支付
    private function processWechat($order) {
        // 这里集成微信支付SDK
        // 实际项目中需要引入微信支付官方SDK
        
        $payment_no = 'WX' . time() . rand(1000, 9999);
        
        // 记录支付记录
        $this->createPaymentRecord($order['id'], 'wechat', $payment_no, $order['final_amount']);
        
        // 模拟微信支付页面URL
        $wechat_url = $this->generateWechatUrl($order, $payment_no);
        
        return [
            'success' => true,
            'redirect_url' => $wechat_url,
            'payment_no' => $payment_no
        ];
    }
    
    // 银联支付
    private function processUnionpay($order) {
        // 这里集成银联支付SDK
        // 实际项目中需要引入银联支付官方SDK
        
        $payment_no = 'UP' . time() . rand(1000, 9999);
        
        // 记录支付记录
        $this->createPaymentRecord($order['id'], 'unionpay', $payment_no, $order['final_amount']);
        
        // 模拟银联支付页面URL
        $unionpay_url = $this->generateUnionpayUrl($order, $payment_no);
        
        return [
            'success' => true,
            'redirect_url' => $unionpay_url,
            'payment_no' => $payment_no
        ];
    }
    
    // USDT支付
    private function processUSDT($order) {
        // 生成USDT支付地址和金额
        $payment_no = 'USDT' . time() . rand(1000, 9999);
        
        // 获取USDT汇率（这里模拟）
        $usdt_rate = $this->getUSDTExchangeRate();
        $usdt_amount = $order['final_amount'] / $usdt_rate;
        
        // 记录支付记录
        $this->createPaymentRecord($order['id'], 'usdt', $payment_no, $order['final_amount'], $usdt_amount);
        
        // 生成USDT支付页面
        $usdt_url = $this->generateUSDTUrl($order, $payment_no, $usdt_amount);
        
        return [
            'success' => true,
            'redirect_url' => $usdt_url,
            'payment_no' => $payment_no,
            'usdt_amount' => $usdt_amount
        ];
    }
    
    // 创建支付记录
    private function createPaymentRecord($order_id, $payment_method, $payment_no, $amount, $usdt_amount = null) {
        $sql = "INSERT INTO payment_records (order_id, payment_method, payment_no, amount, usdt_amount) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$order_id, $payment_method, $payment_no, $amount, $usdt_amount]);
    }
    
    // 生成支付宝支付URL
    private function generateAlipayUrl($order, $payment_no) {
        // 实际项目中这里会调用支付宝SDK生成支付URL
        return "payment/alipay.php?order_id={$order['id']}&payment_no={$payment_no}";
    }
    
    // 生成微信支付URL
    private function generateWechatUrl($order, $payment_no) {
        // 实际项目中这里会调用微信支付SDK生成支付URL
        return "payment/wechat.php?order_id={$order['id']}&payment_no={$payment_no}";
    }
    
    // 生成银联支付URL
    private function generateUnionpayUrl($order, $payment_no) {
        // 实际项目中这里会调用银联支付SDK生成支付URL
        return "payment/unionpay.php?order_id={$order['id']}&payment_no={$payment_no}";
    }
    
    // 生成USDT支付URL
    private function generateUSDTUrl($order, $payment_no, $usdt_amount) {
        return "payment/usdt.php?order_id={$order['id']}&payment_no={$payment_no}&amount={$usdt_amount}";
    }
    
    // 获取USDT汇率
    private function getUSDTExchangeRate() {
        // 实际项目中这里会调用API获取实时汇率
        // 这里返回模拟汇率
        return 7.2; // 1 USDT = 7.2 CNY
    }
    
    // 处理支付回调
    public function handlePaymentCallback($payment_method, $callback_data) {
        try {
            switch ($payment_method) {
                case 'alipay':
                    return $this->handleAlipayCallback($callback_data);
                case 'wechat':
                    return $this->handleWechatCallback($callback_data);
                case 'unionpay':
                    return $this->handleUnionpayCallback($callback_data);
                case 'usdt':
                    return $this->handleUSDTCallback($callback_data);
                default:
                    return ['success' => false, 'message' => '不支持的支付方式'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => '回调处理失败: ' . $e->getMessage()];
        }
    }
    
    // 支付宝回调处理
    private function handleAlipayCallback($callback_data) {
        // 验证支付宝签名
        if (!$this->verifyAlipaySignature($callback_data)) {
            return ['success' => false, 'message' => '签名验证失败'];
        }
        
        $order_no = $callback_data['out_trade_no'];
        $transaction_id = $callback_data['trade_no'];
        
        return $this->updateOrderPaymentStatus($order_no, $transaction_id, 'alipay');
    }
    
    // 微信支付回调处理
    private function handleWechatCallback($callback_data) {
        // 验证微信支付签名
        if (!$this->verifyWechatSignature($callback_data)) {
            return ['success' => false, 'message' => '签名验证失败'];
        }
        
        $order_no = $callback_data['out_trade_no'];
        $transaction_id = $callback_data['transaction_id'];
        
        return $this->updateOrderPaymentStatus($order_no, $transaction_id, 'wechat');
    }
    
    // 银联支付回调处理
    private function handleUnionpayCallback($callback_data) {
        // 验证银联支付签名
        if (!$this->verifyUnionpaySignature($callback_data)) {
            return ['success' => false, 'message' => '签名验证失败'];
        }
        
        $order_no = $callback_data['orderId'];
        $transaction_id = $callback_data['queryId'];
        
        return $this->updateOrderPaymentStatus($order_no, $transaction_id, 'unionpay');
    }
    
    // USDT支付回调处理
    private function handleUSDTCallback($callback_data) {
        // 验证USDT支付
        $payment_no = $callback_data['payment_no'];
        $tx_hash = $callback_data['tx_hash'];
        
        // 这里需要验证区块链交易
        if ($this->verifyUSDTTransaction($tx_hash, $payment_no)) {
            return $this->updateOrderPaymentStatusByPaymentNo($payment_no, $tx_hash, 'usdt');
        }
        
        return ['success' => false, 'message' => 'USDT交易验证失败'];
    }
    
    // 更新订单支付状态
    private function updateOrderPaymentStatus($order_no, $transaction_id, $payment_method) {
        try {
            $this->db->beginTransaction();
            
            // 更新订单状态
            $sql = "UPDATE orders SET payment_status = 'paid', payment_time = NOW(), order_status = 'completed' 
                    WHERE order_no = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$order_no]);
            
            // 更新支付记录
            $sql = "UPDATE payment_records SET status = 'success', transaction_id = ? 
                    WHERE order_id = (SELECT id FROM orders WHERE order_no = ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$transaction_id, $order_no]);
            
            // 记录用户购买
            $this->recordUserPurchases($order_no);
            
            $this->db->commit();
            return ['success' => true, 'message' => '支付成功'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => '更新订单状态失败: ' . $e->getMessage()];
        }
    }
    
    // 通过支付号更新订单状态
    private function updateOrderPaymentStatusByPaymentNo($payment_no, $transaction_id, $payment_method) {
        try {
            $this->db->beginTransaction();
            
            // 更新支付记录
            $sql = "UPDATE payment_records SET status = 'success', transaction_id = ? WHERE payment_no = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$transaction_id, $payment_no]);
            
            // 获取订单ID
            $sql = "SELECT order_id FROM payment_records WHERE payment_no = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payment_no]);
            $payment_record = $stmt->fetch();
            
            if ($payment_record) {
                // 更新订单状态
                $sql = "UPDATE orders SET payment_status = 'paid', payment_time = NOW(), order_status = 'completed' 
                        WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$payment_record['order_id']]);
                
                // 记录用户购买
                $this->recordUserPurchasesByOrderId($payment_record['order_id']);
            }
            
            $this->db->commit();
            return ['success' => true, 'message' => '支付成功'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => '更新订单状态失败: ' . $e->getMessage()];
        }
    }
    
    // 记录用户购买
    private function recordUserPurchases($order_no) {
        $sql = "SELECT oi.*, o.user_id FROM order_items oi 
                LEFT JOIN orders o ON oi.order_id = o.id 
                WHERE o.order_no = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$order_no]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item) {
            if ($item['knowledge_id'] > 0) { // 排除充值订单
                recordUserPurchase($item['user_id'], $item['knowledge_id'], $item['order_id'], $item['price']);
            }
        }
    }
    
    // 通过订单ID记录用户购买
    private function recordUserPurchasesByOrderId($order_id) {
        $sql = "SELECT oi.*, o.user_id FROM order_items oi 
                LEFT JOIN orders o ON oi.order_id = o.id 
                WHERE oi.order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item) {
            if ($item['knowledge_id'] > 0) { // 排除充值订单
                recordUserPurchase($item['user_id'], $item['knowledge_id'], $item['order_id'], $item['price']);
            }
        }
    }
    
    // 验证支付宝签名（模拟）
    private function verifyAlipaySignature($data) {
        // 实际项目中需要验证支付宝签名
        return true;
    }
    
    // 验证微信支付签名（模拟）
    private function verifyWechatSignature($data) {
        // 实际项目中需要验证微信支付签名
        return true;
    }
    
    // 验证银联支付签名（模拟）
    private function verifyUnionpaySignature($data) {
        // 实际项目中需要验证银联支付签名
        return true;
    }
    
    // 验证USDT交易（模拟）
    private function verifyUSDTTransaction($tx_hash, $payment_no) {
        // 实际项目中需要验证区块链交易
        return true;
    }
}

// 全局支付处理函数
function processPayment($order_id, $payment_method) {
    global $db;
    $processor = new PaymentProcessor($db);
    return $processor->processPayment($order_id, $payment_method);
}

// 处理支付回调
function handlePaymentCallback($payment_method, $callback_data) {
    global $db;
    $processor = new PaymentProcessor($db);
    return $processor->handlePaymentCallback($payment_method, $callback_data);
}
?>
