#!/bin/bash
mysql -u root -e "SELECT user, authentication_string, plugin FROM mysql.user WHERE user IN ('root', 'doadmin');"
