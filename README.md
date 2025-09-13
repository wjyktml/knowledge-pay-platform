# 知识付费平台系统

一个完整的知识付费网站系统，支持多种支付方式，包含用户管理、内容管理、订单管理等功能。

## 🚀 系统特性

### 前端功能
- ✅ 响应式设计，支持移动端
- ✅ 用户注册/登录系统
- ✅ 知识内容浏览和购买
- ✅ 多种支付方式（支付宝、微信、银联、USDT）
- ✅ 用户钱包和余额管理
- ✅ 评论和评分系统
- ✅ 搜索和分类功能

### 后台管理
- ✅ 管理员登录系统
- ✅ 数据统计仪表板
- ✅ 用户管理
- ✅ 内容管理
- ✅ 订单和支付管理
- ✅ 评论管理
- ✅ 系统设置

### 支付系统
- ✅ 支付宝支付
- ✅ 微信支付
- ✅ 银联支付
- ✅ USDT数字货币支付
- ✅ 余额支付
- ✅ 支付状态实时检查

## 🛠 技术栈

- **前端**: HTML5, CSS3, JavaScript, Bootstrap 5
- **后端**: PHP 7.4+
- **数据库**: MySQL 8.0
- **支付**: 支付宝、微信支付、银联、USDT
- **部署**: CentOS 服务器

## 📋 系统要求

- PHP 7.4 或更高版本
- MySQL 8.0 或更高版本
- Apache/Nginx Web服务器
- 支持HTTPS（支付安全要求）
- 至少1GB内存，2GB推荐

## 🔧 安装部署

### 方法一：自动安装（推荐）

1. **上传文件到服务器**
   ```bash
   # 将项目文件上传到网站根目录
   scp -r knowledge_pay/ user@your-server:/var/www/html/
   ```

2. **访问安装页面**
   ```
   http://your-domain.com/install.php
   ```

3. **按照安装向导完成安装**
   - 环境检查
   - 数据库配置
   - 管理员账户设置
   - 完成安装

### 方法二：手动安装

#### 1. 环境准备

```bash
# CentOS 7/8 安装 LAMP 环境
yum update -y
yum install -y httpd mysql-server php php-mysql php-json php-curl php-gd php-mbstring

# 启动服务
systemctl start httpd
systemctl start mysqld
systemctl enable httpd
systemctl enable mysqld
```

#### 2. 数据库配置

```bash
# 登录MySQL
mysql -u root -p

# 创建数据库和用户
CREATE DATABASE knowledge_pay DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kp_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON knowledge_pay.* TO 'kp_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 3. 导入数据库结构

```bash
# 导入数据库结构
mysql -u kp_user -p knowledge_pay < database/schema.sql
```

#### 4. 配置文件设置

编辑 `config/database.php` 文件：

```php
private $host = 'localhost';
private $db_name = 'knowledge_pay';
private $username = 'kp_user';
private $password = 'your_password';
```

#### 5. 文件权限设置

```bash
# 设置文件权限
chown -R apache:apache /var/www/html/knowledge_pay
chmod -R 755 /var/www/html/knowledge_pay
chmod -R 777 /var/www/html/knowledge_pay/uploads
```

### 系统测试

安装完成后，访问 `http://your-domain.com/test.php` 进行系统测试。

### 6. 支付配置

在后台管理系统中配置支付参数：

1. 登录管理后台：`http://your-domain.com/admin/`
2. 进入系统设置
3. 配置各支付方式的参数：
   - 支付宝应用ID、私钥、公钥
   - 微信应用ID、商户号、API密钥
   - 银联支付参数
   - USDT钱包地址和私钥

## 📁 项目结构

```
knowledge_pay/
├── admin/                 # 后台管理系统
│   ├── index.php         # 管理后台首页
│   ├── login.php         # 管理员登录
│   └── logout.php        # 管理员退出
├── api/                  # API接口
│   └── check-payment.php # 支付状态检查
├── assets/               # 静态资源
│   ├── images/          # 图片文件
│   ├── css/             # 样式文件
│   └── js/              # JavaScript文件
├── config/              # 配置文件
│   └── database.php     # 数据库配置
├── database/            # 数据库文件
│   └── schema.sql       # 数据库结构
├── includes/            # 公共函数
│   ├── functions.php    # 通用函数
│   └── payment.php      # 支付处理
├── payment/             # 支付页面
│   └── usdt.php         # USDT支付页面
├── index.php            # 网站首页
├── welcome.html         # 欢迎页面
├── login.php            # 用户登录
├── register.php         # 用户注册
├── content.php          # 内容详情
├── wallet.php           # 用户钱包
├── payment.php          # 支付页面
└── logout.php           # 用户退出
```

## 🔐 默认账户

### 管理员账户
- 用户名：`admin`
- 密码：`password`
- 登录地址：`/admin/login.php`

### 测试用户
系统安装后可以注册新用户进行测试。

## 💳 支付配置说明

### 支付宝配置
1. 登录支付宝开放平台
2. 创建应用获取AppID
3. 配置应用公钥和私钥
4. 在后台设置中填入相应参数

### 微信支付配置
1. 登录微信商户平台
2. 获取商户号和API密钥
3. 配置支付回调地址
4. 在后台设置中填入相应参数

### USDT配置
1. 创建USDT钱包地址
2. 配置钱包私钥（用于验证交易）
3. 设置USDT汇率API接口

## 🚀 部署到生产环境

### 1. 域名和SSL配置

```bash
# 安装SSL证书（Let's Encrypt）
yum install -y certbot python3-certbot-apache
certbot --apache -d your-domain.com
```

### 2. 性能优化

```bash
# 启用Apache模块
a2enmod rewrite
a2enmod ssl
a2enmod headers

# 配置PHP优化
vim /etc/php.ini
# 调整以下参数：
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

### 3. 数据库优化

```sql
-- 创建索引优化查询性能
CREATE INDEX idx_orders_user_created ON orders(user_id, created_at);
CREATE INDEX idx_knowledge_category_status ON knowledge_items(category_id, status);
CREATE INDEX idx_purchases_user_knowledge ON user_purchases(user_id, knowledge_id);
```

## 🔧 维护和监控

### 日志监控
- Apache访问日志：`/var/log/httpd/access_log`
- Apache错误日志：`/var/log/httpd/error_log`
- PHP错误日志：`/var/log/php_errors.log`
- MySQL错误日志：`/var/log/mysqld.log`

### 定期备份
```bash
# 数据库备份脚本
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u kp_user -p knowledge_pay > /backup/knowledge_pay_$DATE.sql
```

### 性能监控
- 使用 `htop` 监控服务器资源
- 使用 `mysqladmin status` 监控数据库状态
- 定期检查磁盘空间使用情况

## 🐛 常见问题

### 1. 支付回调失败
- 检查服务器防火墙设置
- 确认回调URL可访问
- 验证支付参数配置

### 2. 文件上传失败
- 检查PHP上传限制
- 确认目录权限设置
- 验证磁盘空间充足

### 3. 数据库连接失败
- 检查数据库服务状态
- 验证连接参数配置
- 确认用户权限设置

## 📞 技术支持

如有技术问题，请检查：
1. 系统日志文件
2. PHP错误日志
3. 数据库连接状态
4. 支付接口配置

## 📄 许可证

本项目仅供学习和研究使用，商业使用请确保遵守相关法律法规。

## 🔄 更新日志

### v1.0.0 (2024-01-01)
- 初始版本发布
- 完整的用户管理系统
- 多种支付方式支持
- 后台管理功能
- 响应式设计

---

**注意**: 部署到生产环境前，请确保：
1. 修改默认密码
2. 配置HTTPS
3. 设置防火墙规则
4. 定期备份数据
5. 监控系统性能
