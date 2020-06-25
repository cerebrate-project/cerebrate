## Requirements

An Ubuntu server (18.04/20.04 should both work fine) - though other Linux installations should work too.
- sqlite, apache2, mysql/mariadb need to be installed and running
- php extensions for intl, mysql, sqlite3, mbstring, xml need to be installed and running
- composer


## Cerebrate installation instructions

Install dependencies
```
sudo apt install composer apache2 libapache2-mod-php php php-intl php-mysql php-mbstring php-sqlite3 php-xml unzip mariadb-server
```

Simply clone this repository (for example into /var/www/cerebrate)

```
sudo mkdir /var/www/cerebrate
sudo chown www-data:www-data /var/www/cerebrate
sudo -u www-data git clone https://github.com/cerebrate-project/cerebrate.git /var/www/cerebrate
```

Run composer

```
cd /var/www/cerebrate
sudo -u www-data composer install
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

```
mysql
sudo mysql -e "CREATE DATABASE cerebrate;"
sudo mysql -e "CREATE USER 'cerebrate'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';"
sudo mysql -e "GRANT USAGE ON *.* to cerebrate@localhost;"
sudo mysql -e "GRANT ALL PRIVILEGES ON cerebrate.* to cerebrate@localhost;"
sudo mysql -e "FLUSH PRIVILEGES;"
```

Load the default table structure into the database

```
sudo mysql -u cerebrate -p cerebrate < /var/www/cerebrate/INSTALL/mysql.sql
```

create your local configuration and set the db credentials

```
sudo -u www-data cp -a /var/www/cerebrate/config/app_local.example.php /var/www/cerebrate/config/app_local.php
sudo -u www-data vim /var/www/cerebrate/config/app_local.php
```

Modify the Datasource -> default array's username, password, database fields

Create an apache config file for cerebrate / ssh key and point the document root to /var/www/cerebrate/webroot/index.php and you're good to go.

For development installs the following can be done:

```
# This configuration is purely meant for local installations for development / testing
# Using HTTP on an unhardened apache is by no means meant to be used in any production environment
sudo cp /var/www/cerebrate/INSTALL/cerebrate_dev.conf /etc/apache2/sites-available/
sudo ln -s /etc/apache2/sites-available/cerebrate_dev.conf /etc/apache2/sites-enabled/
sudo service apache2 restart
```

Now you can point your browser to: http://localhost:8000

To log in use the default credentials below:

Username: admin
Password: Password1234
