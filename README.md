<p align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker">
  <img src="https://img.shields.io/badge/Ubuntu-E95420?style=for-the-badge&logo=ubuntu&logoColor=white" alt="Ubuntu">
</p>

# Controle Obra

Sistema web desenvolvido para a gestão simplificada de diárias, presenças e pagamentos de pessoal em canteiros de obras.

---

## 🛠️ Stack Tecnológica

* **Back-end & Interface:** PHP / Laravel + Blade (com Laravel Breeze)
* **Base de Dados:** MySQL
* **Ambiente & Virtualização:** Docker via Laravel Sail (WSL / Ubuntu Server)

---

## 📋 Pré-requisitos

Certifique-se de ter instalado no seu ambiente de desenvolvimento:
* Windows 11 com WSL2 (Ubuntu)
* Docker Desktop configurado com integração WSL2
* Node.js e Composer (caso execute comandos fora do contentor)

---

## 🚀 Instalação e Execução (Deploy Local)

Aceda ao seu terminal WSL (Ubuntu) e siga os passos abaixo para iniciar o projeto:

```bash
# 1. Entre na pasta do projeto
cd ~/controle-obra

# 2. Copie o ficheiro de configuração de ambiente (se ainda não o tiver feito)
cp .env.example .env

# 3. Suba os contentores Docker em segundo plano via Laravel Sail
./vendor/bin/sail up -d

# 4. Instale as dependências e gere a chave da aplicação (primeira execução)
./vendor/bin/sail composer install
./vendor/bin/sail artisan key:generate

# 5. Execute as migrações e seeders da base de dados
./vendor/bin/sail artisan migrate --seed

---

## 🔑 Perfis de Acesso

O sistema dispõe de dois perfis pré-configurados para validação e operação diária:

| Papel | E-mail de Acesso | Palavra-passe | Permissões Principais |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@controleobra.test` | `password` | Cadastros gerais, gestão de períodos, lançamentos, relatórios e fechamento financeiro. |
| **Ajudante** | `ajuda@controleobra.test` | `password` | Marcação rápida de presença e consulta (sem permissões para fechar ou apagar registos). |

---

## ✨ Funcionalidades

* **Gestão de Pessoal e Obras:** Registo de equipas e locais de trabalho (a remoção de registos preserva todo o histórico financeiro).
* **Controle de Diárias:** Registo diário com histórico detalhado e cálculo de locomoção (transporte coletivo / combustível).
* **Presença Rápida:** Interface otimizada e responsiva para marcação ágil de presenças diretamente via telemóvel no canteiro.
* **Calendário Mensal:** Visão consolidada e individual por colaborador.
* **Ciclo de Períodos:** Gestão de estados do ciclo financeiro (`aberto` → `fechado` → `pago`) com especificação da forma de pagamento.
* **Ajustes Financeiros:** Suporte integrado a adiantamentos, descontos e bónus.
* **Comprovativos e Relatórios:** Emissão de comprovativo imprimível por período e relatório analítico de custo por obra.

---

## ⚙️ Manutenção, Operações e Backup

### 1. Sistema de Backup (Servidor / WSL)
Para gerar uma cópia de segurança manual diretamente no servidor:
```bash
./scripts/backup.sh

### 2. Sincronização de Backup para o Host (Windows)

Para puxar e armazenar os ficheiros de backup diretamente na sua máquina Windows:

```bash
cd ~/controle-obra
./scripts/backup-pc.sh

Destino no Windows: C:\Users\user\backups-controle-obra

### 3. Reposição de Fábrica (Limpeza de Dados)

> **⚠️ Aviso Crítico:** O comando abaixo elimina permanentemente todos os dados e reinicia a base de dados do zero. Utilize com extrema cautela.

```bash
./vendor/bin/sail artisan app:limpar-dados-teste --force