# Actual data and volumes

The actual database will be located in `./run/database` exposed with the
following volume `- ./run/database:/var/lib/mysql`

Application logs (CakePHP / Cerebrate) will be stored in `./run/logs`,
volume `- ./run/logs:/var/www/html/logs`

You're free to change those parameters if you're using Swarm, Kubernetes or
your favorite config management tool to deploy this stack

# Building yourself

You can create the following Makefile in basedir of this repository
and issue `make image`

```
COMPOSER_VERSION?=2.1.5
PHP_VERSION?=8.2
DEBIAN_RELEASE?=buster
IMAGE_NAME?=cerebrate:latest

image:
	docker build -t $(IMAGE_NAME) \
		-f docker/Dockerfile \
		--build-arg COMPOSER_VERSION=$(COMPOSER_VERSION) \
		--build-arg PHP_VERSION=$(PHP_VERSION) \
		--build-arg DEBIAN_RELEASE=$(DEBIAN_RELEASE) \
		.
```
