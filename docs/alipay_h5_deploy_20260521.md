# 支付宝 H5 支付配置说明（2026-05-21）

本项目已接入支付宝手机网站支付：

- 接口：`alipay.trade.wap.pay`
- 产品码：`QUICK_WAP_WAY`
- 网关：`https://openapi.alipay.com/gateway.do`
- 异步回调：`https://kraeuterhof.cn/index.php?s=shop&c=pay&m=alipay_notify`
- 同步返回：`https://kraeuterhof.cn/index.php?s=shop&c=pay&m=alipay_return&out_trade_no={out_trade_no}`

关键文件：

- 支付配置：`dayrui/App/Shop/Config/Pay.php`
- 支付宝库：`dayrui/App/Shop/Libraries/AlipayWap.php`
- 支付控制器：`dayrui/App/Shop/Controllers/Pay.php`
- 后台配置页：`dayrui/App/Shop/Views/payconfig_index.html`
- 支付宝密钥目录：`cache/pay/alipay/`

部署后请确认：

1. 宝塔站点目录为 `/www/wwwroot/kraeuterhof`，运行目录为 `/public`。
2. PHP 启用 `openssl` 扩展，否则 RSA2 签名和验签无法工作。
3. `cache/pay/alipay/app_private_key.pem`、`cache/pay/alipay/app_public_key.pem`、`cache/pay/alipay/alipay_public_key.pem` 存在且不可被公网直接下载。
4. 支付宝开放平台应用已开通“手机网站支付”，并且应用公钥已上传到支付宝平台。
5. 上线后在后台“商城 -> 支付配置”检查支付宝 H5 为启用状态，再用小额订单做真实联调。

安全提醒：压缩包内包含支付私钥文件，上传完成后请妥善保管本地压缩包，不要转发给无关人员。
