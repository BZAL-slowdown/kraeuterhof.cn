# 原服务器部署检测清单

## 当前结论

本地中文路径 `D:\展示官网+H5支付+会员系统\...` 会影响迅睿 CMS 自动加载。
已复制到纯英文路径：

```text
D:\www\kraeuterhof
```

在英文路径下，`public/test.php` 可以访问，说明源码入口和基础文件结构可用。

## 原服务器部署方式

1. 将源码放到纯英文/数字路径，例如：

```text
/www/wwwroot/kraeuterhof
```

2. 网站运行目录必须设置为：

```text
/www/wwwroot/kraeuterhof/public
```

3. PHP 版本建议：

```text
PHP 8.0 或 PHP 8.1
```

4. 必须启用 PHP 扩展：

```text
mysqli
pdo_mysql
curl
openssl
gd
fileinfo
mbstring
```

5. 检查数据库配置：

```text
config/database.php
```

确认原服务器 MySQL 中存在对应数据库、账号、密码。

6. 先访问环境检测：

```text
https://域名/test.php
```

全部通过后再访问：

```text
https://域名/index.php
```

## 当前本机测试结果

本机可访问：

```text
http://127.0.0.1:8090/test.php
```

但本机 PHP 缺少 GD、CURL、HTTPS/openssl 能力，并且本机没有客户服务器上的 MySQL 权限，所以首页不能在本机完整跑通。

## 上线前安全项

正式上线后删除或限制：

```text
public/test.php
public/install.php
```

并确认后台入口文件、数据库密码、支付密钥不暴露。

