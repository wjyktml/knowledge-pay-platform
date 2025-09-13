<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/payment.php';

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? intval($input['order_id']) : 0;
$payment_no = isset($input['payment_no']) ? $input['payment_no'] : '';

if (!$order_id || !$payment_no) {
    echo json_encode(['success' => false, 'message' => '参数不完整']);
    exit;
}

try {
    // 检查订单是否存在
    $sql = "SELECT * FROM orders WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => '订单不存在']);
        exit;
    }
    
    // 检查支付记录
    $sql = "SELECT * FROM payment_records WHERE order_id = ? AND payment_no = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$order_id, $payment_no]);
    $payment_record = $stmt->fetch();
    
    if (!$payment_record) {
        echo json_encode(['success' => false, 'message' => '支付记录不存在']);
        exit;
    }
    
    // 如果已经支付成功
    if ($order['payment_status'] == 'paid') {
        echo json_encode([
            'success' => true,
            'paid' => true,
            'message' => '支付成功',
            'order_status' => $order['order_status']
        ]);
        exit;
    }
    
    // 根据支付方式检查支付状态
    $payment_method = $payment_record['payment_method'];
    $is_paid = false;
    
    switch ($payment_method) {
        case 'alipay':
            $is_paid = checkAlipayPayment($payment_no);
            break;
        case 'wechat':
            $is_paid = checkWechatPayment($payment_no);
            break;
        case 'unionpay':
            $is_paid = checkUnionpayPayment($payment_no);
            break;
        case 'usdt':
            $is_paid = checkUSDTPayment($payment_no);
            break;
    }
    
    if ($is_paid) {
        // 更新订单状态
        $result = updateOrderPaymentStatus($order_id, $payment_no);
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'paid' => true,
                'message' => '支付成功'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '支付状态更新失败: ' . $result['message']
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'paid' => false,
            'message' => '支付尚未确认'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '检查支付状态失败: ' . $e->getMessage()
    ]);
}

// 检查支付宝支付状态
function checkAlipayPayment($payment_no) {
    // 实际项目中这里会调用支付宝API查询支付状态
    // 这里模拟检查逻辑
    return false;
}

// 检查微信支付状态
function checkWechatPayment($payment_no) {
    // 实际项目中这里会调用微信支付API查询支付状态
    // 这里模拟检查逻辑
    return false;
}

// 检查银联支付状态
function checkUnionpayPayment($payment_no) {
    // 实际项目中这里会调用银联支付API查询支付状态
    // 这里模拟检查逻辑
    return false;
}

// 检查USDT支付状态
function checkUSDTPayment($payment_no) {
    // 实际项目中这里会检查区块链交易状态
    // 这里模拟检查逻辑
    
    // 模拟：随机返回支付成功（用于测试）
    if (rand(1, 100) <= 5) { // 5%的概率返回支付成功
        return true;
    }
    
    return false;
}

// 更新订单支付状态
function updateOrderPaymentStatus($order_id, $payment_no) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // 更新订单状态
        $sql = "UPDATE orders SET payment_status = 'paid', payment_time = NOW(), order_status = 'completed' 
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$order_id]);
        
        // 更新支付记录
        $sql = "UPDATE payment_records SET status = 'success', transaction_id = ? 
                WHERE order_id = ? AND payment_no = ?";
        $transaction_id = 'TX' . time() . rand(1000, 9999);
        $stmt = $db->prepare($sql);
        $stmt->execute([$transaction_id, $order_id, $payment_no]);
        
        // 获取订单信息
        $sql = "SELECT * FROM orders WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        // 如果是充值订单，更新用户余额
        if ($order['total_amount'] > 0) {
            $sql = "SELECT * FROM order_items WHERE order_id = ? AND knowledge_id = 0";
            $stmt = $db->prepare($sql);
            $stmt->execute([$order_id]);
            $recharge_item = $stmt->fetch();
            
            if ($recharge_item) {
                // 更新用户余额
                $result = updateUserBalance(
                    $order['user_id'], 
                    $recharge_item['price'], 
                    'recharge', 
                    '账户充值', 
                    $order_id
                );
                
                if (!$result['success']) {
                    throw new Exception($result['message']);
                }
            }
        }
        
        // 记录用户购买
        recordUserPurchasesByOrderId($order_id);
        
        $db->commit();
        return ['success' => true, 'message' => '订单状态更新成功'];
        
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// 通过订单ID记录用户购买
function recordUserPurchasesByOrderId($order_id) {
    global $db;
    
    $sql = "SELECT oi.*, o.user_id FROM order_items oi 
            LEFT JOIN orders o ON oi.order_id = o.id 
            WHERE oi.order_id = ? AND oi.knowledge_id > 0";
    $stmt = $db->prepare($sql);
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
    
    foreach ($items as $item) {
        recordUserPurchase($item['user_id'], $item['knowledge_id'], $item['order_id'], $item['price']);
    }
}
?>
