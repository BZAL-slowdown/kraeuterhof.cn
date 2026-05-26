# AGENTS.md

## 项目概览

本仓库是 `kraeuterhof.cn` 的干净工作副本，基线来自原目录 `www/kraeuterhof`，项目类型为迅睿 CMS / DayruiCMS，并包含自定义商城、会员中心、H5 支付和防伪码导入相关代码。

站点必须以项目根目录作为网站目录，并把 Web 服务的运行目录设置为：

```text
public/
```

实际入口文件是：

```text
public/index.php
```

不要把网站目录直接指向 `public/` 后又在宝塔里设置运行目录 `/public`，否则路径会错乱。

## 重要目录

- `public/`：公网入口、静态资源、上传文件。
- `dayrui/`：迅睿 CMS 框架和应用代码。
- `dayrui/App/Shop/`：商城、会员中心、订单、支付配置、防伪码导入等自定义功能。
- `template/`：前台、会员中心和移动端模板。
- `config/`：站点配置。仓库中的 `config/database.php` 只保留占位值。
- `docs/`：部署文档、交接说明、补充 SQL 和上线检查清单。
- `cache/`：运行缓存和可写数据目录。仓库保留必要目录和占位文件，但不提交支付证书、日志、会话、模板缓存等运行文件。

## 敏感信息规则

绝对不要提交真实密钥或账号信息，包括但不限于：

- 数据库用户名、数据库密码。
- 微信支付 AppID、商户号 `mchid`、APIv3 密钥、商户证书序列号、证书文件。
- 支付宝 AppID、账号 ID、卖家 ID、应用私钥、公钥证书文件。
- 短信服务 AccessKey / Secret。
- 后台管理员账号密码。
- 生产站或测试站完整数据库导出 SQL。

提交前建议执行一次敏感信息扫描：

```bash
rg -n -i "password|api_v3|accesskey|secret|private_key|mchid|merchant_serial|app_id|kraeuterhof_com_|208804|202100"
```

允许出现的结果应仅限于占位值、变量名、代码字段名或文档说明。

## 部署要点

正式目标域名：

```text
kraeuterhof.cn
```

宝塔推荐配置：

- 网站目录：`/www/wwwroot/kraeuterhof`
- 运行目录：`/public`
- PHP：建议 8.2
- MySQL：5.7 或 8.0

数据库迁移注意：

- 未经客户明确确认，不要覆盖正式数据库。
- 如果正式库没有商城表，只执行 `docs/shop_tables_dr_prefix.sql` 进行补表。
- 执行后确认存在这些表：`dr_shop_order`、`dr_shop_address`、`dr_shop_profile`。

支付证书文件应在服务器上安全上传到以下路径，不要提交到 Git：

```text
cache/pay/wechat/apiclient_key.pem
cache/pay/wechat/wechatpay_public.pem
cache/pay/alipay/app_private_key.pem
cache/pay/alipay/app_public_key.pem
cache/pay/alipay/alipay_public_key.pem
```

## 开发流程

1. 后续开发以本仓库为准，不要直接在 `www/` 下的历史补丁包或旧版本目录里改。
2. 修改尽量限定在相关的应用、模板、配置或文档文件中。
3. 涉及支付、会员或下单流程时，至少回归检查：
   - 产品详情页下单流程。
   - 登录、注册、找回密码跳转。
   - 自定义会员中心。
   - 我的订单列表。
   - 微信/支付宝支付返回和异步回调处理。
   - 后台订单、会员资料、支付配置、防伪码导入页面。
4. 如果部署步骤、服务器配置或证书路径发生变化，同步更新 `docs/` 中的相关文档。

## Git 规范

- 小步提交，提交信息要能说明这次变更的目的。
- 不提交 `cache/pay` 证书文件、日志、会话、模板缓存、数据库整库 SQL、历史 zip 包。
- 如果真实密钥误入已跟踪文件，先停止推送，并提醒需要轮换该密钥。
- 推送前确认工作区干净，并确认远端为：

```text
https://github.com/BZAL-slowdown/kraeuterhof.cn.git
```
