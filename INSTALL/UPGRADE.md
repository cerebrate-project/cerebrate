# Upgrade Cerebrate

To upgrade a local cerebrate installation, simply pull the new code from the remote `main` branch:

```bash
sudo -u www-data git -C /var/www/cerebrate/ pull origin main
```

If you need to use a proxy, you can pass them to the command like this:

```bash
https_proxy=http://proxy.local:8080 sudo -Eu www-data git -C /var/www/cerebrate/ pull origin main
```

Then update the PHP dependencies. Use `composer update`, not `composer install`:
`composer.lock` is not shipped with Cerebrate, so `install` would keep the
versions from your existing local lock file.

```bash
sudo -H -u www-data composer --working-dir=/var/www/cerebrate update --no-interaction
```

To upgrade the database, login to the webinterface as administrator and call
http://cerebrate.local:8000/instance/migrationIndex
Also available from the menu in the interface as "Database migration".
Run all available upgrades.

## Optional features: outbound email & reminders

Cerebrate includes an optional outbound email subsystem and a PGP
key-expiry reminder sweep. Both are **disabled by default** and require
explicit opt-in — an SMTP transport, a `from` address, and, for the
sweep, a cron entry — so an upgrade never starts sending mail on its
own. After running the database migrations above, see the "Email and
reminders" section of [`INSTALL.md`](INSTALL.md) and the operator guides
under `docs/admin/` ([`email.md`](../docs/admin/email.md),
[`reminder-sweep.md`](../docs/admin/reminder-sweep.md)) to enable them.
