#!/usr/bin/env sh
set -eu

cd /var/www/html

echo "[entrypoint] bootstrapping Laravel container..."

if [ ! -f ".env" ]; then
  echo "[entrypoint] .env not found, copying from .env.example"
  cp .env.example .env
fi

if [ ! -f "vendor/autoload.php" ]; then
  echo "[entrypoint] installing composer dependencies"
  composer install --no-interaction --prefer-dist
fi

wait_for_mysql() {
  if [ "${DB_CONNECTION:-mysql}" != "mysql" ]; then
    return 0
  fi

  echo "[entrypoint] waiting for MySQL ${DB_HOST:-mysql}:${DB_PORT:-3306} ..."
  ATTEMPTS=0
  MAX_ATTEMPTS="${DB_WAIT_MAX_ATTEMPTS:-60}"

  while [ "$ATTEMPTS" -lt "$MAX_ATTEMPTS" ]; do
    if php -r '
      $host = getenv("DB_HOST") ?: "mysql";
      $port = (int) (getenv("DB_PORT") ?: 3306);
      $db = getenv("DB_DATABASE") ?: "meme_admin";
      $user = getenv("DB_USERNAME") ?: "root";
      $pass = getenv("DB_PASSWORD") ?: "";
      try {
          new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass, [PDO::ATTR_TIMEOUT => 2]);
          exit(0);
      } catch (Throwable $e) {
          exit(1);
      }'; then
      echo "[entrypoint] MySQL is ready"
      return 0
    fi
    ATTEMPTS=$((ATTEMPTS + 1))
    sleep 2
  done

  echo "[entrypoint] ERROR: MySQL not ready after ${MAX_ATTEMPTS} attempts"
  exit 1
}

wait_for_redis() {
  if [ "${REDIS_HOST:-}" = "" ]; then
    return 0
  fi

  echo "[entrypoint] waiting for Redis ${REDIS_HOST}:${REDIS_PORT:-6379} ..."
  ATTEMPTS=0
  MAX_ATTEMPTS="${REDIS_WAIT_MAX_ATTEMPTS:-60}"

  while [ "$ATTEMPTS" -lt "$MAX_ATTEMPTS" ]; do
    if php -r '
      if (!class_exists("Redis")) {
          exit(0);
      }
      $host = getenv("REDIS_HOST") ?: "redis";
      $port = (int) (getenv("REDIS_PORT") ?: 6379);
      try {
          $redis = new Redis();
          $redis->connect($host, $port, 1.5);
          $ok = $redis->ping();
          $redis->close();
          if ($ok === "+PONG" || $ok === true || $ok === "PONG") {
              exit(0);
          }
          exit(1);
      } catch (Throwable $e) {
          exit(1);
      }'; then
      echo "[entrypoint] Redis is ready"
      return 0
    fi
    ATTEMPTS=$((ATTEMPTS + 1))
    sleep 2
  done

  echo "[entrypoint] ERROR: Redis not ready after ${MAX_ATTEMPTS} attempts"
  exit 1
}

wait_for_mysql
wait_for_redis

if ! grep -q '^APP_KEY=' .env || grep -q '^APP_KEY=$' .env; then
  echo "[entrypoint] generating APP_KEY"
  php artisan key:generate --force >/dev/null 2>&1 || true
fi

echo "[entrypoint] creating storage symlink (if missing)"
php artisan storage:link >/dev/null 2>&1 || true

if [ "${AUTO_RUN_MIGRATIONS:-true}" = "true" ]; then
  echo "[entrypoint] running migrations with seed"
  php artisan migrate --seed --force
fi

echo "[entrypoint] starting Laravel server on :8000"

exec php artisan serve --host=0.0.0.0 --port=8000
