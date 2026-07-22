# ITS Center B2B

Customer cabinet for `b2b.its-center.ru`. The application reads catalog and
customer data from the shared ITS Center database and exchanges current stock,
prices, orders, statuses, and marking data with 1C over HTTP APIs.

## Requirements

- PHP 8.2 with PDO MySQL, cURL, DOM, GD, mbstring, XML, and ZIP
- Composer 2
- A database configuration file outside Git
- Outgoing access to the configured 1C HTTP services

## Local setup

1. Run `composer install --no-dev --optimize-autoloader`.
2. Set `APP_ENV=local` and `APP_URL`.
3. Point `DB_CONFIG_FILE` to the existing ITS Center `config_db.php`.
4. Configure the ignored `config/api_goods.php`, `config/api_orders.php`, and
   `config/params.php` files.
5. Use `php bin/check_database.php` to verify database access.

See `.env.example` for environment variable names. The project does not load
`.env` files itself; configure variables in Apache/PHP-FPM or the deployment
environment.

## 1C callback

`POST /api/save-marks` requires the `X-1C-Token` header. Its value must match
`API_1C_CALLBACK_TOKEN`. Requests are size-limited, processed in a transaction,
and do not log raw order or marking payloads.

## Repository policy

Secrets, dependencies, generated exports, runtime logs, product images, and the
legacy store administration are intentionally excluded from Git. The legacy
files may remain in an existing local installation but are not part of the B2B
deployment artifact.
