#!/bin/bash

# Wait for MongoDB to be ready
sleep 9

# Run database initialization
php /var/www/docker/init.php

# Start PHP-FPM
php-fpm 