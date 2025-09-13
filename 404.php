<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>页面未找到 - 知识付费平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .error-icon {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 30px;
        }

        .error-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }

        .error-message {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .btn-home {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: bold;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="error-container">
                    <i class="fas fa-exclamation-triangle error-icon"></i>
                    <h1 class="error-title">404</h1>
                    <p class="error-message">抱歉，您访问的页面不存在或已被删除。</p>
                    <a href="index.php" class="btn-home">
                        <i class="fas fa-home me-2"></i>返回首页
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
