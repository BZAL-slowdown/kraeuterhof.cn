# 七叶庄园官网 + H5 支付 + 会员系统交付说明

## 一、交付范围

本包基于客户提供的迅睿 CMS 源码扩展，已包含：

1. 展示官网源码与原有内容模板。
2. 会员注册、登录、会员中心、头像/昵称、收货资料、我的订单。
3. 商品页下单逻辑，登录后可使用默认收货资料快速下单。
4. 微信 H5 支付对接代码、支付回调、支付返回页。
5. 后台商城管理：订单管理、会员资料、支付配置。
6. 防伪查询页面 PC 端显示修复。
7. 后台防伪码批量导入页面，支持 CSV/TXT 导入。

## 二、服务器要求

1. PHP 7.4 及以上，当前测试环境为 PHP 8.2。
2. MySQL 5.6 及以上。
3. Nginx/Apache 均可，宝塔建议使用 Nginx。
4. 网站运行目录必须指向项目下的 `public` 目录。
5. PHP open_basedir 必须允许访问项目根目录，例如 `/www/wwwroot/kraeuterhof/`，否则 CMS 无法读取 `dayrui/`、`config/`、`cache/`。

## 三、部署步骤

1. 上传本发布包到服务器，例如 `/www/wwwroot/kraeuterhof/`。
2. 解压后应看到：
   - `public/`
   - `dayrui/`
   - `config/`
   - `template/`
   - `cache/`
3. 宝塔网站根目录设置为：
   - `/www/wwwroot/kraeuterhof/public`
4. PHP 版本选择 7.4+。
5. 设置权限：

```bash
chown -R www:www /www/wwwroot/kraeuterhof
chmod -R 755 /www/wwwroot/kraeuterhof
chmod -R 777 /www/wwwroot/kraeuterhof/cache
chmod -R 777 /www/wwwroot/kraeuterhof/public/uploadfile
```

6. 清理模板缓存：

```bash
find /www/wwwroot/kraeuterhof/cache/template -type f ! -name ".htaccess" -delete
```

## 四、数据库

1. 如果是全新测试站：先导入客户原始数据库备份。
2. 再确认以下三张商城表存在：
   - `dr_shop_order`
   - `dr_shop_address`
   - `dr_shop_profile`
3. 如果不存在，在 phpMyAdmin 执行 `dayrui/App/Shop/Config/Install.sql` 中的建表语句，注意将 `{dbprefix}` 替换为 `dr_`。
4. 修改数据库配置：
   - 文件：`config/database.php`
   - 修改 `hostname / username / password / database / DBPrefix`

## 五、必须手动配置

### 1. 域名

迅睿后台：

`设置 → 网站设置 → 域名设置`

正式环境改成客户正式域名，例如：

`kraeuterhof.cn`

### 2. 微信 H5 支付

迅睿后台：

`商城 → 支付配置`

需填写：

- AppID
- 商户号 mchid
- 商户证书序列号
- APIv3 密钥
- 商户私钥路径
- 微信支付平台公钥/平台证书路径
- 支付回调地址
- 支付返回地址
- H5 场景域名

正式域名建议：

- 回调地址：`https://kraeuterhof.cn/index.php?s=shop&c=pay&m=notify`
- 返回地址：`https://kraeuterhof.cn/index.php?s=shop&c=pay&m=return_page&out_trade_no={out_trade_no}`
- H5 场景域名：`https://kraeuterhof.cn/`

证书建议放置：

```text
/www/wwwroot/kraeuterhof/cache/pay/wechat/apiclient_key.pem
/www/wwwroot/kraeuterhof/cache/pay/wechat/wechatpay_public.pem
```

权限建议：

```bash
chmod 700 /www/wwwroot/kraeuterhof/cache/pay
chmod 600 /www/wwwroot/kraeuterhof/cache/pay/wechat/*.pem
chown -R www:www /www/wwwroot/kraeuterhof/cache/pay
```

### 3. 测试支付开关

文件：

`dayrui/App/Shop/Config/Shop.php`

测试期可以：

```php
'allow_admin_mark_paid' => true,
```

正式上线前必须改为：

```php
'allow_admin_mark_paid' => false,
```

### 4. 邮件 SMTP

迅睿后台：

`系统 → 系统维护 → 邮件设置`

邮箱注册验证码依赖 SMTP。请使用客户公司邮箱或专用 no-reply 邮箱，不要使用开发人员个人邮箱。

### 5. 短信接口

迅睿后台：

`系统 → 系统维护 → 短信设置`

手机注册验证码需要短信服务商接口。客户需提供短信服务商账号、签名、模板 ID、AccessKey/Secret 等资料。

## 六、防伪码导入

后台入口：

`商城 → 订单管理 → 防伪码导入`

或直接访问：

`admin后台.php?s=shop&c=antifake&m=index`

CSV 格式：

```csv
code,production_date,origin,message
A1234567890123,2026-05-13,广州,该防伪码有效，为正品
B12345678901234567,2026-05-13,广州,该防伪码有效，为正品
```

导入后会写入原有 `防伪辨识管理` 模块，前台查询同步生效。

## 七、交付前检查

1. 首页可打开。
2. 商品详情页可选择规格、填写数量并下单。
3. 未登录下单会跳转登录。
4. 注册可通过邮箱/手机验证码验证。
5. 会员中心可查看资料、订单、收货资料。
6. 后台商城订单能看到订单记录。
7. 防伪码导入后，前台防伪查询能查到。
8. 微信支付配置完成后，使用手机浏览器测试 H5 支付。

## 八、当前注意事项

1. 微信 H5 支付只能在微信商户平台配置过的 H5 支付域名下真实验证。
2. 当前测试域名如果未加入商户平台 H5 支付域名，无法完成真实支付。
3. 防伪查询已接入客户原有防伪内容表；如需查询次数、首次查询时间、重复查询预警，建议后续升级为服务端防伪校验接口。
4. 正式上线前建议删除服务器上的 `public/test.php`、`public/install.php`。
