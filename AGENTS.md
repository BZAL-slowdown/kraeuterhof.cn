# AGENTS.md

## Project Overview

This repository is the working copy for `kraeuterhof.cn`, based on the XunRuiCMS/Dayrui project in `www/kraeuterhof`.

The site must run from the project root with the web server runtime directory set to `public/`. The actual entry point is:

```text
public/index.php
```

Do not point the web root directly at `public/` and then also configure a `/public` runtime directory in BaoTa; that creates broken paths.

## Important Directories

- `public/`: publicly served entry point, static assets, and upload files.
- `dayrui/`: XunRuiCMS framework and app code.
- `dayrui/App/Shop/`: custom shop, member center, orders, anti-fake import, and H5 payment code.
- `template/`: front-end and member templates.
- `config/`: site configuration. `config/database.php` contains placeholders in Git.
- `docs/`: deployment notes, SQL supplement, and handoff documents.
- `cache/`: runtime cache and writable data. Payment certificate directories are kept, but certificate files are not committed.

## Sensitive Data Rules

Never commit real secrets, including:

- database username/password
- WeChat Pay AppID, mchid, APIv3 key, merchant serial number, or certificates
- Alipay AppID, seller/account IDs, private keys, or public key files
- SMS AccessKey/Secret
- admin passwords
- full SQL database dumps from production or test servers

Before committing, run a quick scan such as:

```bash
rg -n -i "password|api_v3|accesskey|secret|private_key|mchid|merchant_serial|app_id|kraeuterhof_com_|208804|202100"
```

Expected matches should be placeholders, code variable names, or documentation only.

## Deployment Notes

Target production domain: `kraeuterhof.cn`.

Recommended BaoTa settings:

- site path: `/www/wwwroot/kraeuterhof`
- runtime directory: `/public`
- PHP: 8.2
- MySQL: 5.7 or 8.0

Database migration guidance:

- Do not overwrite the customer's production database without explicit confirmation.
- If the production database does not yet have shop tables, apply `docs/shop_tables_dr_prefix.sql`.
- Verify the existence of `dr_shop_order`, `dr_shop_address`, and `dr_shop_profile`.

Payment certificate paths expected by the code:

```text
cache/pay/wechat/apiclient_key.pem
cache/pay/wechat/wechatpay_public.pem
cache/pay/alipay/app_private_key.pem
cache/pay/alipay/app_public_key.pem
cache/pay/alipay/alipay_public_key.pem
```

These files must be uploaded securely on the server and must remain outside Git.

## Development Workflow

1. Start from this clean repository, not from the historical patch folders in `www/`.
2. Keep changes scoped to the relevant app/template/config files.
3. After payment or member-flow edits, check:
   - product detail order flow
   - login/register redirect
   - member center
   - order list
   - payment return and notify handlers
   - admin order/profile/payconfig/anti-fake pages
4. Update `docs/` when deployment steps or required server configuration changes.

## Git Hygiene

- Commit small, meaningful checkpoints.
- Do not commit `cache/pay` certificate files, logs, SQL dumps, or local archives.
- If real credentials were accidentally written into a tracked file, stop and rotate the credential before pushing.
