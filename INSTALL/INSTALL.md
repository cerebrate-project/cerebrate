## Requirements

An Ubuntu server (18.04/20.04 should both work fine) - though other linux installations should work too.
- apache2, mysql/mariadb, sqlite need to be installed and running
- php extensions for intl, mysql, sqlite need to be installed and running
  
sudo apt install apache2 mariadb-server git composer php-intl php-mbstring php-dom php-ldap php-sqlite3 sqlite libapache2-mod-php php-mysql


## Cerebrate installation instructions

Simply clone this repository (for example into /var/www/cerebrate)

```
cd /var/www
sudo git clone git@github.com:cerebrate-project/cerebrate.git
```

Run composer

```
cd /var/www/cerebrate
sudo composer install
```

Create a database for cerebrate

```
mysql
CREATE DATABASE cerebrate;
CREATE USER 'cerebrate'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';
GRANT USAGE ON *.* to cerebrate@localhost;
GRANT ALL PRIVILEGES ON cerebrate.* to cerebrate@localhost;
FLUSH PRIVILEGES;
```

Load the default table structure into the database

```
mysql -u cerebrate -pYOUR_PASSWORD cerebrate < /var/www/cerebrate/INSTALL/mysql.sql
```

create your local configuration and set the db credentials

```
cp -a /var/www/cerebrate/config/app_local.example.php /var/www/cerebrate/config/app_local.php
vim /var/www/cerebrate/config/app_local.php
```

Simply modify the Datasource -> default array's username, password, database fields
This would be, when following the steps above:

```
    'Datasources' => [
        'default' => [
            'host' => 'localhost',
            'username' => 'cerebrate',
            'password' => 'YOUR_PASSWORD',
            'database' => 'cerebrate',
```
Create an apache config file for cerebrate / ssh key and point the document root to /var/www/cerebrate/webroot/index.php and you're good to go

To log in use the default credentials below:

username: admin
Password: Password1234

