<?php
// 系统测试页面
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>知识付费平台 - 系统测试</h1>";

// 测试数据库连接
echo "<h2>1. 数据库连接测试</h2>";
try {
    require_once 'config/database.php';
    echo "✅ 数据库连接成功<br>";
    
    // 测试查询
    $sql = "SELECT COUNT(*) as count FROM users";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    echo "✅ 用户表查询成功，当前用户数: " . $result['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "<br>";
}

// 测试函数文件
echo "<h2>2. 函数文件测试</h2>";
try {
    require_once 'includes/functions.php';
    echo "✅ 函数文件加载成功<br>";
    
    // 测试获取分类
    $categories = getCategories();
    echo "✅ 获取分类成功，分类数: " . count($categories) . "<br>";
    
} catch (Exception $e) {
    echo "❌ 函数文件加载失败: " . $e->getMessage() . "<br>";
}

// 测试支付处理
echo "<h2>3. 支付处理测试</h2>";
try {
    require_once 'includes/payment.php';
    echo "✅ 支付处理文件加载成功<br>";
    
} catch (Exception $e) {
    echo "❌ 支付处理文件加载失败: " . $e->getMessage() . "<br>";
}

// 测试文件权限
echo "<h2>4. 文件权限测试</h2>";
$directories = ['config', 'includes', 'admin', 'api', 'payment'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_readable($dir)) {
            echo "✅ 目录 $dir 可读<br>";
        } else {
            echo "❌ 目录 $dir 不可读<br>";
        }
    } else {
        echo "❌ 目录 $dir 不存在<br>";
    }
}

// 测试PHP扩展
echo "<h2>5. PHP扩展测试</h2>";
$extensions = ['pdo', 'pdo_mysql', 'json', 'curl', 'gd', 'mbstring'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext 扩展已加载<br>";
    } else {
        echo "❌ $ext 扩展未加载<br>";
    }
}

// 测试会话
echo "<h2>6. 会话测试</h2>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ 会话功能正常<br>";
} else {
    echo "❌ 会话功能异常<br>";
}

// 测试URL重写
echo "<h2>7. URL重写测试</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "✅ mod_rewrite 模块已启用<br>";
    } else {
        echo "❌ mod_rewrite 模块未启用<br>";
    }
} else {
    echo "⚠️ 无法检测 mod_rewrite 模块状态<br>";
}

echo "<h2>测试完成</h2>";
echo "<p><a href='index.php'>返回首页</a> | <a href='admin/index.php'>管理后台</a></p>";
?>
