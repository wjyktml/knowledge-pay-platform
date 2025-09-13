<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// 获取热门内容
$hot_contents = getHotContents(6);
$categories = getCategories();
$featured_contents = getFeaturedContents(4);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>知识付费平台 - 专业在线学习平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 50%, var(--accent-color) 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .search-box {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .search-input {
            border-radius: 50px;
            padding: 15px 50px 15px 20px;
            border: none;
            font-size: 1.1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border: none;
            color: white;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .price-tag {
            background: linear-gradient(45deg, var(--success-color), #20c997);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .category-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .category-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .stats-section {
            background: white;
            padding: 60px 0;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .footer {
            background: var(--dark-color);
            color: white;
            padding: 40px 0 20px;
        }

        .user-menu {
            position: relative;
        }

        .dropdown-menu {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .badge {
            background: var(--danger-color);
            border-radius: 50%;
            padding: 5px 8px;
            font-size: 0.7rem;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>知识付费平台
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">首页</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="courses.php">课程</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">分类</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">关于我们</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown user-menu">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                                <span class="badge"><?php echo getUserBalance($_SESSION['user_id']); ?></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>个人中心</a></li>
                                <li><a class="dropdown-item" href="my-courses.php"><i class="fas fa-book me-2"></i>我的课程</a></li>
                                <li><a class="dropdown-item" href="wallet.php"><i class="fas fa-wallet me-2"></i>我的钱包</a></li>
                                <li><a class="dropdown-item" href="orders.php"><i class="fas fa-list me-2"></i>我的订单</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>退出登录</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">登录</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">注册</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 英雄区域 -->
    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">发现知识的力量</h1>
            <p class="hero-subtitle">专业的在线知识付费平台，汇聚优质内容，助力个人成长</p>
            
            <div class="search-box">
                <form action="search.php" method="GET">
                    <input type="text" class="form-control search-input" name="q" placeholder="搜索课程、文章、视频...">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- 分类区域 -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">热门分类</h2>
            <div class="row">
                <?php foreach ($categories as $category): ?>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                    <div class="category-card" onclick="location.href='category.php?id=<?php echo $category['id']; ?>'">
                        <div class="category-icon">
                            <i class="<?php echo $category['icon'] ?: 'fas fa-folder'; ?>"></i>
                        </div>
                        <h5><?php echo htmlspecialchars($category['name']); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($category['description']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 推荐内容 -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">精选推荐</h2>
            <div class="row">
                <?php foreach ($featured_contents as $content): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo $content['cover_image'] ?: 'assets/images/default-cover.jpg'; ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($content['title']); ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($content['title']); ?></h5>
                            <p class="card-text text-muted flex-grow-1">
                                <?php echo mb_substr(strip_tags($content['description']), 0, 80) . '...'; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="price-tag">
                                    <?php if ($content['is_free']): ?>
                                        免费
                                    <?php else: ?>
                                        ¥<?php echo number_format($content['price'], 2); ?>
                                    <?php endif; ?>
                                </span>
                                <a href="content.php?id=<?php echo $content['id']; ?>" class="btn btn-primary btn-sm">查看详情</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 热门内容 -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">热门内容</h2>
            <div class="row">
                <?php foreach ($hot_contents as $content): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo $content['cover_image'] ?: 'assets/images/default-cover.jpg'; ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($content['title']); ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($content['title']); ?></h5>
                            <p class="card-text text-muted flex-grow-1">
                                <?php echo mb_substr(strip_tags($content['description']), 0, 100) . '...'; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <div>
                                    <small class="text-muted">
                                        <i class="fas fa-eye me-1"></i><?php echo $content['view_count']; ?>
                                        <i class="fas fa-shopping-cart ms-3 me-1"></i><?php echo $content['purchase_count']; ?>
                                    </small>
                                </div>
                                <span class="price-tag">
                                    <?php if ($content['is_free']): ?>
                                        免费
                                    <?php else: ?>
                                        ¥<?php echo number_format($content['price'], 2); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 统计数据 -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo getTotalUsers(); ?></div>
                        <div class="text-muted">注册用户</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo getTotalContents(); ?></div>
                        <div class="text-muted">知识内容</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo getTotalOrders(); ?></div>
                        <div class="text-muted">成功订单</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo getTotalRevenue(); ?></div>
                        <div class="text-muted">平台收入(万元)</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5>知识付费平台</h5>
                    <p class="text-muted">专业的在线知识付费平台，汇聚优质内容，助力个人成长。</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>快速链接</h6>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-muted">关于我们</a></li>
                        <li><a href="contact.php" class="text-muted">联系我们</a></li>
                        <li><a href="help.php" class="text-muted">帮助中心</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>服务支持</h6>
                    <ul class="list-unstyled">
                        <li><a href="terms.php" class="text-muted">服务条款</a></li>
                        <li><a href="privacy.php" class="text-muted">隐私政策</a></li>
                        <li><a href="refund.php" class="text-muted">退款政策</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6>联系我们</h6>
                    <p class="text-muted">
                        <i class="fas fa-envelope me-2"></i>contact@knowledgepay.com<br>
                        <i class="fas fa-phone me-2"></i>400-123-4567
                    </p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-muted">
                <p>&copy; 2024 知识付费平台. 保留所有权利.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 搜索建议功能
        document.querySelector('.search-input').addEventListener('input', function(e) {
            const query = e.target.value;
            if (query.length > 2) {
                // 这里可以添加搜索建议的AJAX请求
                console.log('搜索建议:', query);
            }
        });

        // 平滑滚动
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>
