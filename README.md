# Controle Obra

Sistema para gestão de diárias e pagamentos de pessoal em obras.

## Stack

- Laravel + Blade (Breeze)
- MySQL
- Docker via Laravel Sail (WSL / Ubuntu Server)

## Subir o projeto

```bash
cd ~/controle-obra
./vendor/bin/sail up -d
```

Acesse: http://localhost

Deploy no notebook: veja [DEPLOY.md](DEPLOY.md).

## Logins

| Papel | E-mail | Senha |
|-------|--------|-------|
| Admin | admin@controleobra.test | password |
| Ajudante | ajuda@controleobra.test | password |

- **Admin:** cadastros, períodos, lançamentos, relatórios, fechamento
- **Ajudante:** marcar presença e consultar (sem fechar/apagar)

## Funcionalidades

- Obras e pessoal (remoção preserva histórico)
- Diária com histórico + locomoção (ônibus/gasolina)
- Marcação rápida de presença (celular)
- Calendário mensal por pessoa
- Períodos (aberto → fechado → pago) com forma de pagamento
- Adiantamentos, descontos e bônus
- Comprovante imprimível do período
- Relatório de custo por obra
- Backup: `./scripts/backup.sh`

## Backup neste PC

```bash
cd ~/controle-obra
./scripts/backup-pc.sh
```

Arquivos em: `C:\Users\kakam\backups-controle-obra`

Limpar dados e recomeçar do zero:

```bash
./vendor/bin/sail artisan app:limpar-dados-teste --force
```
