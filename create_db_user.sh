#!/bin/bash
# Create application database user with password auth

mysql -u root << 'SQL'
-- Create new user for web application with password authentication
CREATE USER IF NOT EXISTS 'viabix'@'localhost' IDENTIFIED WITH caching_sha2_password BY '59380204Mm';

-- Grant all permissions on viabix_db
GRANT ALL PRIVILEGES ON viabix_db.* TO 'viabix'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Verify user exists
SELECT user, host, plugin FROM mysql.user WHERE user='viabix';

echo "✅ Database user 'viabix' created with password authentication!"
SQL
