# Puxa o backup mais recente do notebook (Ubuntu Server) para este PC.
# Agende no Agendador de Tarefas do Windows (semanal).
#
# Uso:
#   powershell -ExecutionPolicy Bypass -File .\puxar-backup-do-servidor.ps1
#
# Configure abaixo:

$Servidor = "usuario@192.168.1.50"   # usuário@IP do notebook
$PastaRemota = "~/controle-obra/storage/backups"
$PastaLocal = "C:\Users\kakam\backups-controle-obra"
$Manter = 8

New-Item -ItemType Directory -Force -Path $PastaLocal | Out-Null

# Lista o arquivo mais recente no servidor e baixa
$arquivoRemoto = ssh $Servidor "ls -1t $PastaRemota/controle-obra-*.sql.gz 2>/dev/null | head -1"

if ([string]::IsNullOrWhiteSpace($arquivoRemoto)) {
    Write-Error "Nenhum backup encontrado em $Servidor`:$PastaRemota"
    exit 1
}

$nome = Split-Path $arquivoRemoto -Leaf
$destino = Join-Path $PastaLocal $nome

Write-Host "Baixando $arquivoRemoto ..."
scp "${Servidor}:${arquivoRemoto}" $destino
Write-Host "Salvo em $destino"

# Mantém só os últimos N no PC
Get-ChildItem $PastaLocal -Filter "controle-obra-*.sql.gz" |
    Sort-Object LastWriteTime -Descending |
    Select-Object -Skip $Manter |
    Remove-Item -Force

Write-Host "Concluído."
