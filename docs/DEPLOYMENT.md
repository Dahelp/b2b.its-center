# B2B production deployment

## Server requirements

- Linux hosting with SSH and Git access
- PHP 8.2 CLI and web runtime
- Composer 2
- Apache `mod_rewrite` and permission to use `.htaccess`
- PHP extensions listed in `README.md`
- outbound HTTP access to the 1C API
- access to the shared ITS Center database

The web document root should point to the repository root. The root `.htaccess`
forwards requests into `public/`. If the hosting supports a custom document root,
pointing it directly to `public/` is preferable and the root forwarding rule is
not needed.

## Secrets and private configuration

Do not copy secrets into Git. Create `$APP_DIR/.env` from `.env.example`, fill
these values, and set `chmod 600 $APP_DIR/.env`:

- `APP_ENV=production`
- `APP_URL=https://b2b.its-center.ru`
- `DB_CONFIG_FILE` pointing to the existing ITS Center DB configuration
- `API_1C_HOST`, `API_1C_BASE`, `API_1C_USER`, `API_1C_PASSWORD`
- `API_1C_CALLBACK_TOKEN`
- optional cache TTL values from `.env.example`

The ignored files `config/params.php`, `config/api_goods.php`, and
`config/api_orders.php` must also exist on the server. Keep them outside the
deployment overwrite process or create server-local symlinks to protected files.

The old 1C credential previously committed to the repository must be rotated
before production deployment because removing it from the current tree does not
remove it from Git history.

## First deployment

1. Back up the current B2B files and record the active PHP version.
2. Clone `git@github.com:Dahelp/b2b.its-center.git` into a new directory.
3. Restore or symlink the private config files.
4. Create the protected `.env` file and run `chmod 600 .env`.
5. Run `composer install --no-dev --optimize-autoloader`.
6. Create the runtime directories listed in `bin/deploy.sh`.
7. Run `php bin/preflight.php`; deployment is allowed only after `PREFLIGHT_OK`.
8. Switch the domain document root to the new directory and perform acceptance tests.

## Routine deployment

Run from SSH:

```bash
APP_DIR=/absolute/path/to/b2b bash bin/deploy.sh
```

The script refuses a dirty working tree, accepts only a fast-forward update,
installs locked production dependencies, fixes runtime directory permissions,
and runs the production preflight check.

## Rollback

Keep the previous application directory unchanged until acceptance testing is
complete. Rollback should switch the domain document root back to that directory.
The shared database has no B2B-specific schema migration in this release, so the
application rollback does not require a database rollback.
