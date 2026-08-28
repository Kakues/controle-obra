#!/usr/bin/env bash
set -euo pipefail

# Backup do MySQL do Controle Obra (Laravel Sail / Docker Compose)
#
# Uso local:
#   ./scripts/backup.sh
#
# Com envio para o PC (SCP), depois de configurar:
#   COPIAR_PARA_PC=1 ./scripts/backup.sh
#
# Variáveis opcionais:
#   DESTINO=...                 pasta local dos backups
#   COPIAR_PARA_PC=1            envia uma cópia via SCP
#   PC_DESTINO=usuario@IP:pasta  destino SCP no seu computador
#   MANTER=14                   quantos backups locais manter

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DESTINO="${DESTINO:-$ROOT_DIR/storage/backups}"
MANTER="${MANTER:-14}"
DATA="$(date +%Y-%m-%d_%H-%M-%S)"
ARQUIVO="$DESTINO/controle-obra-$DATA.sql.gz"
ENV_BACKUP="$ROOT_DIR/.env.backup"

if [[ -f "$ENV_BACKUP" ]]; then
  # shellcheck disable=SC1090
  source "$ENV_BACKUP"
fi

mkdir -p "$DESTINO"
cd "$ROOT_DIR"

if [[ -x "./vendor/bin/sail" ]] && ./vendor/bin/sail ps >/dev/null 2>&1; then
  ./vendor/bin/sail exec -T mysql mysqldump --no-tablespaces -u"${DB_USERNAME:-sail}" -p"${DB_PASSWORD:-password}" "${DB_DATABASE:-laravel}" | gzip > "$ARQUIVO"
else
  docker compose exec -T mysql mysqldump --no-tablespaces -u"${DB_USERNAME:-sail}" -p"${DB_PASSWORD:-password}" "${DB_DATABASE:-laravel}" | gzip > "$ARQUIVO"
fi

# Mantém só os últimos N backups locais
mapfile -t ANTIGOS < <(ls -1t "$DESTINO"/controle-obra-*.sql.gz 2>/dev/null | tail -n +"$((MANTER + 1))" || true)
if ((${#ANTIGOS[@]} > 0)); then
  rm -f -- "${ANTIGOS[@]}"
fi

echo "Backup local: $ARQUIVO"

if [[ "${COPIAR_PARA_PC:-0}" == "1" ]]; then
  if [[ -z "${PC_DESTINO:-}" ]]; then
    echo "COPIAR_PARA_PC=1, mas PC_DESTINO não está definido em .env.backup" >&2
    exit 1
  fi

  scp -o StrictHostKeyChecking=accept-new "$ARQUIVO" "$PC_DESTINO"
  echo "Cópia enviada para: $PC_DESTINO"
fi
