#!/usr/bin/env bash

docker run -it --rm -u ${UID} -v `pwd`:/var/www/html -v `pwd`/.composer:/.composer -w /var/www/html kilik/php:7.4-buster-dev vendor/bin/simple-phpunit
