<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprovante — {{ $periodo->nome ?: 'Período' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            margin: 0;
            padding: 24px;
            background: #f3f4f6;
        }
        .folha {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 28px;
            border: 1px solid #e5e7eb;
        }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .meta { color: #555; font-size: 14px; margin-bottom: 20px; }
        .acoes { margin-bottom: 16px; display: flex; gap: 8px; flex-wrap: wrap; }
        .acoes a, .acoes button {
            font-size: 14px;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            background: #fff;
            border-radius: 6px;
            text-decoration: none;
            color: #111;
            cursor: pointer;
        }
        .acoes button.primario { background: #1c1917; color: #fff; border-color: #1c1917; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 13px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px 6px; text-align: left; vertical-align: top; }
        th { font-size: 12px; color: #555; }
        td.valor, th.valor { text-align: right; white-space: nowrap; }
        .pessoa { margin-top: 22px; page-break-inside: avoid; }
        .pessoa h2 { font-size: 16px; margin: 0 0 4px; }
        .totais { margin-top: 24px; border-top: 2px solid #111; padding-top: 12px; }
        .assinatura {
            margin-top: 36px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        .linha { border-top: 1px solid #111; margin-top: 40px; padding-top: 6px; font-size: 12px; color: #555; }
        @media print {
            body { background: #fff; padding: 0; }
            .acoes { display: none !important; }
            .folha { border: none; padding: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="folha">
        <div class="acoes">
            <button class="primario" onclick="window.print()">Imprimir / Salvar PDF</button>
            <a href="{{ route('periodos.show', $periodo) }}">Voltar ao período</a>
        </div>

        <h1>Controle Obra — Comprovante de pagamento</h1>
        <div class="meta">
            {{ $periodo->nome ?: 'Período' }}
            · {{ $periodo->data_inicio->format('d/m/Y') }} a {{ $periodo->data_fim->format('d/m/Y') }}
            · Status: {{ \App\Models\PeriodoPagamento::STATUS[$periodo->status] ?? $periodo->status }}
            · Emitido em {{ now()->format('d/m/Y H:i') }}
        </div>

        @forelse ($resumo as $item)
            @php $pagamento = $pagamentos->get($item['funcionario']?->id); @endphp
            <section class="pessoa">
                <h2>{{ $item['funcionario']?->nome }}</h2>
                <div class="meta" style="margin-bottom: 8px;">
                    {{ $item['dias'] }} dia(s)
                    @if ($pagamento)
                        · Forma: {{ $pagamento->labelForma() }}
                        @if ($pagamento->data_pagamento)
                            · Pago em {{ $pagamento->data_pagamento->format('d/m/Y') }}
                        @endif
                    @endif
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Obra</th>
                            <th>Tipo</th>
                            <th class="valor">Diária</th>
                            <th class="valor">Locomoção</th>
                            <th class="valor">Total dia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($item['presencas'] as $presenca)
                            <tr>
                                <td>{{ $presenca->data->format('d/m/Y') }}</td>
                                <td>{{ $presenca->obra?->nome }}</td>
                                <td>{{ \App\Models\Presenca::TIPOS[$presenca->tipo] ?? $presenca->tipo }}</td>
                                <td class="valor">R$ {{ number_format($presenca->valor_aplicado, 2, ',', '.') }}</td>
                                <td class="valor">R$ {{ number_format($presenca->valor_locomocao, 2, ',', '.') }}</td>
                                <td class="valor">R$ {{ number_format($presenca->valor_aplicado + $presenca->valor_locomocao, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">Sem dias marcados (apenas lançamentos).</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <table>
                    <tbody>
                        <tr>
                            <td>Total diárias</td>
                            <td class="valor">R$ {{ number_format($item['total_diarias'], 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Total locomoção</td>
                            <td class="valor">R$ {{ number_format($item['total_locomocao'], 2, ',', '.') }}</td>
                        </tr>
                        @if ($item['bonus'] > 0)
                            <tr>
                                <td>Bônus</td>
                                <td class="valor">R$ {{ number_format($item['bonus'], 2, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($item['adiantamentos'] > 0)
                            <tr>
                                <td>Adiantamentos (−)</td>
                                <td class="valor">R$ {{ number_format($item['adiantamentos'], 2, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($item['descontos'] > 0)
                            <tr>
                                <td>Descontos (−)</td>
                                <td class="valor">R$ {{ number_format($item['descontos'], 2, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td><strong>A pagar</strong></td>
                            <td class="valor"><strong>R$ {{ number_format($item['a_pagar'], 2, ',', '.') }}</strong></td>
                        </tr>
                    </tbody>
                </table>

                <div class="assinatura">
                    <div>
                        <div class="linha">Assinatura do trabalhador</div>
                    </div>
                    <div>
                        <div class="linha">Responsável pelo pagamento</div>
                    </div>
                </div>
            </section>
        @empty
            <p>Nenhum valor neste período.</p>
        @endforelse

        <div class="totais">
            <table>
                <tr>
                    <td><strong>Total geral do período</strong></td>
                    <td class="valor"><strong>R$ {{ number_format(collect($resumo)->sum('a_pagar'), 2, ',', '.') }}</strong></td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
