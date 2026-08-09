# Deploy — Controle Obra (Ubuntu Server)

Guia para subir o MVP no notebook com Ubuntu Server + Docker.

## 1. Requisitos no notebook

- Ubuntu Server
- Docker + Docker Compose plugin
- Git

```bash
sudo apt update
sudo apt install -y docker.io docker-compose-v2 git
sudo usermod -aG docker $USER
# saia e entre de novo no SSH/usuário
```

## 2. Copiar o projeto

No notebook:

```bash
cd ~
git clone <seu-repositorio> controle-obra
cd controle-obra
```

Ou copie a pasta `/home/kaiky/controle-obra` via pendrive/`scp`.

## 3. Configurar ambiente

```bash
cp .env.example .env
# Ajuste APP_URL para o IP do notebook, ex:
# APP_URL=http://192.168.1.50
```

Gere a chave e suba os containers (Sail com **PHP 8.4**):

```bash
# Importante: NÃO use --no-dev aqui.
# O Laravel Sail está em require-dev; sem ele não existe ./vendor/bin/sail.
docker run --rm -v "$(pwd)":/var/www/html -w /var/www/html laravelsail/php84-composer:latest composer install
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --force
./vendor/bin/sail artisan db:seed --force
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

Se o `vendor` já existir mas o Sail faltar (porque rodou com `--no-dev`), rode de novo o `composer install` acima e confira:

```bash
ls -la vendor/bin/sail
```

### Se o `sail build` falhar (erro `failed to solve` / `exit code: 1`)

A imagem do Sail é bem pesada. Em notebook antigo costuma falhar por memória, rede ou timeout.

Tente nesta ordem:

```bash
# Liberar espaço Docker
docker system prune -af

# Build de novo (PHP 8.4 — já configurado no compose.yaml)
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d
```

**Plano B — copiar a imagem pronta do PC para o notebook**

No PC (WSL), depois que a imagem `sail-8.4/app` existir:

```bash
docker save sail-8.4/app | gzip > /mnt/c/Users/kakam/sail-8.4-app.tar.gz
```

Leve o arquivo para o notebook e rode:

```bash
gunzip -c sail-8.4-app.tar.gz | docker load
./vendor/bin/sail up -d
```

## 4. Usuários iniciais

| Papel | E-mail | Senha |
|-------|--------|-------|
| Admin | admin@controleobra.test | password |
| Ajudante | ajuda@controleobra.test | password |

**Troque as senhas** depois do primeiro login.

- Admin: tudo
- Ajudante: só marca presença e consulta (sem fechar período, sem apagar)

## 5. Acesso na rede

No notebook, descubra o IP:

```bash
ip a
```

No celular/PC da mesma Wi‑Fi: `http://IP-DO-NOTEBOOK`

Libere a porta 80 no firewall se necessário:

```bash
sudo ufw allow 80/tcp
sudo ufw enable
```

## 6. Backup

### 6.1 Backup diário no notebook

```bash
chmod +x scripts/backup.sh
mkdir -p storage/backups
cp .env.backup.example .env.backup   # opcional, para envio ao PC
./scripts/backup.sh
```

Cron diário às 23h:

```cron
0 23 * * * /home/SEU_USUARIO/controle-obra/scripts/backup.sh >> /home/SEU_USUARIO/controle-obra/storage/logs/backup.log 2>&1
```

### 6.2 Cópia semanal para o seu PC Windows

Pasta já preparada no PC: `C:\Users\kakam\backups-controle-obra`

**Opção A — o PC puxa do notebook (recomendada)**

1. No notebook, deixe o SSH ligado (`sudo apt install openssh-server`).
2. No PC, abra PowerShell e teste:
   ```powershell
   ssh usuario@IP_DO_NOTEBOOK
   ```
3. Edite `C:\Users\kakam\backups-controle-obra\puxar-backup-do-servidor.ps1`
   (coloque usuário e IP do notebook).
4. Agendador de Tarefas do Windows → Nova tarefa:
   - Disparo: semanal (ex.: domingo 10h)
   - Ação: `powershell.exe`
   - Argumentos:
     ```
     -ExecutionPolicy Bypass -File "C:\Users\kakam\backups-controle-obra\puxar-backup-do-servidor.ps1"
     ```
   - Marque “executar mesmo se o usuário não estiver logado” se quiser.

Assim o backup fica no notebook **e** uma cópia semanal no PC. Se o notebook quebrar, você restaura pelo arquivo do PC.

**Opção B — o notebook envia para o PC**

1. No Windows: Configurações → Aplicativos → Recursos opcionais → **Servidor OpenSSH**.
2. No notebook, configure `.env.backup`:
   ```bash
   COPIAR_PARA_PC=1
   PC_DESTINO=kakam@IP_DO_PC:C:/Users/kakam/backups-controle-obra/
   ```
3. Cron semanal (domingo 9h) no notebook:
   ```cron
   0 9 * * 0 COPIAR_PARA_PC=1 /home/SEU_USUARIO/controle-obra/scripts/backup.sh >> /home/SEU_USUARIO/controle-obra/storage/logs/backup-pc.log 2>&1
   ```

**Opção C — OneDrive / Google Drive**

Salve/copie os `.sql.gz` para uma pasta sincronizada na nuvem. Simples, mas depende da conta.

### 6.3 Restaurar

No servidor:

```bash
gunzip -c storage/backups/controle-obra-XXXX.sql.gz | ./vendor/bin/sail exec -T mysql mysql -u sail -ppassword laravel
```

Ou use o arquivo baixado em `C:\Users\kakam\backups-controle-obra`.

## 7. Fora da Wi‑Fi (depois)

Opções:
- Cloudflare Tunnel / Tailscale (recomendado para começar)
- Hospedagem/VPS com domínio

O notebook precisa ficar ligado e com internet estável.

## 8. Comandos do dia a dia

```bash
./vendor/bin/sail up -d
./vendor/bin/sail down
./vendor/bin/sail artisan migrate --force
./scripts/backup.sh
```
