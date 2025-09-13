<?php
require_once 'config/database.php';

// 获取分类列表
function getCategories($limit = null) {
    global $db;
    $sql = "SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $stmt = $db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// 获取热门内容
function getHotContents($limit = 6) {
    global $db;
    $sql = "SELECT ki.*, c.name as category_name, u.username as author_name 
            FROM knowledge_items ki 
            LEFT JOIN categories c ON ki.category_id = c.id 
            LEFT JOIN users u ON ki.author_id = u.id 
            WHERE ki.status = 'published' 
            ORDER BY ki.purchase_count DESC, ki.view_count DESC 
            LIMIT ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// 获取推荐内容
function getFeaturedContents($limit = 4) {
    global $db;
    $sql = "SELECT ki.*, c.name as category_name, u.username as author_name 
            FROM knowledge_items ki 
            LEFT JOIN categories c ON ki.category_id = c.id 
            LEFT JOIN users u ON ki.author_id = u.id 
            WHERE ki.status = 'published' AND ki.is_featured = 1 
            ORDER BY ki.created_at DESC 
            LIMIT ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// 获取用户余额
function getUserBalance($user_id) {
    global $db;
    $sql = "SELECT balance FROM users WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result ? number_format($result['balance'], 2) : '0.00';
}

// 获取总用户数
function getTotalUsers() {
    global $db;
    $sql = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'];
}

// 获取总内容数
function getTotalContents() {
    global $db;
    $sql = "SELECT COUNT(*) as total FROM knowledge_items WHERE status = 'published'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'];
}

// 获取总订单数
function getTotalOrders() {
    global $db;
    $sql = "SELECT COUNT(*) as total FROM orders WHERE order_status = 'completed'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'];
}

// 获取总收入
function getTotalRevenue() {
    global $db;
    $sql = "SELECT SUM(final_amount) as total FROM orders WHERE order_status = 'completed'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'] ? number_format($result['total'] / 10000, 1) : '0.0';
}

// 用户注册
function registerUser($username, $email, $password, $phone = null) {
    global $db;
    
    // 检查用户名和邮箱是否已存在
    $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => '用户名或邮箱已存在'];
    }
    
    // 创建新用户
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password, phone) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute([$username, $email, $hashed_password, $phone])) {
        return ['success' => true, 'message' => '注册成功', 'user_id' => $db->lastInsertId()];
    } else {
        return ['success' => false, 'message' => '注册失败，请重试'];
    }
}

// 用户登录
function loginUser($username, $password) {
    global $db;
    
    $sql = "SELECT id, username, email, password, status FROM users WHERE (username = ? OR email = ?) AND status = 'active'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // 更新最后登录时间
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user['id']]);
        
        return ['success' => true, 'user' => $user];
    } else {
        return ['success' => false, 'message' => '用户名或密码错误'];
    }
}

// 生成订单号
function generateOrderNo() {
    return 'KP' . date('YmdHis') . rand(1000, 9999);
}

// 创建订单
function createOrder($user_id, $items, $payment_method, $coupon_id = null) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // 计算总金额
        $total_amount = 0;
        foreach ($items as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }
        
        // 应用优惠券
        $discount_amount = 0;
        if ($coupon_id) {
            $coupon = getCoupon($coupon_id);
            if ($coupon && $coupon['status'] == 'active' && 
                $coupon['start_time'] <= date('Y-m-d H:i:s') && 
                $coupon['end_time'] >= date('Y-m-d H:i:s') &&
                $coupon['usage_limit'] > $coupon['used_count']) {
                
                if ($coupon['type'] == 'percentage') {
                    $discount_amount = $total_amount * ($coupon['value'] / 100);
                    if ($coupon['max_discount'] > 0) {
                        $discount_amount = min($discount_amount, $coupon['max_discount']);
                    }
                } else {
                    $discount_amount = $coupon['value'];
                }
            }
        }
        
        $final_amount = $total_amount - $discount_amount;
        
        // 创建订单
        $order_no = generateOrderNo();
        $sql = "INSERT INTO orders (order_no, user_id, total_amount, discount_amount, final_amount, payment_method) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$order_no, $user_id, $total_amount, $discount_amount, $final_amount, $payment_method]);
        
        $order_id = $db->lastInsertId();
        
        // 创建订单详情
        foreach ($items as $item) {
            $sql = "INSERT INTO order_items (order_id, knowledge_id, price, quantity) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$order_id, $item['knowledge_id'], $item['price'], $item['quantity']]);
        }
        
        // 更新优惠券使用次数
        if ($coupon_id && $discount_amount > 0) {
            $sql = "UPDATE coupons SET used_count = used_count + 1 WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$coupon_id]);
        }
        
        $db->commit();
        return ['success' => true, 'order_id' => $order_id, 'order_no' => $order_no];
        
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => '创建订单失败: ' . $e->getMessage()];
    }
}

// 获取优惠券信息
function getCoupon($coupon_id) {
    global $db;
    $sql = "SELECT * FROM coupons WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$coupon_id]);
    return $stmt->fetch();
}

// 验证用户是否已购买内容
function hasUserPurchased($user_id, $knowledge_id) {
    global $db;
    $sql = "SELECT id FROM user_purchases WHERE user_id = ? AND knowledge_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id, $knowledge_id]);
    return $stmt->fetch() ? true : false;
}

// 记录用户购买
function recordUserPurchase($user_id, $knowledge_id, $order_id, $price) {
    global $db;
    $sql = "INSERT INTO user_purchases (user_id, knowledge_id, order_id, purchase_price) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$user_id, $knowledge_id, $order_id, $price]);
}

// 更新用户余额
function updateUserBalance($user_id, $amount, $type, $description = '', $order_id = null) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // 获取当前余额
        $sql = "SELECT balance FROM users WHERE id = ? FOR UPDATE";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('用户不存在');
        }
        
        $balance_before = $user['balance'];
        $balance_after = $balance_before + $amount;
        
        if ($balance_after < 0) {
            throw new Exception('余额不足');
        }
        
        // 更新余额
        $sql = "UPDATE users SET balance = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$balance_after, $user_id]);
        
        // 记录余额变动
        $sql = "INSERT INTO balance_logs (user_id, type, amount, balance_before, balance_after, description, related_order_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id, $type, $amount, $balance_before, $balance_after, $description, $order_id]);
        
        $db->commit();
        return ['success' => true, 'new_balance' => $balance_after];
        
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// 安全输出HTML
function safe_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// 格式化价格
function formatPrice($price) {
    return '¥' . number_format($price, 2);
}

// 格式化时间
function formatTime($datetime) {
    return date('Y-m-d H:i:s', strtotime($datetime));
}

// 获取相对时间
function getRelativeTime($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return '刚刚';
    if ($time < 3600) return floor($time/60) . '分钟前';
    if ($time < 86400) return floor($time/3600) . '小时前';
    if ($time < 2592000) return floor($time/86400) . '天前';
    if ($time < 31536000) return floor($time/2592000) . '个月前';
    return floor($time/31536000) . '年前';
}

// 验证邮箱格式
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// 验证手机号格式
function validatePhone($phone) {
    return preg_match('/^1[3-9]\d{9}$/', $phone);
}

// 生成随机字符串
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

// 记录用户操作日志
function logUserAction($user_id, $action, $description = '') {
    global $db;
    $sql = "INSERT INTO user_logs (user_id, action, description, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id, $action, $description, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
}

// 获取今日订单数
function getTodayOrders() {
    global $db;
    $sql = "SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'];
}

// 获取今日收入
function getTodayRevenue() {
    global $db;
    $sql = "SELECT SUM(final_amount) as total FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'] ? number_format($result['total'], 2) : '0.00';
}

// 获取待处理订单数
function getPendingOrders() {
    global $db;
    $sql = "SELECT COUNT(*) as total FROM orders WHERE payment_status = 'pending'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'];
}

// 获取待审核评论数
function getPendingComments() {
    global $db;
    $sql = "SELECT COUNT(*) as total FROM comments WHERE status = 'pending'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'];
}
?>
