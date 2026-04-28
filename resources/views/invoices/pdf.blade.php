{{--
  TEMPLATE FATTURA PDF (M10 — InvoicePdfGenerator)

  Path: resources/views/invoices/pdf.blade.php
  Renderizzato da: App\Services\Invoice\InvoicePdfGenerator (via barryvdh/laravel-dompdf
  se installato, altrimenti fallback automatico al servizio raw).

  VARIABILI (passate da InvoicePdfGenerator::generate):
    - $order        Order (con relazioni user, packages, originAddress, destinationAddress, service)
    - $invoice      ['number','issue_date','due_date','order_number']
    - $cedente      array config('billing.cedente')
    - $pagamento    array config('billing.pagamento')
    - $bollo        ['applicabile','importo','nota']
    - $totals       ['imponibile_cents','iva_cents','iva_rate','totale_cents']

  STILE:
    - Formato A4 portrait, margine 20mm.
    - Palette: teal #095866 (brand), arancione #E44203 (accent), neutri.
    - Stile sobrio adatto a stampa B/N (contrasto sufficiente anche senza colore).
    - Font: DejaVu Sans (incluso in dompdf — supporta accenti italiani).
--}}
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Fattura {{ $invoice['number'] }}</title>
    <style>
        @page { margin: 20mm 18mm 22mm 18mm; }

        * { box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
            padding: 0;
            line-height: 1.45;
        }

        /* ── Header ─────────────────────────────────────────── */
        .header {
            border-bottom: 2px solid #095866;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .brand {
            font-size: 22px;
            font-weight: 700;
            color: #095866;
            letter-spacing: 0.3px;
        }
        .brand .accent { color: #E44203; }
        .brand-tagline {
            font-size: 9px;
            color: #6b7280;
            margin-top: 2px;
        }
        .cedente {
            font-size: 9px;
            color: #374151;
            text-align: right;
            line-height: 1.4;
        }
        .cedente strong { color: #095866; }

        /* ── Riga numero fattura + destinatario ─────────────── */
        .invoice-meta {
            margin-bottom: 16px;
        }
        .invoice-meta table { width: 100%; border-collapse: collapse; }
        .invoice-meta td { vertical-align: top; padding-right: 12px; }

        .meta-block {
            background: #f6f9fa;
            border-left: 3px solid #095866;
            padding: 10px 12px;
        }
        .meta-block.accent { border-left-color: #E44203; }
        .meta-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #6b7280;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .meta-value { font-size: 13px; color: #095866; font-weight: 700; }
        .meta-sub { font-size: 9px; color: #4b5563; margin-top: 4px; }

        /* ── Sezione destinatario ────────────────────────────── */
        .recipient-box {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 12px;
            font-size: 10px;
        }
        .recipient-box .row { margin: 2px 0; }
        .recipient-box .label {
            display: inline-block;
            min-width: 84px;
            color: #6b7280;
            font-weight: 600;
        }
        .recipient-box .value { color: #1f2937; }
        .recipient-box strong.name {
            font-size: 12px;
            color: #095866;
            display: block;
            margin-bottom: 4px;
        }

        /* ── Riferimento ordine ──────────────────────────────── */
        .order-ref {
            margin: 14px 0 10px;
            padding: 8px 10px;
            background: #fef4f0;
            border-left: 3px solid #E44203;
            font-size: 9.5px;
            color: #4b5563;
        }
        .order-ref strong { color: #E44203; }

        /* ── Sezioni e titoli ────────────────────────────────── */
        h2.section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #095866;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
            margin: 18px 0 8px;
        }

        /* ── Tabella righe fattura ───────────────────────────── */
        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.lines thead th {
            background: #095866;
            color: #ffffff;
            text-align: left;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.4px;
            padding: 7px 8px;
            text-transform: uppercase;
        }
        table.lines tbody td {
            border-bottom: 1px solid #e5e7eb;
            padding: 7px 8px;
            font-size: 9.5px;
            vertical-align: top;
        }
        table.lines tbody tr:nth-child(even) td { background: #fbfcfd; }
        table.lines td.num, table.lines th.num { text-align: right; white-space: nowrap; }
        table.lines td.center, table.lines th.center { text-align: center; }

        /* ── Tabella totali ──────────────────────────────────── */
        .totals-wrap {
            margin-top: 12px;
            display: block;
        }
        table.totals {
            width: 46%;
            margin-left: 54%;
            border-collapse: collapse;
        }
        table.totals td {
            padding: 5px 8px;
            font-size: 10px;
        }
        table.totals td.label { color: #4b5563; }
        table.totals td.value { text-align: right; color: #1f2937; white-space: nowrap; }
        table.totals tr.grand td {
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            background: #095866;
            border-top: 2px solid #E44203;
            padding-top: 8px;
            padding-bottom: 8px;
        }
        table.totals tr.bollo td {
            font-size: 9px;
            color: #6b7280;
            font-style: italic;
        }

        /* ── Note legali / pagamento ─────────────────────────── */
        .legal-notes {
            margin-top: 18px;
            padding: 10px 12px;
            background: #f6f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 8.5px;
            color: #4b5563;
            line-height: 1.5;
        }
        .legal-notes p { margin: 3px 0; }
        .legal-notes strong { color: #095866; }

        /* ── Footer ──────────────────────────────────────────── */
        .footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7.5px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }
        .footer span { color: #095866; font-weight: 700; }
    </style>
</head>
<body>

    {{-- ────────────────────── HEADER ────────────────────── --}}
    <div class="header">
        <table>
            <tr>
                <td style="width: 50%;">
                    <div class="brand">Spedizione<span class="accent">Facile</span></div>
                    <div class="brand-tagline">Spedizioni semplici, veloci e convenienti</div>
                </td>
                <td style="width: 50%;" class="cedente">
                    <strong>{{ $cedente['ragione_sociale'] ?? 'SpedizioneFacile S.r.l.' }}</strong><br>
                    P.IVA {{ $cedente['partita_iva'] ?? '—' }}
                    @if(!empty($cedente['codice_fiscale']) && $cedente['codice_fiscale'] !== ($cedente['partita_iva'] ?? null))
                        — C.F. {{ $cedente['codice_fiscale'] }}
                    @endif
                    <br>
                    {{ $cedente['indirizzo'] ?? '' }}<br>
                    {{ $cedente['cap'] ?? '' }} {{ $cedente['citta'] ?? '' }} ({{ $cedente['provincia'] ?? '' }}) — {{ $cedente['paese'] ?? 'IT' }}<br>
                    @if(!empty($cedente['email']))
                        {{ $cedente['email'] }}
                    @endif
                    @if(!empty($cedente['pec']))
                        — PEC {{ $cedente['pec'] }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- ────────────────────── META FATTURA ────────────────────── --}}
    <div class="invoice-meta">
        <table>
            <tr>
                <td style="width: 50%;">
                    <div class="meta-block">
                        <div class="meta-label">Numero fattura</div>
                        <div class="meta-value">{{ $invoice['number'] }}</div>
                        <div class="meta-sub">
                            Data emissione: <strong>{{ $invoice['issue_date']->format('d/m/Y') }}</strong>
                            @if($invoice['due_date']->greaterThan($invoice['issue_date']))
                                — Scadenza: <strong>{{ $invoice['due_date']->format('d/m/Y') }}</strong>
                            @else
                                — Pagamento: <strong>immediato</strong>
                            @endif
                        </div>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="meta-block accent">
                        <div class="meta-label">Riferimento ordine</div>
                        <div class="meta-value">{{ $invoice['order_number'] }}</div>
                        <div class="meta-sub">
                            Data ordine: {{ $order->created_at?->format('d/m/Y H:i') ?? 'n/d' }}
                            @if($order->brt_tracking_number)
                                <br>Tracking BRT: <strong>{{ $order->brt_tracking_number }}</strong>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ────────────────────── DESTINATARIO ────────────────────── --}}
    @php
        $billingData = is_array($order->billing_data) ? $order->billing_data : [];
        $userName = trim(($order->user->name ?? '') . ' ' . ($order->user->surname ?? ''));
        $isBusiness = ($billingData['subject_type'] ?? null) === 'azienda'
            || ($billingData['is_business'] ?? false) === true
            || !empty($billingData['ragione_sociale'])
            || !empty($billingData['company_name']);

        $intestatario = $isBusiness
            ? ($billingData['ragione_sociale'] ?? $billingData['company_name'] ?? $billingData['name'] ?? $userName)
            : ($billingData['nome_completo'] ?? $billingData['name'] ?? $userName);

        $vat = $billingData['p_iva'] ?? $billingData['vat_number'] ?? null;
        $cf = $billingData['codice_fiscale'] ?? $billingData['fiscal_code'] ?? null;
        $sdi = $billingData['codice_sdi'] ?? $billingData['sdi_code'] ?? null;
        $pec = $billingData['pec'] ?? $billingData['pec_email'] ?? null;
        $billAddress = $billingData['indirizzo'] ?? $billingData['address'] ?? null;
        $billCap = $billingData['postal_code'] ?? null;
        $billCity = $billingData['city'] ?? null;
        $billProv = $billingData['province'] ?? null;
    @endphp

    <h2 class="section-title">Destinatario</h2>
    <div class="recipient-box">
        <strong class="name">{{ $intestatario ?: '—' }}</strong>
        @if($isBusiness && !empty($vat))
            <div class="row"><span class="label">Partita IVA:</span> <span class="value">{{ $vat }}</span></div>
        @endif
        @if(!empty($cf))
            <div class="row"><span class="label">Cod. Fiscale:</span> <span class="value">{{ $cf }}</span></div>
        @endif
        @if(!empty($sdi) && $sdi !== '0000000')
            <div class="row"><span class="label">Codice SDI:</span> <span class="value">{{ $sdi }}</span></div>
        @elseif(!empty($pec))
            <div class="row"><span class="label">PEC:</span> <span class="value">{{ $pec }}</span></div>
        @endif
        @if(!empty($billAddress))
            <div class="row"><span class="label">Indirizzo:</span> <span class="value">{{ $billAddress }}</span></div>
        @endif
        @if($billCap || $billCity || $billProv)
            <div class="row"><span class="label">Località:</span>
                <span class="value">{{ trim(($billCap ?? '') . ' ' . ($billCity ?? '') . ($billProv ? ' (' . $billProv . ')' : '')) }}</span>
            </div>
        @endif
        @if($order->user?->email)
            <div class="row"><span class="label">Email:</span> <span class="value">{{ $order->user->email }}</span></div>
        @endif
    </div>

    {{-- ────────────────────── INDIRIZZI SPEDIZIONE (riepilogo) ────────────────────── --}}
    @php
        $firstPackage = $order->packages->first();
        $origin = $firstPackage?->originAddress;
        $destination = $firstPackage?->destinationAddress;
    @endphp
    @if($origin || $destination)
        <div class="order-ref">
            <strong>Spedizione:</strong>
            @if($origin)
                da {{ $origin->city }} ({{ $origin->postal_code }})
            @endif
            @if($destination)
                → a {{ $destination->city }} ({{ $destination->postal_code }})
            @endif
        </div>
    @endif

    {{-- ────────────────────── RIGHE FATTURA ────────────────────── --}}
    <h2 class="section-title">Dettaglio prestazioni</h2>
    @php
        $vatRate = (float) ($totals['iva_rate'] ?? 22);
        $rows = [];
        foreach ($order->packages as $idx => $pkg) {
            $totaleRiga = (int) ($pkg->getRawOriginal('single_price') ?? 0); // centesimi, IVA inclusa
            $imponibileRiga = (int) round($totaleRiga / (1 + $vatRate / 100));
            $ivaRiga = $totaleRiga - $imponibileRiga;
            $descrizione = trim(sprintf(
                'Spedizione %s — %s × %s × %s cm, %s kg',
                $pkg->package_type ?? 'Pacco',
                $pkg->first_size ?? '?',
                $pkg->second_size ?? '?',
                $pkg->third_size ?? '?',
                $pkg->weight ?? '?',
            ));
            $rows[] = [
                'n' => $idx + 1,
                'descrizione' => $descrizione,
                'qty' => (int) ($pkg->quantity ?? 1),
                'imponibile' => $imponibileRiga,
                'iva' => $ivaRiga,
                'totale' => $totaleRiga,
            ];
        }
    @endphp
    <table class="lines">
        <thead>
            <tr>
                <th class="center" style="width: 5%;">#</th>
                <th style="width: 47%;">Descrizione</th>
                <th class="center" style="width: 7%;">Q.tà</th>
                <th class="num" style="width: 14%;">Imponibile</th>
                <th class="num" style="width: 9%;">IVA %</th>
                <th class="num" style="width: 18%;">Totale</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="center">{{ $row['n'] }}</td>
                    <td>{{ $row['descrizione'] }}</td>
                    <td class="center">{{ $row['qty'] }}</td>
                    <td class="num">{{ number_format($row['imponibile'] / 100, 2, ',', '.') }} &euro;</td>
                    <td class="num">{{ number_format($vatRate, 0, ',', '.') }}%</td>
                    <td class="num">{{ number_format($row['totale'] / 100, 2, ',', '.') }} &euro;</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center" style="color: #9ca3af; padding: 14px;">Nessuna riga di dettaglio disponibile.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ────────────────────── TOTALI ────────────────────── --}}
    <div class="totals-wrap">
        <table class="totals">
            @if(!empty($totals['discount_cents']))
                <tr>
                    <td class="label">Totale lordo</td>
                    <td class="value">{{ number_format(($totals['gross_total_cents'] ?? ($totals['totale_cents'] + $totals['discount_cents'])) / 100, 2, ',', '.') }} &euro;</td>
                </tr>
                <tr>
                    <td class="label">Sconto</td>
                    <td class="value">-{{ number_format($totals['discount_cents'] / 100, 2, ',', '.') }} &euro;</td>
                </tr>
            @endif
            <tr>
                <td class="label">Imponibile</td>
                <td class="value">{{ number_format($totals['imponibile_cents'] / 100, 2, ',', '.') }} &euro;</td>
            </tr>
            <tr>
                <td class="label">IVA ({{ number_format($vatRate, 0, ',', '.') }}%)</td>
                <td class="value">{{ number_format($totals['iva_cents'] / 100, 2, ',', '.') }} &euro;</td>
            </tr>
            @if(!empty($bollo['applicabile']))
                <tr class="bollo">
                    <td class="label">Bollo virtuale</td>
                    <td class="value">{{ number_format($bollo['importo'], 2, ',', '.') }} &euro;</td>
                </tr>
            @endif
            <tr class="grand">
                <td>TOTALE FATTURA</td>
                <td class="value">{{ number_format($totals['totale_cents'] / 100, 2, ',', '.') }} &euro;</td>
            </tr>
        </table>
    </div>

    {{-- ────────────────────── NOTE LEGALI ────────────────────── --}}
    <div class="legal-notes">
        @php
            $paymentLabel = $pagamento['etichette'][$order->payment_method] ?? null;
        @endphp
        @if($paymentLabel)
            <p><strong>Modalità di pagamento:</strong> {{ $paymentLabel }} — pagamento ricevuto contestualmente all'emissione.</p>
        @else
            <p><strong>Modalità di pagamento:</strong> secondo accordi.</p>
        @endif

        @if(!empty($pagamento['iban']))
            <p><strong>Coordinate bancarie:</strong>
                {{ $pagamento['banca'] }} — IBAN: {{ $pagamento['iban'] }}
                @if(!empty($pagamento['swift']))
                    — SWIFT: {{ $pagamento['swift'] }}
                @endif
            </p>
        @endif

        @if(!empty($bollo['nota']))
            <p>{{ $bollo['nota'] }}</p>
        @endif

        <p>Operazione soggetta a IVA ai sensi del D.P.R. 633/1972 — Regime fiscale: <strong>{{ config('billing.regime_fiscale', 'RF01') }}</strong> (ordinario).</p>
        <p>Documento conforme al D.M. 17/06/2014 ai fini della conservazione decennale.</p>
    </div>

    {{-- ────────────────────── FOOTER ────────────────────── --}}
    <div class="footer">
        <span>{{ $cedente['ragione_sociale'] ?? 'SpedizioneFacile' }}</span>
        — P.IVA {{ $cedente['partita_iva'] ?? '—' }}
        — {{ $cedente['indirizzo'] ?? '' }}, {{ $cedente['cap'] ?? '' }} {{ $cedente['citta'] ?? '' }} ({{ $cedente['provincia'] ?? '' }})
        @if(!empty($cedente['email']))
            — {{ $cedente['email'] }}
        @endif
        @if(!empty($cedente['telefono']))
            — Tel. {{ $cedente['telefono'] }}
        @endif
    </div>

</body>
</html>
