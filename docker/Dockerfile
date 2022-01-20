ARG COMPOSER_VERSION
ARG PHP_VERSION
ARG DEBIAN_RELEASE

FROM php:${PHP_VERSION}-apache-${DEBIAN_RELEASE}

# we need some extra libs to be installed in the runtime
RUN apt-get update && \
	apt-get install -y --no-install-recommends curl git zip unzip && \
	apt-get install -y --no-install-recommends libicu-dev libxml2-dev && \
	docker-php-ext-install intl pdo pdo_mysql mysqli simplexml && \
	apt-get remove -y --purge libicu-dev libxml2-dev && \
	apt-get clean && \
	rm -rf /var/lib/apt/lists/*

COPY composer.json composer.json

# install composer as root
ARG COMPOSER_VERSION
RUN curl -sL https://getcomposer.org/installer | \
	php -- --install-dir=/usr/bin/ --filename=composer --version=${COMPOSER_VERSION}

# switch back to unprivileged user for composer install
USER www-data

RUN composer install \
	--no-interaction \
	--no-plugins \
	--no-scripts \
	--prefer-dist

# web server configuration
USER root

# allow .htaccess overrides and push them
RUN a2enmod rewrite
RUN sed -i -r '/DocumentRoot/a \\t<Directory /var/www/html/>\n\t\tAllowOverride all\n\t</Directory>' /etc/apache2/sites-available/000-default.conf
COPY --chown=www-data docker/etc/DocumentRoot.htaccess /var/www/html/.htaccess
COPY --chown=www-data docker/etc/webroot.htaccess /var/www/html/webroot/.htaccess

# passing environment variables through apache
RUN a2enmod env
RUN echo 'PassEnv CEREBRATE_DB_HOST'          >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'PassEnv CEREBRATE_DB_NAME'          >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'PassEnv CEREBRATE_DB_PASSWORD'      >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'PassEnv CEREBRATE_DB_PORT'          >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'PassEnv CEREBRATE_DB_SCHEMA'        >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'PassEnv CEREBRATE_DB_USERNAME'      >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'PassEnv CEREBRATE_EMAIL_HOST'       >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'PassEnv CEREBRATE_EMAIL_PASSWORD'   >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'PassEnv CEREBRATE_EMAIL_PORT'       >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'PassEnv CEREBRATE_EMAIL_TLS'        >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'PassEnv CEREBRATE_EMAIL_USERNAME'   >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'PassEnv CEREBRATE_SECURITY_SALT'    >> /etc/apache2/conf-enabled/environment.conf

# entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh

# copy actual codebase
COPY --chown=www-data . /var/www/html

# last checks with unprivileged user
USER www-data

# CakePHP seems to not handle very well externally installed components
# this will chown/chmod/symlink all in place for its own good
RUN composer install --no-interaction

# app config override making use of environment variables
COPY --chown=www-data docker/etc/app_local.php /var/www/html/config/app_local.php
# version 1.0 addition requires a config/config.json file
# can still be overriden by a docker volume
RUN cp -a /var/www/html/config/config.example.json /var/www/html/config/config.json

# also can be overridin by a docker volume
RUN mkdir -p /var/www/html/logs

ENTRYPOINT [ "/entrypoint.sh" ]
