# 展示官网 + H5支付 + 会员系统实施说明

## 当前完成范围

- `dayrui/App/Shop`：新增独立商城订单应用，不修改 CMS 核心。
- `Config/Install.sql`：订单表 `dr_shop_order`。
- `Controllers/Home.php`：产品详情页提交下单。
- `Controllers/Pay.php`：微信 H5 支付发起、回调、支付结果页。
- `Controllers/Member/Order.php`：会员订单列表。
- `Controllers/Admin/Order.php`：后台订单搜索、测试标记已支付、标记发货。
- `Libraries/WechatH5.php`：微信支付 API v3 H5 请求签名、回调签名校验、回调解密。
- `template/pc/default/home/achanpin.html`：产品详情页“立即购买”已接入下单表单。
- `template/pc/default/member/shop/order_index.html`：会员中心订单列表模板。

## 当前可测试流程

1. 会员注册和登录。
2. 打开产品详情页，填写收货人、手机号、地址后提交订单。
3. 系统生成 `dr_shop_order` 记录，状态为未支付。
4. 会员中心“我的订单”可以看到订单。
5. 后台“商城订单”可以搜索订单。
6. 后台可以临时“测试标记已支付”，再“标记发货”，用于无真实微信资料时验证订单流转。

## 真实支付联调前必须配置

在 `dayrui/App/Shop/Config/Pay.php` 中配置：

- `enabled => true`
- `appid`
- `mchid`
- `merchant_serial_no`
- 商户私钥 `apiclient_key.pem`
- 微信支付平台公钥或平台证书
- `api_v3_key`
- 微信商户平台 H5 支付授权域名
- 回调地址：`https://客户域名/index.php?s=shop&c=pay&m=notify`

证书文件建议放在 `cache/pay/wechat/` 这类非公开目录，不要放到 `public/` 下。

## 正式上线前注意

1. 先备份正式站源码和数据库。
2. 不要用测试库覆盖客户正式库。
3. 不要重置客户微信支付 APIv3 密钥。
4. 不要删除客户原有 H5 支付域名。
5. 生产环境建议将 `dayrui/App/Shop/Config/Shop.php` 中的 `allow_admin_mark_paid` 改为 `false`。
6. 删除或限制访问：
   - `public/install.php`
   - `public/test.php`

## 还需客户确认

- 正式支付域名。
- 产品实际价格字段或是否统一使用默认测试价。
- 是否需要物流公司、快递单号、退款、发票等扩展功能。
