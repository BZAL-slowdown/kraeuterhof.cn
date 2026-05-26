# 宝塔部署步骤 - kraeuterhof.cn

## 1. 上传源码包

将部署包上传到服务器目录：

```text
/www/wwwroot/
```

解压后应得到：

```text
/www/wwwroot/kraeuterhof.cn/
```

目录中应能看到：

```text
public/
dayrui/
config/
template/
cache/
docs/
```

## 2. 宝塔站点设置

在宝塔面板中创建或修改站点：

- 域名：`kraeuterhof.cn`
- 网站目录：`/www/wwwroot/kraeuterhof.cn`
- 运行目录：`/public`
- PHP 版本：建议 PHP 8.2
- SSL：为 `kraeuterhof.cn` 配置正式证书

注意：不要把网站目录直接设为 `/www/wwwroot/kraeuterhof.cn/public` 后又设置运行目录 `/public`。

## 3. 配置数据库

编辑服务器上的文件：

```text
/www/wwwroot/kraeuterhof.cn/config/database.php
```

把占位值改成正式数据库信息：

```php
'hostname' => '127.0.0.1',
'username' => '数据库用户名',
'password' => '数据库密码',
'database' => '数据库名',
'DBPrefix' => 'dr_',
```

如果是正式客户数据库，不要直接用测试库整库覆盖。若只缺商城表，执行：

```text
docs/shop_tables_dr_prefix.sql
```

执行后确认存在：

```text
dr_shop_order
dr_shop_address
dr_shop_profile
```

## 4. 上传支付证书

支付证书不要放进 Git，也不要放在 `public/` 下。按实际开通情况上传到：

```text
/www/wwwroot/kraeuterhof.cn/cache/pay/wechat/apiclient_key.pem
/www/wwwroot/kraeuterhof.cn/cache/pay/wechat/wechatpay_public.pem
/www/wwwroot/kraeuterhof.cn/cache/pay/alipay/app_private_key.pem
/www/wwwroot/kraeuterhof.cn/cache/pay/alipay/app_public_key.pem
/www/wwwroot/kraeuterhof.cn/cache/pay/alipay/alipay_public_key.pem
```

然后在后台商城支付配置中填写正式商户参数，并确认回调域名使用：

```text
https://kraeuterhof.cn/
```

## 5. 权限设置

SSH 进入服务器后执行：

```bash
chown -R www:www /www/wwwroot/kraeuterhof.cn
chmod -R 755 /www/wwwroot/kraeuterhof.cn
chmod -R 777 /www/wwwroot/kraeuterhof.cn/cache
chmod -R 777 /www/wwwroot/kraeuterhof.cn/public/uploadfile
```

不要对 `/www/wwwroot` 整体执行递归权限修改。

## 6. 清理缓存

首次部署和修改配置后执行：

```bash
cd /www/wwwroot/kraeuterhof.cn
find cache/template -type f -name "*.php" -delete
```

也可以在迅睿后台中清理系统缓存。

## 7. 上线检查

前台检查：

- 首页能正常打开。
- 产品详情页能选择数量并进入下单流程。
- 注册、登录、找回密码流程正常。
- 会员中心、收货资料、我的订单正常。
- 防伪查询正常。

后台检查：

- 内容管理正常。
- 商城订单管理正常。
- 会员资料正常。
- 支付配置正常。
- 防伪码导入正常。

支付检查：

- 微信 H5 支付回调地址为 `https://kraeuterhof.cn/index.php?s=shop&c=pay&m=notify`。
- 支付宝异步回调地址为 `https://kraeuterhof.cn/index.php?s=shop&c=pay&m=alipay_notify`。
- 回调地址直接浏览器访问出现 `invalid payload` 属于正常现象，应由支付平台服务器 POST 触发。
