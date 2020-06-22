# Simply clone this repository (for example into /var/www/cerebrate)

```
cd /var/www
git clone git@github.com:cerebrate-project/cerebrate.git
```

# Run composer

```
cd /var/www/cerebrate
composer install
```

# Create a database for cerebrate

```
mysql
CREATE DATABASE cerebrate;
CREATE USER 'cerebrate'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD'
GRANT USAGE ON *.* to cerebrate@localhost;
GRANT ALL PRIVILEGES ON cerebrate.* to cerebrate@localhost;
FLUSH ALL PRIVILEGES;
```

# Load the default table structure into the database

mysql -u cerebrate -p cerebrate < /var/www/cerebrate/INSTALL/MYSQL.sql

# create your local configuration and set the db credentials

cp -a /var/www/cerebrate/config/app_local.example.php /var/www/cerebrate/config/app_local.php
vim /var/www/cerebrate/config/app_local.php

# Simple modify the Datasource -> default array's username, password, database fields

# Create an apache config file for cerebrate / ssh key and point the document root to /var/www/cerebrate/webroot/index.php and you're good to go


