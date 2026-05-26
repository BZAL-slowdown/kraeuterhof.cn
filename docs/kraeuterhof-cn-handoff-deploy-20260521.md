# 七叶庄园项目交付迁移部署说明

生成日期：2026-05-21  
目标域名：`kraeuterhof.cn`  
当前测试域名：`shop.zibohaowen.top`

本文用于把当前已经部署在测试域名上的完整项目，迁移交付到客户正式域名 `kraeuterhof.cn`。操作时请同时迁移源码、数据库、上传文件、支付证书，并在新环境里修改域名、数据库、支付回调、缓存和权限。

## 一、交付前先确认

交付给客户前，至少应准备这些文件：

1. 项目源码压缩包。
   - 源码目录应包含：`public/`、`dayrui/`、`config/`、`template/`、`cache/`、`docs/`。
   - 不要只打包 `public/`，否则 CMS 会缺少 `dayrui/`、`config/`、`template/` 等核心目录。

2. 数据库备份文件。
   - 从当前可正常运行的测试站导出。
   - 建议使用 `utf8mb4` 字符集导出。

3. 支付证书与密钥文件。
   - 支付宝：`cache/pay/alipay/`
   - 微信支付：`cache/pay/wechat/`
   - 这些文件不能放到 `public/` 目录下，避免被外部直接访问。

4. 后台登录信息、数据库账号、短信/邮件服务账号、支付商户后台账号。
   - 不要把明文密钥写进公开文档。
   - 建议单独通过安全渠道交付给客户。

## 二、当前服务器导出源码

如果在宝塔面板操作：

1. 进入文件管理。
2. 找到当前项目根目录，例如：

```text
/www/wwwroot/kraeuterhof
```

3. 确认目录里可以看到：

```text
public/
dayrui/
config/
template/
cache/
docs/
```

4. 压缩整个 `kraeuterhof` 项目根目录。

建议排除这些临时文件：

```text
cache/template/
cache/log/
runtime_php_server.out.log
runtime_php_server.err.log
旧的补丁 zip 包
```

注意：不要删除或遗漏 `cache/pay/`，这里保存支付证书。

如果用宝塔终端导出，可以参考：

```bash
cd /www/wwwroot
tar \
  --exclude='kraeuterhof/cache/template/*' \
  --exclude='kraeuterhof/cache/log/*' \
  --exclude='kraeuterhof/*.zip' \
  --exclude='kraeuterhof/runtime_php_server.*.log' \
  -czf /www/backup/kraeuterhof_source_20260521.tar.gz kraeuterhof
```

## 三、当前服务器导出数据库

宝塔面板方式：

1. 打开宝塔面板。
2. 进入「数据库」。
3. 找到当前测试站使用的数据库。
4. 点击「备份」或「导出」。
5. 下载 `.sql` 或 `.sql.gz` 文件。

终端方式参考：

```bash
mysqldump -u数据库用户名 -p --default-character-set=utf8mb4 数据库名 > /www/backup/kraeuterhof_db_20260521.sql
```

导出后请保留一份原始备份，不要直接在原始备份上做替换。

## 四、客户服务器新建站点

在客户宝塔面板里新建网站：

```text
域名：kraeuterhof.cn
PHP：建议 PHP 8.2
数据库：MySQL 5.7 / 8.0 均可
```

如果客户需要同时访问 `www.kraeuterhof.cn`，也可以一起绑定：

```text
kraeuterhof.cn
www.kraeuterhof.cn
```

宝塔网站目录建议这样设置：

```text
项目根目录：/www/wwwroot/kraeuterhof
运行目录：/public
最终入口：/www/wwwroot/kraeuterhof/public/index.php
```

不要把源码全部上传到 `/www/wwwroot/kraeuterhof/public` 里面。`public` 只是对外访问入口，项目核心代码应放在 `public` 的上一级。

PHP 扩展建议确认已开启：

```text
mysqli
curl
openssl
mbstring
gd
fileinfo
zip
```

如果宝塔开启了「防跨站攻击 open_basedir」，要保证 PHP 能访问整个项目根目录：

```text
/www/wwwroot/kraeuterhof
```

否则可能出现能访问 `public`，但读不到 `dayrui/`、`config/`、`cache/` 的问题。

## 五、上传并解压源码

把源码包上传到客户服务器，例如：

```text
/www/wwwroot/
```

解压后应得到：

```text
/www/wwwroot/kraeuterhof/public
/www/wwwroot/kraeuterhof/dayrui
/www/wwwroot/kraeuterhof/config
/www/wwwroot/kraeuterhof/template
/www/wwwroot/kraeuterhof/cache
```

如果解压后出现两层目录，例如：

```text
/www/wwwroot/kraeuterhof/kraeuterhof/public
```

需要把里面那层文件移动到正确项目根目录。

## 六、导入数据库

在客户宝塔面板中新建数据库，例如：

```text
数据库名：kraeuterhof
用户名：kraeuterhof
密码：使用强密码
```

然后通过 phpMyAdmin 或宝塔数据库导入功能，把测试站导出的 SQL 导入客户数据库。

导入后重点检查是否存在这些表：

```text
dr_site
dr_member
dr_admin
dr_shop_order
dr_shop_address
dr_shop_profile
```

如果 `dr_shop_order`、`dr_shop_address`、`dr_shop_profile` 不存在，说明商城相关表没有导入完整，需要补导项目里的商城建表 SQL 或重新导出完整数据库。

## 七、修改数据库连接配置

编辑客户服务器文件：

```text
/www/wwwroot/kraeuterhof/config/database.php
```

把数据库连接信息改成客户服务器的新数据库：

```php
'hostname' => 'localhost',
'username' => '数据库用户名',
'password' => '数据库密码',
'database' => '数据库名',
'DBPrefix' => 'dr_',
```

如果打开网站出现：

```text
Unable to connect to the database
Access denied for user
```

通常就是这里的数据库名、用户名、密码、端口或权限不正确。

## 八、替换测试域名和旧域名

迁移到正式域名后，必须把测试域名和旧域名替换为：

```text
kraeuterhof.cn
```

需要重点替换这些旧域名：

```text
shop.zibohaowen.top
www.kraeuterhof.com.cn
kraeuterhof.com.cn
```

推荐优先在后台修改：

```text
后台 -> 设置 -> 网站设置 -> 站点域名
```

如果后台暂时进不去，可以先在 phpMyAdmin 执行下面 SQL。执行前请先备份数据库。

```sql
UPDATE dr_site
SET domain = REPLACE(domain, 'shop.zibohaowen.top', 'kraeuterhof.cn');

UPDATE dr_site
SET domain = REPLACE(domain, 'www.kraeuterhof.com.cn', 'kraeuterhof.cn');

UPDATE dr_site
SET domain = REPLACE(domain, 'kraeuterhof.com.cn', 'kraeuterhof.cn');

UPDATE dr_site
SET setting = REPLACE(setting, 'shop.zibohaowen.top', 'kraeuterhof.cn')
WHERE setting LIKE '%shop.zibohaowen.top%';

UPDATE dr_site
SET setting = REPLACE(setting, 'www.kraeuterhof.com.cn', 'kraeuterhof.cn')
WHERE setting LIKE '%www.kraeuterhof.com.cn%';

UPDATE dr_site
SET setting = REPLACE(setting, 'kraeuterhof.com.cn', 'kraeuterhof.cn')
WHERE setting LIKE '%kraeuterhof.com.cn%';
```

如果数据库里还有其他配置表保存了域名，可以在 phpMyAdmin 里使用「搜索」功能，全库搜索：

```text
shop.zibohaowen.top
www.kraeuterhof.com.cn
kraeuterhof.com.cn
```

发现后再替换成：

```text
kraeuterhof.cn
```

如果没有改干净，常见表现是：

1. 从 `shop.zibohaowen.top` 点会员中心跳到旧域名。
2. 页面表单提交地址还是 `http://`。
3. 支付回调地址还是测试域名。
4. 浏览器提示表单不安全或自动填充关闭。

## 九、清理缓存

修改域名、数据库、模板或支付配置后，一定要清缓存。

在客户服务器终端执行：

```bash
cd /www/wwwroot/kraeuterhof
find cache/template -type f ! -name ".htaccess" -delete
rm -f cache/config/site.php
rm -f cache/config/domain_site.php
rm -f cache/config/domain_sso.php
rm -f cache/config/webpath.php
```

注意：不要直接执行 `rm -rf cache`，因为 `cache/pay/` 里面有支付证书。

后台可以再执行一次：

```text
后台 -> 系统 -> 系统维护 -> 系统缓存 -> 更新缓存
```

## 十、设置文件权限

客户服务器执行：

```bash
cd /www/wwwroot
chown -R www:www kraeuterhof
find kraeuterhof -type d -exec chmod 755 {} \;
find kraeuterhof -type f -exec chmod 644 {} \;
chmod -R 775 kraeuterhof/cache
chmod -R 775 kraeuterhof/public/uploadfile
```

支付证书建议收紧权限：

```bash
chmod 700 /www/wwwroot/kraeuterhof/cache/pay
chmod -R 600 /www/wwwroot/kraeuterhof/cache/pay/alipay/* 2>/dev/null
chmod -R 600 /www/wwwroot/kraeuterhof/cache/pay/wechat/* 2>/dev/null
chown -R www:www /www/wwwroot/kraeuterhof/cache/pay
```

如果网站运行后提示缓存无法写入，可以临时把 `cache` 改成 `777` 排查：

```bash
chmod -R 777 /www/wwwroot/kraeuterhof/cache
```

确认正常后再根据服务器安全策略收紧权限。

## 十一、关闭开发者模式

正式交付前检查：

```text
/www/wwwroot/kraeuterhof/public/index.php
```

确保开发者模式关闭：

```php
define('IS_DEV', 0);
```

如果开启开发者模式，网站报错时会显示详细代码路径和错误信息，不适合正式环境。

## 十二、SSL 和 HTTPS

客户域名上线后应配置 SSL：

```text
https://kraeuterhof.cn/
```

宝塔面板操作：

```text
网站 -> kraeuterhof.cn -> SSL -> Let's Encrypt 或客户证书 -> 开启强制 HTTPS
```

支付回调、注册登录、验证码接口都建议使用 HTTPS。

如果页面里还有 `http://shop.zibohaowen.top` 或 `http://kraeuterhof.cn`，需要继续检查后台站点域名、数据库配置和缓存。

## 十三、支付宝 H5 配置

后台位置：

```text
后台 -> 商城 -> 支付配置 -> 支付宝 H5
```

正式域名应使用：

```text
支付宝异步回调地址：
https://kraeuterhof.cn/index.php?s=shop&c=pay&m=alipay_notify

支付宝同步返回地址：
https://kraeuterhof.cn/index.php?s=shop&c=pay&m=alipay_return
```

支付宝开放平台也要同步检查：

1. AppID 是否为客户正式应用。
2. 产品是否已开通「手机网站支付」。
3. 应用公钥是否与服务器保存的应用私钥匹配。
4. 支付宝公钥是否填写正确。
5. 回调地址、授权域名、应用网关等配置是否使用 `kraeuterhof.cn`。

服务器证书目录：

```text
/www/wwwroot/kraeuterhof/cache/pay/alipay/
```

不要把应用私钥发给不相关人员，也不要放到 `public/`。

## 十四、微信 H5 支付配置

后台位置：

```text
后台 -> 商城 -> 支付配置 -> 微信 H5 支付
```

正式域名建议填写：

```text
微信回调地址：
https://kraeuterhof.cn/index.php?s=shop&c=pay&m=notify

微信返回地址：
https://kraeuterhof.cn/index.php?s=shop&c=pay&m=return_page&out_trade_no={out_trade_no}

H5 场景域名：
https://kraeuterhof.cn/

H5 场景名称：
七叶庄园
```

证书路径参考：

```text
商户私钥路径：
/www/wwwroot/kraeuterhof/cache/pay/wechat/apiclient_key.pem

平台公钥/证书路径：
/www/wwwroot/kraeuterhof/cache/pay/wechat/wechatpay_public.pem
```

商户证书序列号可在服务器执行：

```bash
openssl x509 -in /www/wwwroot/kraeuterhof/cache/pay/wechat/apiclient_cert.pem -noout -serial
```

输出类似：

```text
serial=XXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

后台填写时通常只填等号后面的序列号。

微信商户平台也要检查：

1. H5 支付是否已开通。
2. H5 支付域名是否配置为 `kraeuterhof.cn`。
3. APIv3 密钥是否与后台一致。
4. 商户号、AppID、证书序列号是否匹配。
5. 证书文件是否使用客户商户号对应的正式证书。

## 十五、短信和邮件配置

后台位置：

```text
短信设置：
后台 -> 系统 -> 系统维护 -> 短信设置

邮件设置：
后台 -> 系统 -> 系统维护 -> 邮件设置
```

注册、找回密码会用到验证码：

1. 手机号注册/找回：依赖短信配置。
2. 邮箱注册/找回：依赖 SMTP 邮件配置。

迁移后要分别测试：

```text
注册页发送手机验证码
注册页发送邮箱验证码
找回密码页发送手机验证码
找回密码页发送邮箱验证码
后台邮件服务器测试发送
后台短信测试发送
```

如果前台提示「无邮件 smtp 配置」，但后台已经配置过，通常检查：

1. 数据库是否导入的是正确数据库。
2. 邮件配置是否在当前站点/当前环境保存。
3. 是否清理了 `cache/config/` 相关缓存。
4. 后台「系统缓存」是否已更新。
5. SMTP 账号密码是否可用，QQ 邮箱要使用授权码，不是登录密码。

如果前台验证码发送失败，通常检查：

1. 短信 AccessKey、Secret、签名、模板 ID 是否正确。
2. 模板是否审核通过。
3. 手机号是否触发发送频率限制。
4. 短信服务商余额是否充足。
5. 服务器是否能访问短信服务商接口。
6. 后台测试发送是否成功。

## 十六、登录、注册、找回密码地址

当前项目已使用商城自定义页面，不应再跳到迅睿默认会员页面。

正式访问地址：

```text
登录：
https://kraeuterhof.cn/index.php?s=shop&c=login&m=index

注册：
https://kraeuterhof.cn/index.php?s=shop&c=register&m=index

找回密码：
https://kraeuterhof.cn/index.php?s=shop&c=password&m=find

会员中心：
https://kraeuterhof.cn/index.php?s=member&app=shop&c=center&m=index
```

兼容跳转由文件处理：

```text
/www/wwwroot/kraeuterhof/public/index.php
```

迁移源码时必须包含这个文件的最新版本，否则可能再次出现：

1. 点击注册账号进入迅睿默认注册页。
2. 提示「系统没有设置默认注册的用户组」。
3. 登录后跳转到默认会员中心或旧页面。

## 十七、备案信息

页脚备案信息应按客户提供的信息显示：

```text
主体名称：广州艺妍轩信息科技有限公司
网站备案号：粤ICP备2022040723号-3
域名：kraeuterhof.cn
```

已修改的模板文件：

```text
/www/wwwroot/kraeuterhof/template/pc/default/home/footer.html
```

修改后需要清理模板缓存：

```bash
cd /www/wwwroot/kraeuterhof
find cache/template -type f ! -name ".htaccess" -delete
```

如果客户没有提供公安备案号，不要继续显示测试占位数字，例如 `1212121212`。

## 十八、防伪码和后台功能检查

后台常用入口：

```text
订单管理：
后台文件.php?s=shop&c=order&m=index

会员资料：
后台文件.php?s=shop&c=profile&m=index

支付配置：
后台文件.php?s=shop&c=payconfig&m=index

防伪码导入：
后台文件.php?s=shop&c=antifake&m=index
```

如果后台页面提示模板不存在，说明源码没有上传完整，重点检查：

```text
dayrui/App/Shop/Views/
dayrui/App/Shop/Controllers/
```

## 十九、正式验收地址

迁移完成后按下面顺序检查：

```text
首页：
https://kraeuterhof.cn/

产品详情页：
https://kraeuterhof.cn/index.php?c=show&id=54

登录：
https://kraeuterhof.cn/index.php?s=shop&c=login&m=index

注册：
https://kraeuterhof.cn/index.php?s=shop&c=register&m=index

找回密码：
https://kraeuterhof.cn/index.php?s=shop&c=password&m=find

会员中心：
https://kraeuterhof.cn/index.php?s=member&app=shop&c=center&m=index
```

功能验收清单：

1. 首页能正常打开，不是 404。
2. 页面不再跳转到 `shop.zibohaowen.top` 或 `kraeuterhof.com.cn`。
3. 全站主要链接都是 `https://kraeuterhof.cn`。
4. 备案号显示为 `粤ICP备2022040723号-3`。
5. 登录可以使用用户名、手机号或邮箱。
6. 注册页面能正常显示自定义设计。
7. 注册验证码可以发送。
8. 找回密码验证码可以发送。
9. 会员中心能正常打开。
10. 产品详情页能填写收货信息并提交订单。
11. 订单能在后台看到。
12. 支付宝 H5 能拉起支付。
13. 微信 H5 能拉起支付。
14. 支付成功后订单状态能更新。
15. 防伪查询能正常使用。

## 二十、常见问题排查

### 1. 打开首页 404

优先检查：

```text
宝塔网站目录是否指向项目根目录
运行目录是否为 /public
源码是否只上传了 public
Nginx/Apache 配置是否生效
```

正确入口应是：

```text
/www/wwwroot/kraeuterhof/public/index.php
```

### 2. 首页提示数据库连接失败

优先检查：

```text
config/database.php
数据库是否已导入
数据库用户名和密码是否正确
数据库用户是否有权限访问该库
```

### 3. 从正式域名跳到旧域名

优先检查：

```text
dr_site.domain
dr_site.setting
cache/config/site.php
cache/config/domain_site.php
cache/config/domain_sso.php
```

处理方式：

```bash
cd /www/wwwroot/kraeuterhof
rm -f cache/config/site.php cache/config/domain_site.php cache/config/domain_sso.php cache/config/webpath.php
find cache/template -type f ! -name ".htaccess" -delete
```

然后后台更新缓存。

### 4. 浏览器提示表单不安全

通常是页面仍有 `http://` 表单地址或旧域名。

检查：

```text
后台站点域名
数据库旧域名
模板缓存
支付配置回调地址
```

### 5. 注册提示默认用户组错误

说明又进入了迅睿默认会员注册页。

应使用：

```text
https://kraeuterhof.cn/index.php?s=shop&c=register&m=index
```

并确认 `public/index.php` 中的兼容跳转代码是最新版本。

### 6. 邮件提示无 SMTP 配置

检查：

```text
后台邮件服务器是否存在
是否测试发送成功
数据库是否导入正确
cache/config 是否已清理
后台系统缓存是否已更新
```

### 7. 支付回调失败

检查：

```text
回调地址是否使用 https://kraeuterhof.cn
支付宝/微信商户平台是否配置同一个域名
证书和密钥是否存在
订单金额是否与支付金额一致
服务器是否能被支付平台公网访问
```

支付异步回调地址不能只靠浏览器直接打开测试，正确触发方式是由支付宝或微信支付服务器回调。

## 二十一、交付给客户时的建议

建议最终交付内容包括：

1. 源码压缩包。
2. 数据库 SQL 备份。
3. 本文档。
4. 后台登录地址和账号。
5. 数据库账号。
6. 支付宝、微信支付、短信、邮箱配置说明。
7. 证书和密钥的安全交接说明。

不建议交付或保留在公网目录里的内容：

```text
旧补丁 zip 包
测试日志
数据库明文备份
临时测试 PHP 文件
无用安装文件
```

交付前最后再确认一次：

```text
public/index.php 已关闭开发者模式
域名已替换为 kraeuterhof.cn
缓存已清理
支付回调已改正式域名
备案信息已更新
短信和邮件已测试
支付证书不在 public 目录
```
