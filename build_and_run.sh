#!/bin/bash

cd $(dirname "$0")
php tools/build.php
php -S 0.0.0.0:8000 -t public