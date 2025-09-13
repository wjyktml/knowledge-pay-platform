<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查管理员登录状态
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取统计数据
$stats = [
    'total_users' => getTotalUsers(),
    'total_contents' => getTotalContents(),
    'total_orders' => getTotalOrders(),
    'total_revenue' => getTotalRevenue(),
    'today_orders' => getTodayOrders(),
    'today_revenue' => getTodayRevenue(),
    'pending_orders' => getPendingOrders(),
    'pending_comments' => getPendingComments()
];

// 获取最近订单
$sql = "SELECT o.*, u.username FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// 获取热门内容
$sql = "SELECT ki.*, c.name as category_name FROM knowledge_items ki 
        LEFT JOIN categories c ON ki.category_id = c.id 
        WHERE ki.status = 'published' 
        ORDER BY ki.purchase_count DESC LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute();
$popular_contents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - 知识付费平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 5px 10px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .main-content {
            padding: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
        }

        .badge {
            font-size: 0.8rem;
        }

        .navbar-admin {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <div class="col-md-2 p-0">
                <div class="sidebar">
                    <div class="p-3">
                        <h4 class="text-center mb-4">
                            <i class="fas fa-graduation-cap me-2"></i>
                            管理后台
                        </h4>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>仪表板
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>用户管理
                        </a>
                        <a class="nav-link" href="contents.php">
                            <i class="fas fa-book me-2"></i>内容管理
                        </a>
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-folder me-2"></i>分类管理
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-cart me-2"></i>订单管理
                        </a>
                        <a class="nav-link" href="payments.php">
                            <i class="fas fa-credit-card me-2"></i>支付管理
                        </a>
                        <a class="nav-link" href="comments.php">
                            <i class="fas fa-comments me-2"></i>评论管理
                        </a>
                        <a class="nav-link" href="coupons.php">
                            <i class="fas fa-ticket-alt me-2"></i>优惠券管理
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-2"></i>系统设置
                        </a>
                        <hr class="my-3">
                        <a class="nav-link" href="../index.php" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>查看前台
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>退出登录
                        </a>
                    </nav>
                </div>
            </div>

            <!-- 主内容区 -->
            <div class="col-md-10">
                <!-- 顶部导航 -->
                <nav class="navbar navbar-admin">
                    <div class="container-fluid">
                        <h5 class="mb-0">仪表板</h5>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-3">欢迎，<?php echo $_SESSION['admin_username']; ?></span>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="profile.php">个人资料</a></li>
                                    <li><a class="dropdown-item" href="settings.php">系统设置</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">退出登录</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <div class="main-content">
                    <!-- 统计卡片 -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(45deg, #667eea, #764ba2);">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                                        <div class="stat-label">总用户数</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(45deg, #28a745, #20c997);">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?php echo number_format($stats['total_contents']); ?></div>
                                        <div class="stat-label">内容总数</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(45deg, #ffc107, #fd7e14);">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?php echo number_format($stats['total_orders']); ?></div>
                                        <div class="stat-label">总订单数</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(45deg, #dc3545, #e83e8c);">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number">¥<?php echo $stats['total_revenue']; ?></div>
                                        <div class="stat-label">总收入(万元)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 今日统计 -->
                    <div class="row mb-4">
                        <div class="col-lg-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(45deg, #17a2b8, #6f42c1);">
                                        <i class="fas fa-calendar-day"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?php echo number_format($stats['today_orders']); ?></div>
                                        <div class="stat-label">今日订单</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(45deg, #fd7e14, #e83e8c);">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number">¥<?php echo $stats['today_revenue']; ?></div>
                                        <div class="stat-label">今日收入(元)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- 最近订单 -->
                        <div class="col-lg-8 mb-4">
                            <div class="table-container">
                                <div class="p-3 border-bottom">
                                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>最近订单</h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>订单号</th>
                                                <th>用户</th>
                                                <th>金额</th>
                                                <th>状态</th>
                                                <th>时间</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td>
                                                        <a href="orders.php?id=<?php echo $order['id']; ?>" class="text-decoration-none">
                                                            <?php echo $order['order_no']; ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                                    <td>¥<?php echo number_format($order['final_amount'], 2); ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = '';
                                                        $status_text = '';
                                                        switch ($order['payment_status']) {
                                                            case 'paid':
                                                                $status_class = 'bg-success';
                                                                $status_text = '已支付';
                                                                break;
                                                            case 'pending':
                                                                $status_class = 'bg-warning';
                                                                $status_text = '待支付';
                                                                break;
                                                            case 'failed':
                                                                $status_class = 'bg-danger';
                                                                $status_text = '支付失败';
                                                                break;
                                                            default:
                                                                $status_class = 'bg-secondary';
                                                                $status_text = '未知';
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                    </td>
                                                    <td><?php echo getRelativeTime($order['created_at']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- 热门内容 -->
                        <div class="col-lg-4 mb-4">
                            <div class="table-container">
                                <div class="p-3 border-bottom">
                                    <h5 class="mb-0"><i class="fas fa-fire me-2"></i>热门内容</h5>
                                </div>
                                <div class="p-3">
                                    <?php foreach ($popular_contents as $content): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-<?php echo $content['type'] == 'video' ? 'play' : 'file'; ?> text-muted"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">
                                                    <a href="../content.php?id=<?php echo $content['id']; ?>" 
                                                       class="text-decoration-none" target="_blank">
                                                        <?php echo htmlspecialchars($content['title']); ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-shopping-cart me-1"></i><?php echo $content['purchase_count']; ?> 次购买
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // 这里可以添加图表初始化代码
        console.log('管理后台已加载');
    </script>
</body>
</html>
