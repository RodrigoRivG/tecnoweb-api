#!/usr/bin/env bash
set -Eeuo pipefail

APP_KIND=backend
: "${SSH_HOST:?Configura el secret SSH_HOST}"
: "${SSH_USER:?Configura el secret SSH_USER}"
: "${SSH_PRIVATE_KEY:?Configura el secret SSH_PRIVATE_KEY}"
: "${SSH_KNOWN_HOSTS:?Configura el secret SSH_KNOWN_HOSTS}"
: "${DEPLOY_PATH:?Configura la variable DEPLOY_PATH}"
: "${PUBLIC_BASE_URL:?Configura la variable PUBLIC_BASE_URL}"
: "${RUNNER_TEMP:?Falta RUNNER_TEMP}"
: "${GITHUB_SHA:?Falta GITHUB_SHA}"
: "${GITHUB_RUN_ID:?Falta GITHUB_RUN_ID}"
SSH_PORT=${SSH_PORT:-22}
PHP_BIN=${PHP_BIN:-php}
PHP_WEB_USER=${PHP_WEB_USER:-apache}
RUN_MIGRATIONS=${RUN_MIGRATIONS:-false}

[[ "$APP_KIND" == backend ]]
[[ "$SSH_HOST" =~ ^[a-zA-Z0-9][a-zA-Z0-9.-]*$ ]]
[[ "$SSH_USER" =~ ^[a-zA-Z0-9_][a-zA-Z0-9_.-]*$ ]]
[[ "$SSH_PORT" =~ ^[0-9]+$ ]]
[[ "$DEPLOY_PATH" =~ ^/home/[a-zA-Z0-9_.-]+/proyecto2/backend$ ]] || {
  echo 'DEPLOY_PATH debe ser /home/USUARIO/proyecto2/backend.' >&2; exit 1;
}
[[ "$PUBLIC_BASE_URL" =~ ^https://[a-zA-Z0-9.-]+/inf513/[a-zA-Z0-9_.-]+/proyecto2/?$ ]]
PUBLIC_BASE_URL=${PUBLIC_BASE_URL%/}
[[ "$PHP_BIN" =~ ^[a-zA-Z0-9_./-]+$ ]]
[[ "$PHP_WEB_USER" =~ ^[a-zA-Z0-9_][a-zA-Z0-9_.-]*$ ]]
[[ "$RUN_MIGRATIONS" == true || "$RUN_MIGRATIONS" == false ]]
[[ "$GITHUB_SHA" =~ ^[a-f0-9]{40}$ ]]
[[ "$GITHUB_RUN_ID" =~ ^[0-9]+$ ]]
[[ "${GITHUB_RUN_ATTEMPT:-1}" =~ ^[0-9]+$ ]]
[[ -f "$RUNNER_TEMP/release.tar.gz" ]]

release_id="tecnoweb-$GITHUB_SHA-$GITHUB_RUN_ID-${GITHUB_RUN_ATTEMPT:-1}"
ssh_dir=$(mktemp -d)
trap 'rm -rf -- "$ssh_dir"' EXIT
chmod 700 "$ssh_dir"
printf '%s\n' "$SSH_PRIVATE_KEY" | tr -d '\r' > "$ssh_dir/key"
printf '%s\n' "$SSH_KNOWN_HOSTS" > "$ssh_dir/known_hosts"
chmod 600 "$ssh_dir/key" "$ssh_dir/known_hosts"
ssh_options=(-i "$ssh_dir/key" -o IdentitiesOnly=yes -o BatchMode=yes
  -o StrictHostKeyChecking=yes -o "UserKnownHostsFile=$ssh_dir/known_hosts"
  -o ConnectTimeout=20)
target="$SSH_USER@$SSH_HOST"

ssh "${ssh_options[@]}" -p "$SSH_PORT" "$target" "bash -s -- '$DEPLOY_PATH'" <<'PREPARE'
set -Eeuo pipefail
root=$1
mkdir -p "$root/releases"
if [[ -e "$root/.htaccess" ]]; then
  grep -qFx '# proyecto2-backend-protection-v1' "$root/.htaccess" || {
    echo 'El backend ya tiene un .htaccess ajeno al despliegue. Revisalo antes de continuar.' >&2
    exit 1
  }
fi
PREPARE
scp "${ssh_options[@]}" -P "$SSH_PORT" .github/deployment/backend.htaccess "$target:$DEPLOY_PATH/.htaccess-$release_id"

probe="deploy-check-$release_id.txt"
ssh "${ssh_options[@]}" -p "$SSH_PORT" "$target" "mv -Tf '$DEPLOY_PATH/.htaccess-$release_id' '$DEPLOY_PATH/.htaccess' && printf 'private deployment check' > '$DEPLOY_PATH/$probe'"
status=$(curl --silent --show-error --output /dev/null --write-out '%{http_code}' \
  --connect-timeout 20 --max-time 30 "$PUBLIC_BASE_URL/backend/$probe")
ssh "${ssh_options[@]}" -p "$SSH_PORT" "$target" "rm -- '$DEPLOY_PATH/$probe'"
[[ "$status" == 403 ]] || {
  echo "La proteccion HTTP del backend no devolvio 403 (HTTP $status). Revisa Apache/AllowOverride antes de subir Laravel." >&2
  exit 1
}

scp "${ssh_options[@]}" -P "$SSH_PORT" "$RUNNER_TEMP/release.tar.gz" \
  "$target:$DEPLOY_PATH/releases/$release_id.tar.gz"
ssh "${ssh_options[@]}" -p "$SSH_PORT" "$target" \
  "bash -s -- '$DEPLOY_PATH' '$release_id' '$APP_KIND' '$PHP_BIN' '$RUN_MIGRATIONS' '$PHP_WEB_USER'" <<'REMOTE'
set -Eeuo pipefail
root=$1
release_id=$2
kind=$3
php_bin=$4
run_migrations=$5
php_web_user=$6
deploy_user=$(id -un)
project_root="${root%/backend}"
release="$root/releases/$release_id"
archive="$root/releases/$release_id.tar.gz"

[[ ! -e "$release" ]] || { echo 'La versiÃ³n ya existe.' >&2; exit 1; }
mkdir -p "$release"
tar -xzf "$archive" -C "$release"
rm -- "$archive"

if [[ "$kind" == backend ]]; then
  command -v "$php_bin" >/dev/null
  command -v setfacl >/dev/null
  id "$php_web_user" >/dev/null
  "$php_bin" "$release/vendor/composer/platform_check.php"
  "$php_bin" -r 'foreach (["pdo_pgsql", "mbstring", "fileinfo", "openssl", "dom", "xml", "curl"] as $extension) { if (!extension_loaded($extension)) { fwrite(STDERR, "Falta la extensiÃ³n PHP: ".$extension.PHP_EOL); exit(1); } }'
  mkdir -p "$root/shared/storage/app/public" "$root/shared/storage/app/private" \
    "$root/shared/storage/framework/cache/data" "$root/shared/storage/framework/sessions" \
    "$root/shared/storage/framework/views" "$root/shared/storage/logs"
  if [[ ! -f "$root/shared/.env" ]]; then
    cp "$release/.env.production.example" "$root/shared/.env.production.example"
    echo "Falta $root/shared/.env. ConfigÃºralo en el servidor con APP_ENV=production, APP_DEBUG=false, APP_KEY y PostgreSQL; vuelve a ejecutar el workflow." >&2
    exit 1
  fi
  grep -Eq '^APP_ENV="?production"?[[:space:]]*$' "$root/shared/.env" || { echo 'Configura APP_ENV=production.' >&2; exit 1; }
  grep -Eq '^APP_DEBUG="?false"?[[:space:]]*$' "$root/shared/.env" || { echo 'Configura APP_DEBUG=false.' >&2; exit 1; }
  grep -Eq '^APP_KEY="?base64:[^"[:space:]]+' "$root/shared/.env" || { echo 'Configura una APP_KEY vÃ¡lida y permanente.' >&2; exit 1; }
  grep -Eq '^DB_CONNECTION="?pgsql"?[[:space:]]*$' "$root/shared/.env" || { echo 'Configura PostgreSQL.' >&2; exit 1; }
  grep -Eq '^DB_SEARCH_PATH="?talento"?[[:space:]]*$' "$root/shared/.env" || { echo 'Configura DB_SEARCH_PATH=talento.' >&2; exit 1; }
  grep -Eq '^DB_MIGRATIONS_TABLE="?talento.migrations"?[[:space:]]*$' "$root/shared/.env" || { echo 'Configura DB_MIGRATIONS_TABLE=talento.migrations.' >&2; exit 1; }
  chmod 600 "$root/shared/.env"
  setfacl -m "u:$php_web_user:r--" "$root/shared/.env"
  ln -s "$root/shared/.env" "$release/.env"
  ln -s "$root/shared/storage" "$release/storage"
  mkdir -p "$release/bootstrap/cache"
  for directory in "$root/shared/storage" "$release/bootstrap/cache"; do
    find "$directory" -user "$deploy_user" -type d -exec chmod u+rwx,go-rwx {} +
    find "$directory" -user "$deploy_user" -type f -exec chmod u+rw,go-rwx {} +
    find "$directory" -user "$deploy_user" \( -type f -o -type d \) \
      -exec setfacl -m "u:$deploy_user:rwX,u:$php_web_user:rwX" {} +
    find "$directory" -user "$deploy_user" -type d \
      -exec setfacl -m "d:u::rwx,d:u:$deploy_user:rwx,d:u:$php_web_user:rwx,d:g::---,d:m::rwx,d:o::---" {} +
  done
  ln -s "$root/shared/storage/app/public" "$release/public/storage"
  cd "$release"
  "$php_bin" artisan package:discover --no-interaction
  # FRONTEND_URL se lee con env() en el checkout Stripe actual: conservar .env cargado.
  "$php_bin" artisan config:clear --no-interaction
  "$php_bin" artisan route:cache --no-interaction
  "$php_bin" artisan view:cache --no-interaction
  if [[ "$run_migrations" == true ]]; then
    "$php_bin" .github/deployment/prepare.php
    "$php_bin" artisan migrate --force --no-interaction
  fi

  "$php_bin" artisan migrate:status --no-interaction
  "$php_bin" .github/deployment/check.php

  for entry in .htaccess api.php; do
    if [[ -e "$project_root/$entry" || -L "$project_root/$entry" ]]; then
      case "$entry" in
        .htaccess) marker='# proyecto2-api-routing-v1' ;;
        api.php) marker='// proyecto2-api-gateway-v1' ;;
      esac
      [[ ! -L "$project_root/$entry" ]] && grep -qFx "$marker" "$project_root/$entry" || {
        echo "Ya existe $project_root/$entry y no pertenece a este despliegue. Revisalo antes de reemplazarlo." >&2
        exit 1
      }
    fi
  done
else
  [[ -f "$release/index.html" ]]
fi

[[ ! -e "$root/current" || -L "$root/current" ]] || {
  echo 'current debe ser un enlace simbÃ³lico, no una carpeta con datos.' >&2; exit 1;
}
ln -s "$release" "$root/.current-$release_id"
mv -Tf "$root/.current-$release_id" "$root/current"
install -m 644 "$release/.github/deployment/api.php" "$project_root/.api-$release_id.php"
mv -Tf "$project_root/.api-$release_id.php" "$project_root/api.php"
install -m 644 "$release/.github/deployment/project.htaccess" "$project_root/.htaccess-$release_id"
mv -Tf "$project_root/.htaccess-$release_id" "$project_root/.htaccess"
printf 'Desplegado: %s/current -> %s\n' "$root" "$release"
REMOTE

# Comprobar una ruta publica real despues de activar la version.
curl --fail --silent --show-error --connect-timeout 20 --max-time 30 \
  -H 'Accept: application/json' "$PUBLIC_BASE_URL/api/v1/publico/facultades" -o /dev/null
