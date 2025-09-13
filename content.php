<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$content_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$content_id) {
    header('Location: index.php');
    exit;
}

// 获取内容详情
$sql = "SELECT ki.*, c.name as category_name, u.username as author_name, u.avatar as author_avatar 
        FROM knowledge_items ki 
        LEFT JOIN categories c ON ki.category_id = c.id 
        LEFT JOIN users u ON ki.author_id = u.id 
        WHERE ki.id = ? AND ki.status = 'published'";
$stmt = $db->prepare($sql);
$stmt->execute([$content_id]);
$content = $stmt->fetch();

if (!$content) {
    header('Location: index.php');
    exit;
}

// 检查用户是否已购买
$user_has_purchased = false;
if (isset($_SESSION['user_id'])) {
    $user_has_purchased = hasUserPurchased($_SESSION['user_id'], $content_id);
}

// 增加浏览量
$sql = "UPDATE knowledge_items SET view_count = view_count + 1 WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$content_id]);

// 获取相关推荐
$sql = "SELECT * FROM knowledge_items 
        WHERE category_id = ? AND id != ? AND status = 'published' 
        ORDER BY view_count DESC LIMIT 4";
$stmt = $db->prepare($sql);
$stmt->execute([$content['category_id'], $content_id]);
$related_contents = $stmt->fetchAll();

// 获取评论
$sql = "SELECT c.*, u.username, u.avatar FROM comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.knowledge_id = ? AND c.status = 'approved' 
        ORDER BY c.created_at DESC LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute([$content_id]);
$comments = $stmt->fetchAll();

$error_message = '';
$success_message = '';

// 处理购买请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'purchase') {
    if (!isset($_SESSION['user_id'])) {
        $error_message = '请先登录';
    } elseif ($user_has_purchased) {
        $error_message = '您已购买过此内容';
    } elseif ($content['is_free']) {
        $error_message = '此内容为免费内容';
    } else {
        // 检查用户余额
        $user_balance = getUserBalance($_SESSION['user_id']);
        if ($user_balance < $content['price']) {
            $error_message = '余额不足，请先充值';
        } else {
            // 创建订单
            $items = [['knowledge_id' => $content_id, 'price' => $content['price'], 'quantity' => 1]];
            $result = createOrder($_SESSION['user_id'], $items, 'balance');
            
            if ($result['success']) {
                // 直接支付（余额支付）
                $payment_result = processPayment($result['order_id'], 'balance');
                if ($payment_result['success']) {
                    $success_message = '购买成功！';
                    $user_has_purchased = true;
                } else {
                    $error_message = '支付失败: ' . $payment_result['message'];
                }
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

// 处理评论提交
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'comment') {
    if (!isset($_SESSION['user_id'])) {
        $error_message = '请先登录';
    } elseif (!$user_has_purchased) {
        $error_message = '只有购买用户才能评论';
    } else {
        $comment_content = trim($_POST['comment_content']);
        $rating = intval($_POST['rating']);
        
        if (empty($comment_content)) {
            $error_message = '请输入评论内容';
        } elseif ($rating < 1 || $rating > 5) {
            $error_message = '请选择评分';
        } else {
            $sql = "INSERT INTO comments (user_id, knowledge_id, content, rating) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            if ($stmt->execute([$_SESSION['user_id'], $content_id, $comment_content, $rating])) {
                $success_message = '评论提交成功，等待审核';
                // 重新获取评论列表
                $sql = "SELECT c.*, u.username, u.avatar FROM comments c 
                        LEFT JOIN users u ON c.user_id = u.id 
                        WHERE c.knowledge_id = ? AND c.status = 'approved' 
                        ORDER BY c.created_at DESC LIMIT 10";
                $stmt = $db->prepare($sql);
                $stmt->execute([$content_id]);
                $comments = $stmt->fetchAll();
            } else {
                $error_message = '评论提交失败';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($content['title']); ?> - 知识付费平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .content-header {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .content-cover {
            height: 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
        }

        .content-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .content-info {
            padding: 30px;
        }

        .price-tag {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .price-tag.free {
            background: linear-gradient(45deg, #6c757d, #495057);
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }

        .content-body {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .content-tabs {
            border-bottom: 1px solid #e9ecef;
        }

        .content-tabs .nav-link {
            border: none;
            border-radius: 0;
            color: #6c757d;
            font-weight: 500;
        }

        .content-tabs .nav-link.active {
            color: #667eea;
            border-bottom: 2px solid #667eea;
        }

        .tab-content {
            padding: 30px;
        }

        .author-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 25px;
            text-align: center;
        }

        .author-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }

        .related-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .related-item {
            border-bottom: 1px solid #e9ecef;
            padding: 15px;
            transition: background 0.3s ease;
        }

        .related-item:hover {
            background: #f8f9fa;
        }

        .related-item:last-child {
            border-bottom: none;
        }

        .comment-item {
            border-bottom: 1px solid #e9ecef;
            padding: 20px 0;
        }

        .comment-item:last-child {
            border-bottom: none;
        }

        .comment-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .btn-purchase {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: bold;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-purchase:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }

        .btn-purchase:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .content-meta {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            color: #6c757d;
        }

        .meta-item i {
            margin-right: 5px;
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="profile.php">个人中心</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">登录</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <!-- 主要内容 -->
            <div class="col-lg-8">
                <!-- 内容头部 -->
                <div class="content-header">
                    <div class="content-cover">
                        <?php if ($content['cover_image']): ?>
                            <img src="<?php echo $content['cover_image']; ?>" alt="<?php echo htmlspecialchars($content['title']); ?>">
                        <?php else: ?>
                            <div class="text-center">
                                <i class="fas fa-<?php echo $content['type'] == 'video' ? 'play-circle' : ($content['type'] == 'audio' ? 'headphones' : 'file-alt'); ?> fa-4x mb-3"></i>
                                <h3><?php echo htmlspecialchars($content['title']); ?></h3>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="content-info">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="mb-3"><?php echo htmlspecialchars($content['title']); ?></h1>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($content['description']); ?></p>
                                
                                <div class="content-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-folder"></i>
                                        <span><?php echo htmlspecialchars($content['category_name']); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo htmlspecialchars($content['author_name']); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-eye"></i>
                                        <span><?php echo $content['view_count']; ?> 次浏览</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-shopping-cart"></i>
                                        <span><?php echo $content['purchase_count']; ?> 次购买</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo getRelativeTime($content['created_at']); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($content['rating'] > 0): ?>
                                    <div class="rating-stars mb-3">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $content['rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ms-2"><?php echo number_format($content['rating'], 1); ?> 分</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4 text-end">
                                <div class="mb-3">
                                    <?php if ($content['is_free']): ?>
                                        <span class="price-tag free">免费</span>
                                    <?php else: ?>
                                        <span class="price-tag">¥<?php echo number_format($content['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($user_has_purchased): ?>
                                    <button class="btn btn-success btn-lg" disabled>
                                        <i class="fas fa-check me-2"></i>已购买
                                    </button>
                                <?php elseif ($content['is_free']): ?>
                                    <button class="btn btn-primary btn-lg" disabled>
                                        <i class="fas fa-download me-2"></i>免费内容
                                    </button>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="purchase">
                                        <button type="submit" class="btn-purchase btn-lg">
                                            <i class="fas fa-shopping-cart me-2"></i>立即购买
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 内容详情 -->
                <div class="content-body">
                    <ul class="nav nav-tabs content-tabs" id="contentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button">
                                <i class="fas fa-info-circle me-2"></i>内容介绍
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button">
                                <i class="fas fa-comments me-2"></i>用户评论
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="contentTabContent">
                        <!-- 内容介绍 -->
                        <div class="tab-pane fade show active" id="description" role="tabpanel">
                            <?php if ($user_has_purchased || $content['is_free']): ?>
                                <div class="content-detail">
                                    <?php echo $content['content']; ?>
                                </div>
                                
                                <?php if ($content['file_path']): ?>
                                    <div class="mt-4">
                                        <h5>下载内容</h5>
                                        <a href="<?php echo $content['file_path']; ?>" class="btn btn-primary" download>
                                            <i class="fas fa-download me-2"></i>下载文件
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                                    <h5>购买后查看完整内容</h5>
                                    <p class="text-muted">此内容需要购买后才能查看</p>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="purchase">
                                        <button type="submit" class="btn-purchase">
                                            <i class="fas fa-shopping-cart me-2"></i>立即购买
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 用户评论 -->
                        <div class="tab-pane fade" id="comments" role="tabpanel">
                            <?php if ($user_has_purchased): ?>
                                <!-- 评论表单 -->
                                <div class="mb-4">
                                    <h5>发表评论</h5>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="comment">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">评分</label>
                                            <div class="rating-input">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <input type="radio" name="rating" value="<?php echo $i; ?>" id="rating<?php echo $i; ?>" required>
                                                    <label for="rating<?php echo $i; ?>" class="star-label">
                                                        <i class="fas fa-star"></i>
                                                    </label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <textarea class="form-control" name="comment_content" rows="4" placeholder="请输入您的评论..." required></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>提交评论
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 评论列表 -->
                            <div class="comments-list">
                                <h5>用户评论</h5>
                                <?php if (empty($comments)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-comment-slash fa-2x mb-3"></i>
                                        <p>暂无评论，成为第一个评论的用户吧！</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($comments as $comment): ?>
                                        <div class="comment-item">
                                            <div class="row">
                                                <div class="col-auto">
                                                    <img src="<?php echo $comment['avatar'] ?: 'assets/images/default-avatar.jpg'; ?>" 
                                                         class="comment-avatar" alt="<?php echo htmlspecialchars($comment['username']); ?>">
                                                </div>
                                                <div class="col">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($comment['username']); ?></h6>
                                                            <div class="rating-stars small">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <i class="fas fa-star<?php echo $i <= $comment['rating'] ? '' : '-o'; ?>"></i>
                                                                <?php endfor; ?>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted"><?php echo getRelativeTime($comment['created_at']); ?></small>
                                                    </div>
                                                    <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
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

            <!-- 侧边栏 -->
            <div class="col-lg-4">
                <!-- 作者信息 -->
                <div class="author-card mb-4">
                    <img src="<?php echo $content['author_avatar'] ?: 'assets/images/default-avatar.jpg'; ?>" 
                         class="author-avatar" alt="<?php echo htmlspecialchars($content['author_name']); ?>">
                    <h5><?php echo htmlspecialchars($content['author_name']); ?></h5>
                    <p class="text-muted small">内容作者</p>
                    <button class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-user-plus me-2"></i>关注作者
                    </button>
                </div>

                <!-- 相关推荐 -->
                <div class="related-content">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-thumbs-up me-2"></i>相关推荐</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($related_contents as $related): ?>
                            <div class="related-item">
                                <div class="row">
                                    <div class="col-8">
                                        <h6 class="mb-1">
                                            <a href="content.php?id=<?php echo $related['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($related['title']); ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-eye me-1"></i><?php echo $related['view_count']; ?>
                                        </small>
                                    </div>
                                    <div class="col-4 text-end">
                                        <?php if ($related['is_free']): ?>
                                            <span class="badge bg-success">免费</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">¥<?php echo number_format($related['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 评分星星交互
        document.querySelectorAll('.rating-input input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const rating = this.value;
                const labels = document.querySelectorAll('.rating-input .star-label');
                
                labels.forEach((label, index) => {
                    const star = label.querySelector('i');
                    if (index < rating) {
                        star.classList.remove('fa-star-o');
                        star.classList.add('fa-star');
                    } else {
                        star.classList.remove('fa-star');
                        star.classList.add('fa-star-o');
                    }
                });
            });
        });

        // 表单验证
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const action = this.querySelector('input[name="action"]').value;
                
                if (action === 'purchase') {
                    if (!confirm('确定要购买此内容吗？')) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        });
    </script>
    
    <style>
        .rating-input {
            display: flex;
            gap: 5px;
        }
        
        .rating-input input[type="radio"] {
            display: none;
        }
        
        .rating-input .star-label {
            cursor: pointer;
            color: #ddd;
            font-size: 1.5rem;
            transition: color 0.2s ease;
        }
        
        .rating-input .star-label:hover,
        .rating-input input:checked ~ .star-label {
            color: #ffc107;
        }
        
        .rating-input input:checked ~ .star-label i {
            color: #ffc107;
        }
    </style>
</body>
</html>
