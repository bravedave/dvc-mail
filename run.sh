#!/bin/bash

php=php
# if [[ -x /usr/bin/php8 ]]; then php=php8; fi

# this script is for linux, perhaps MAC ? environments

# this script will make the environment available on
# port 8080 - you can make it availabled o port 80
# running as root

current_dir=`pwd`
cd www
$php -S localhost:1080 _dvc.php
cd $current_dir
