#!/bin/bash
set -e

echo "=== Starting KOMS Container ==="

APACHE_PORT="${PORT:-80}"
echo "Configuring Apache to listen on port ${APACHE_PORT}..."
sed -i "s/Listen [0-9]*/Listen ${APACHE_PORT}/g" /etc/apache2/ports.conf 2>/dev/null || true
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${APACHE_PORT}>/g" /etc/apache2/sites-available/000-default.conf 2>/dev/null || true

DB_NAME="${DB_NAME:-koms}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_PORT="${DB_PORT:-3306}"

EXT_DB_OK=0

cleanup_legacy_demo_accounts() {
    local MYSQL_ARGS=("$@")

    # Ensure cleanup failures do not terminate container startup
    set +e

    local HAS_USERS
    HAS_USERS=$("${MYSQL_ARGS[@]}" -N -s -e "SELECT count(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME' AND table_name = 'users';" 2>/dev/null || echo 0)
    if [ "$HAS_USERS" != "1" ]; then
        echo "Table 'users' does not exist in $DB_NAME yet; skipping demo cleanup."
        set -e
        return 0
    fi

    "${MYSQL_ARGS[@]}" -N -s -e "CREATE TABLE IF NOT EXISTS koms_system_flags (flag_name VARCHAR(100) PRIMARY KEY, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);" "$DB_NAME" 2>/dev/null || true

    local ALREADY_DONE
    ALREADY_DONE=$("${MYSQL_ARGS[@]}" -N -s -e "SELECT COUNT(*) FROM koms_system_flags WHERE flag_name='legacy_demo_accounts_removed_v1';" "$DB_NAME" 2>/dev/null || echo 0)

    if [ "$ALREADY_DONE" = "1" ]; then
        echo "Legacy demo-account cleanup already completed."
        set -e
        return 0
    fi

    echo "Removing legacy KOMS demo accounts and their demo records..."
    "${MYSQL_ARGS[@]}" "$DB_NAME" 2>/dev/null <<'SQL' || true
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM audit_logs
WHERE user_id IN (SELECT id FROM users WHERE email IN ('admin@gmail.com','master@gmail.com','senior@gmail.com','student@gmail.com','minor@gmail.com','ryu@gmail.com','ken@gmail.com','chunli@gmail.com','rubeshwaran.t@koms.local'));

DELETE FROM tournament_registrations
WHERE student_id IN (SELECT id FROM users WHERE email IN ('student@gmail.com','minor@gmail.com','ryu@gmail.com','ken@gmail.com','chunli@gmail.com'));

DELETE FROM tournament_brackets
WHERE tournament_id IN (SELECT id FROM tournaments WHERE name='All-Valley Shorin Ryu Championship');

DELETE FROM tournaments
WHERE name='All-Valley Shorin Ryu Championship';

DELETE FROM grading_history
WHERE student_id IN (SELECT id FROM users WHERE email IN ('student@gmail.com','minor@gmail.com','ryu@gmail.com','ken@gmail.com','chunli@gmail.com'))
   OR instructor_id IN (SELECT id FROM users WHERE email IN ('admin@gmail.com','master@gmail.com','senior@gmail.com'));

DELETE FROM achievements
WHERE student_id IN (SELECT id FROM users WHERE email IN ('student@gmail.com','minor@gmail.com','ryu@gmail.com','ken@gmail.com','chunli@gmail.com'))
   OR added_by IN (SELECT id FROM users WHERE email IN ('admin@gmail.com','master@gmail.com','senior@gmail.com'));

DELETE FROM attendance_entries
WHERE student_id IN (SELECT id FROM users WHERE email IN ('student@gmail.com','minor@gmail.com','ryu@gmail.com','ken@gmail.com','chunli@gmail.com'))
   OR marked_by IN (SELECT id FROM users WHERE email IN ('admin@gmail.com','master@gmail.com','senior@gmail.com'));

DELETE FROM attendance_sessions
WHERE instructor_id IN (SELECT id FROM users WHERE email IN ('admin@gmail.com','master@gmail.com','senior@gmail.com'))
   OR dojo_id IN (SELECT id FROM dojos WHERE name IN ('Mass Dragon Dojo','Okinawa Shorin Ryu Central'));

DELETE FROM payments
WHERE student_id IN (SELECT id FROM users WHERE email IN ('student@gmail.com','minor@gmail.com','ryu@gmail.com','ken@gmail.com','chunli@gmail.com'));

DELETE FROM fee_records
WHERE student_id IN (SELECT id FROM users WHERE email IN ('student@gmail.com','minor@gmail.com','ryu@gmail.com','ken@gmail.com','chunli@gmail.com'))
   OR fee_structure_id IN (SELECT id FROM fee_structures WHERE dojo_id IN (SELECT id FROM dojos WHERE name IN ('Mass Dragon Dojo','Okinawa Shorin Ryu Central')));

DELETE FROM fee_structures
WHERE dojo_id IN (SELECT id FROM dojos WHERE name IN ('Mass Dragon Dojo','Okinawa Shorin Ryu Central'));

DELETE FROM announcements
WHERE created_by IN (SELECT id FROM users WHERE email IN ('admin@gmail.com','master@gmail.com','senior@gmail.com'))
   OR dojo_id IN (SELECT id FROM dojos WHERE name IN ('Mass Dragon Dojo','Okinawa Shorin Ryu Central'));

DELETE FROM dojo_memberships
WHERE student_id IN (SELECT id FROM users WHERE email IN ('student@gmail.com','minor@gmail.com','ryu@gmail.com','ken@gmail.com','chunli@gmail.com'))
   OR dojo_id IN (SELECT id FROM dojos WHERE name IN ('Mass Dragon Dojo','Okinawa Shorin Ryu Central'));

DELETE FROM student_profiles
WHERE user_id IN (SELECT id FROM users WHERE email IN ('student@gmail.com','minor@gmail.com','ryu@gmail.com','ken@gmail.com','chunli@gmail.com'));

DELETE FROM dojos
WHERE name IN ('Mass Dragon Dojo','Okinawa Shorin Ryu Central');

DELETE FROM users
WHERE email IN ('admin@gmail.com','master@gmail.com','senior@gmail.com','student@gmail.com','minor@gmail.com','ryu@gmail.com','ken@gmail.com','chunli@gmail.com','rubeshwaran.t@koms.local')
   OR member_id='master.rubeshwaran2004.koms';

SET FOREIGN_KEY_CHECKS = 1;

INSERT IGNORE INTO koms_system_flags(flag_name)
VALUES ('legacy_demo_accounts_removed_v1');
SQL

    set -e
    echo "Legacy demo-account cleanup completed."
}

if [ -n "$DATABASE_URL" ] || ([ -n "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ] && [ "$DB_HOST" != "127.0.0.1" ]); then
    echo "Checking external database connection to $DB_HOST:$DB_PORT..."
    MYSQL_SSL_OPTS=(--ssl=1 --ssl-verify-server-cert=0 --connect-timeout=4)
    MYSQL_BASE=(mysql "${MYSQL_SSL_OPTS[@]}" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD")
    MYSQL_ADMIN=(mysqladmin "${MYSQL_SSL_OPTS[@]}" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD")

    if "${MYSQL_ADMIN[@]}" ping --silent 2>/dev/null; then
        echo "External database is reachable and authenticated!"
        EXT_DB_OK=1

        HAS_CORE_TABLES=$("${MYSQL_BASE[@]}" -N -s -e "SELECT count(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME' AND table_name = 'users';" 2>/dev/null || echo "0")

        if [ "$HAS_CORE_TABLES" = "0" ] || [ -z "$HAS_CORE_TABLES" ]; then
            if [ -f "/var/www/html/database/schema.sql" ]; then
                echo "Importing initial database schema into external database ($DB_NAME)..."
                "${MYSQL_BASE[@]}" "$DB_NAME" < /var/www/html/database/schema.sql || true
                echo "External database schema import complete."
            fi
        else
            echo "External database $DB_NAME already contains core application tables."
        fi

        cleanup_legacy_demo_accounts "${MYSQL_BASE[@]}"

        if [ -f "/var/www/html/database/seed_students.php" ]; then
            echo "Seeding/verifying 13 KOMS student accounts in external database..."
            php /var/www/html/database/seed_students.php 2>/dev/null || true
        fi
    else
        echo "External database ($DB_HOST) was not reachable or credentials were not provided."
        echo "Activating container internal MariaDB fallback for high availability..."
    fi
fi

if [ "$EXT_DB_OK" = "0" ]; then
    echo "Configuring internal MariaDB service inside container..."
    mkdir -p /run/mysqld /var/run/mysqld /var/lib/mysql /var/log/mysql /etc/mysql/conf.d
    chown -R mysql:mysql /run/mysqld /var/run/mysqld /var/lib/mysql /var/log/mysql
    chmod 777 /run/mysqld /var/run/mysqld

    cat << 'EOF' > /etc/mysql/conf.d/render.cnf
[mysqld]
performance_schema = OFF
innodb_buffer_pool_size = 32M
innodb_log_buffer_size = 1M
innodb_stats_on_metadata = OFF
key_buffer_size = 8M
max_connections = 50
table_open_cache = 100
query_cache_size = 0
bind-address = 0.0.0.0
skip-grant-tables
EOF

    if [ ! -d "/var/lib/mysql/mysql" ]; then
        echo "Initializing MariaDB system tables..."
        mariadb-install-db --user=mysql --datadir=/var/lib/mysql --skip-test-db >/dev/null 2>&1 || mysql_install_db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1 || true
    fi

    echo "Starting MariaDB daemon..."
    /usr/sbin/mariadbd --user=mysql --datadir=/var/lib/mysql --skip-grant-tables > /var/log/mysql.log 2>&1 &

    echo "Waiting for MariaDB to accept connections..."
    READY=0
    for i in $(seq 1 35); do
        if mysqladmin ping --silent 2>/dev/null; then
            READY=1
            echo "MariaDB is ready!"
            break
        fi
        sleep 1
    done

    if [ "$READY" = "1" ]; then
        mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" || true

        HAS_CORE_TABLES=$(mysql -N -s -e "SELECT count(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME' AND table_name = 'users';" 2>/dev/null || echo "0")
        if [ "$HAS_CORE_TABLES" = "0" ] || [ -z "$HAS_CORE_TABLES" ]; then
            if [ -f "/var/www/html/database/schema.sql" ]; then
                echo "Importing initial database schema into local MariaDB ($DB_NAME)..."
                mysql "$DB_NAME" < /var/www/html/database/schema.sql || true
                echo "Local database schema import complete."
            fi
        else
            echo "Local database $DB_NAME already contains core application tables."
        fi

        cleanup_legacy_demo_accounts mysql

        if [ -f "/var/www/html/database/seed_students.php" ]; then
            echo "Seeding/verifying 13 KOMS student accounts in local MariaDB..."
            php /var/www/html/database/seed_students.php 2>/dev/null || true
        fi
    else
        echo "Warning: MariaDB did not become ready in time."
    fi
fi

mkdir -p /var/www/html/uploads
chown -R www-data:www-data /var/www/html/uploads 2>/dev/null || true
chmod -R 775 /var/www/html/uploads 2>/dev/null || true

echo "Launching Apache on port ${APACHE_PORT}..."
exec "$@"
