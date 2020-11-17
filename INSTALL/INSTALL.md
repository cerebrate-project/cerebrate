## Requirements

An Ubuntu server (18.04/20.04 should both work fine) - though other linux installations should work too.
- apache2, mysql/mariadb, sqlite need to be installed and running
- php extensions for intl, mysql, sqlite3, mbstring, xml need to be installed and running
- composer


## Cerebrate installation instructions

It should be sufficient to issue the following command to install the dependencies:
```bash
sudo apt install apache2 mariadb-server git composer php-intl php-mbstring php-dom php-xml unzip php-ldap php-sqlite3 sqlite libapache2-mod-php php-mysql
```

Clone this repository (for example into /var/www/cerebrate)

```bash
sudo mkdir /var/www/cerebrate
sudo chown www-data:www-data /var/www/cerebrate
sudo -u www-data git clone https://github.com/cerebrate-project/cerebrate.git /var/www/cerebrate
```

Run composer

```bash
cd /var/www/cerebrate
sudo -u www-data composer install
```

Create a database for cerebrate

From SQL shell:
```mysql
mysql
CREATE DATABASE cerebrate;
CREATE USER 'cerebrate'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';
GRANT USAGE ON *.* to cerebrate@localhost;
GRANT ALL PRIVILEGES ON cerebrate.* to cerebrate@localhost;
FLUSH PRIVILEGES;
```

Or from Bash:
```bash
sudo mysql -e "CREATE DATABASE cerebrate;"
sudo mysql -e "CREATE USER 'cerebrate'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';"
sudo mysql -e "GRANT USAGE ON *.* to cerebrate@localhost;"
sudo mysql -e "GRANT ALL PRIVILEGES ON cerebrate.* to cerebrate@localhost;"
sudo mysql -e "FLUSH PRIVILEGES;"
```

Load the default table structure into the database

```bash
sudo mysql -u cerebrate -p cerebrate < /var/www/cerebrate/INSTALL/mysql.sql
```

create your local configuration and set the db credentials

```bash
sudo -u www-data cp -a /var/www/cerebrate/config/app_local.example.php /var/www/cerebrate/config/app_local.php
sudo -u www-data vim /var/www/cerebrate/config/app_local.php
```

mod_rewrite needs to be enabled:

```bash
sudo a2enmod rewrite
```

Simply modify the Datasource -> default array's username, password, database fields
This would be, when following the steps above:

```php
    'Datasources' => [
        'default' => [
            'host' => 'localhost',
            'username' => 'cerebrate',
            'password' => 'YOUR_PASSWORD',
            'database' => 'cerebrate',
```
Create an apache config file for cerebrate / ssh key and point the document root to /var/www/cerebrate/webroot and you're good to go

For development installs the following can be done:

```bash
# This configuration is purely meant for local installations for development / testing
# Using HTTP on an unhardened apache is by no means meant to be used in any production environment
sudo cp /var/www/cerebrate/INSTALL/cerebrate_dev.conf /etc/apache2/sites-available/
sudo ln -s /etc/apache2/sites-available/cerebrate_dev.conf /etc/apache2/sites-enabled/
sudo service apache2 restart
```

Now you can point your browser to: http://localhost:8000

To log in use the default credentials below:

- Username: admin
- Password: Password1234
