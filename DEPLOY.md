# Deploy — Controle Obra (Ubuntu Server)

Guia oficial para a subida, configuração e operação do MVP do sistema **Controle Obra** em ambiente de produção local (Ubuntu Server + Docker / Laravel Sail com PHP 8.4).

---

## 1. Requisitos no Servidor

Certifique-se de que o servidor possui os pacotes base necessários instalados e o utilizador devidamente configurado no grupo do Docker.

```bash
sudo apt update
sudo apt install -y docker.io docker-compose-v2 git
sudo usermod -aG docker $USER
```
> **⚠️ Nota de Segurança:** Após executar o comando `usermod`, termine a sessão SSH e volte a iniciar sessão para aplicar as permissões de grupo.

---

## 2. Obtenção do Código

Pode obter o código-fonte diretamente via repositório Git ou através de transferência direta:

* **Opção A — Via Git:**
  ```bash
  cd ~
  git clone <seu-repositorio> controle-obra
  cd controle-obra
  ```
---

## 3. Configuração de Ambiente e Execução

### A. Configuração do Ficheiro de Ambiente
```bash
cp .env.example .env
```
> **Importante:** Edite o ficheiro `.env` gerado e ajuste a variável `APP_URL` para refletir o IP real do seu servidor/notebook (ex: `APP_URL=http://192.168.1.50`).

### B. Otimização de Imagem Docker (Opcional, altamente recomendada)
Para poupar tempo de compilação (*build*) no servidor, pode exportar a imagem Docker pré-construída diretamente da sua máquina de desenvolvimento WSL para o servidor:

* **Na máquina de desenvolvimento (WSL):**
  ```bash
  docker save sail-8.4/app | gzip > /mnt/c/Users/user/sail-8.4-app.tar.gz
  ```
* **No servidor (após transferir o arquivo compactado):**
  ```bash
  gunzip -c sail-8.4-app.tar.gz | docker load
  ```

### C. Instalação de Dependências e Subida dos Contentores
Execute os comandos abaixo para instalar as dependências com o PHP 8.4, compilar as imagens e iniciar os serviços em segundo plano:

```bash
# Instalação das dependências do composer via imagem PHP 8.4 do Sail
docker run --rm -v "$(pwd)":/var/www/html -w /var/www/html laravelsail/php84-composer:latest composer install

# Subida e construção do ambiente
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d

# Configuração da aplicação, migrações e dados iniciais
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --force
./vendor/bin/sail artisan db:seed --force

# Instalação e build dos assets frontend
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

---

## 4. Utilizadores Iniciais

O sistema é inicializado com dois perfis pré-configurados. **Recomenda-se a alteração imediata das passwords** após o primeiro acesso.

| Papel | E-mail de Acesso | Palavra-passe | Permissões Principais |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@controleobra.test` | `password` | Acesso total: cadastros, períodos, lançamentos, relatórios e fecho financeiro. |
| **Ajudante** | `ajuda@controleobra.test` | `password` | Acesso restrito: marcação rápida de presença e consultas (sem permissão para fechar ou apagar). |

---

## 5. Acesso em Rede e Firewall

### A. Identificação do IP Local
No terminal do servidor, descubra o endereço IP atribuído à interface de rede local:
```bash
ip a
```

### B. Configuração do Firewall (UFW)
Caso o acesso a partir de outros dispositivos na mesma rede esteja bloqueado, liberte a porta HTTP (80):
```bash
sudo ufw allow 80/tcp
sudo ufw enable
```

👉 Aceda à aplicação a partir de qualquer dispositivo na mesma rede Wi-Fi através de: `http://<IP-DO-NOTEBOOK>`

---

## 6. Rotinas de Backup e Recuperação

### 6.1 Backup Diário no Servidor
Prepare o ambiente de backup executando os seguintes comandos:
```bash
chmod +x scripts/backup.sh
mkdir -p storage/backups
cp .env.backup.example .env.backup   # Opcional, para envio automatizado
./scripts/backup.sh
```

Para automatizar a execução diária às 23h, adicione a seguinte linha ao seu `crontab` (`crontab -e`):
```cron
0 23 * * * /home/SEU_USUARIO/controle-obra/scripts/backup.sh >> /home/SEU_USUARIO/controle-obra/storage/logs/backup.log 2>&1
```

### 6.2 Cópia Semanal para o Host (Windows)
A pasta de destino no PC Windows está pré-configurada em: `C:\Users\user\backups-controle-obra`

* **Opção A — O PC puxa do notebook (Recomendada):**
  1. Certifique-se de que o SSH está ativo no servidor: `sudo apt install openssh-server`.
  2. No PC Windows, abra o PowerShell e valide a ligação: `ssh usuario@IP_DO_NOTEBOOK`.
  3. Edite o script `C:\Ususer\backups-controle-obra\puxar-backup-do-servidor.ps1` inserindo o utilizador e o IP correto do notebook.
  4. No Agendador de Tarefas do Windows, crie uma nova tarefa recorrente (ex: Domingo às 10h):
     * **Ação:** `powershell.exe`
     * **Argumentos:** `-ExecutionPolicy Bypass -File "C:\Users\user\backups-controle-obra\puxar-backup-do-servidor.ps1"`

* **Opção B — O notebook envia para o PC:**
  1. No Windows, ative o recurso opcional **Servidor OpenSSH**.
  2. No servidor, configure o ficheiro `.env.backup`:
     ```bash
     COPIAR_PARA_PC=1
     PC_DESTINO=user@IP_DO_PC:C:/Users/user/backups-controle-obra/
     ```
  3. Adicione a tarefa ao `crontab` do servidor (ex: Domingo às 9h):
     ```cron
     0 9 * * 0 COPIAR_PARA_PC=1 /home/SEU_USUARIO/controle-obra/scripts/backup.sh >> /home/SEU_USUARIO/controle-obra/storage/logs/backup-pc.log 2>&1
     ```

### 6.3 Restauração de Base de Dados
Para restaurar um ponto de salvaguarda diretamente no servidor:
```bash
gunzip -c storage/backups/controle-obra-XXXX.sql.gz | ./vendor/bin/sail exec -T mysql mysql -u sail -ppassword laravel
```
*(Também pode utilizar os ficheiros sincronizados na pasta do seu PC Windows, se necessário).*

---

## 7. Acesso Remoto (Fora da Wi-Fi Local)

Caso precise de aceder ao sistema fora da rede local do canteiro de obras, recomenda-se a utilização de túneis seguros:
* **Tailscale** ou **Cloudflare Tunnel** (soluções rápidas, seguras e fáceis de configurar para manter o acesso externo sem expor portas diretamente).

---

## 8. Comandos Operacionais (Dia a Dia)

Guarde os comandos mais comuns para a gestão diária do ambiente:

```bash
# Iniciar os serviços em background
./vendor/bin/sail up -d

# Parar os serviços
./vendor/bin/sail down

# Aplicar migrações pendentes em produção
./vendor/bin/sail artisan migrate --force

# Executar backup manual imediato
./scripts/backup.sh
```