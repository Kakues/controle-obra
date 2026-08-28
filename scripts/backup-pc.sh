#!/usr/bin/env bash
set -euo pipefail
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DESTINO_PC="${DESTINO_PC:-/mnt/c/Users/kakam/backups-controle-obra}"
DESTINO_LOCAL="${DESTINO_LOCAL:-$ROOT_DIR/storage/backups}"
DATA="$(date +%Y-%m-%d_%H-%M-%S)"
NOME="controle-obra-$DATA.sql.gz"
mkdir -p "$DESTINO_LOCAL" "$DESTINO_PC"
cd "$ROOT_DIR"
./vendor/bin/sail exec -T mysql mysqldump --no-tablespaces -u"${DB_USERNAME:-sail}" -p"${DB_PASSWORD:-password}" "${DB_DATABASE:-laravel}" | gzip > "$DESTINO_LOCAL/$NOME"
cp "$DESTINO_LOCAL/$NOME" "$DESTINO_PC/$NOME"
echo "Backup local: $DESTINO_LOCAL/$NOME"
echo "Backup no PC: $DESTINO_PC/$NOME"
