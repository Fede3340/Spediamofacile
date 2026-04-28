<?php
namespace App\Services\Brt;

use App\Models\Order;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BrtBordereauGenerator
{
    /** Conversione cm -> pt PDF (1 cm = 28.3464567 pt). */
    private const CM_TO_PT = 28.3464567;

    /** A4 landscape (pt). */
    private float $a4lWidth = 842.0;

    private float $a4lHeight = 595.0;

    /** Etichetta 10 x 15 cm (pt). */
    private float $labelWidth;

    private float $labelHeight;

    /**
     * Pagine raccolte: ognuna { width, height, content }.
     */
    private array $pages = [];

    /**
     * Altezza della pagina che si sta attualmente costruendo (per conversione coordinate).
     */
    private float $currentPageHeight = 595.0;

    public function __construct()
    {
        $this->labelWidth = 10.0 * self::CM_TO_PT;   // ~283.46
        $this->labelHeight = 15.0 * self::CM_TO_PT;  // ~425.20
    }

    /* ============================================================
     |  BORDEREAU GIORNALIERO (DISTINTA RITIRO)
     * ============================================================*/

    /**
     * Costruisce il PDF della distinta giornaliera dei ritiri.
     *
     * @param  Carbon  $pickupDate  Data di ritiro (singolo giorno).
     * @param  Collection<int, Order>  $orders  Ordini con quel pickup_date.
     * @param  array  $sender  ['name', 'address', 'city_line', 'phone', 'piva'].
     */
    public function buildDailyBordereau(Carbon $pickupDate, Collection $orders, array $sender): string
    {
        $this->resetDocument();

        $pageW = $this->a4lWidth;
        $pageH = $this->a4lHeight;
        $marginX = 36.0;

        $totalPackages = $orders->sum(fn (Order $o) => (int) $o->packages->sum(fn (Package $p) => max(1, (int) ($p->quantity ?? 1))));
        $totalWeight = $orders->sum(fn (Order $o) => $o->packages->sum(fn (Package $p) => (float) $p->weight * max(1, (int) ($p->quantity ?? 1))));
        $borderoNumber = $pickupDate->format('Ymd');

        $cols = [
            ['label' => 'N.',           'key' => 'n',         'width' => 28,  'align' => 'center'],
            ['label' => 'Ordine',       'key' => 'code',      'width' => 78,  'align' => 'left'],
            ['label' => 'Tracking',     'key' => 'tracking',  'width' => 92,  'align' => 'left'],
            ['label' => 'Destinatario', 'key' => 'recipient', 'width' => 130, 'align' => 'left'],
            ['label' => 'Indirizzo',    'key' => 'address',   'width' => 150, 'align' => 'left'],
            ['label' => 'CAP / Citta',  'key' => 'city',      'width' => 110, 'align' => 'left'],
            ['label' => 'Peso (kg)',    'key' => 'weight',    'width' => 50,  'align' => 'right'],
            ['label' => 'Colli',        'key' => 'colli',     'width' => 36,  'align' => 'center'],
            ['label' => 'Servizi',      'key' => 'services',  'width' => 96,  'align' => 'left'],
        ];
        $tableWidth = (float) array_sum(array_column($cols, 'width'));
        $tableX = ($pageW - $tableWidth) / 2;

        $rowsPerPage = 18;
        $rowHeight = 20.0;
        $headerHeight = 22.0;

        $rows = $this->buildBordereauRows($orders);
        $rowChunks = array_chunk($rows, $rowsPerPage);
        if (empty($rowChunks)) {
            $rowChunks = [[]]; // pagina vuota se nessun ordine
        }
        $totalPages = count($rowChunks);

        foreach ($rowChunks as $pageIndex => $chunk) {
            $self = $this;
            $isFirst = $pageIndex === 0;
            $isLast = $pageIndex === $totalPages - 1;
            $pageNumber = $pageIndex + 1;

            $this->pushPage($pageW, $pageH, function () use (
                $self, $pageW, $pageH, $marginX, $pickupDate, $borderoNumber, $sender,
                $orders, $totalPackages, $totalWeight, $cols, $tableX, $tableWidth,
                $headerHeight, $rowHeight, $chunk, $isFirst, $isLast, $pageNumber, $totalPages
            ) {
                $ops = '';

                // Header pagina
                $ops .= $self->_setFillTeal();
                $ops .= $self->_drawText($pageW / 2, 36, 16, 'Distinta di ritiro BRT', 'F2', 'center');
                $ops .= $self->_setFillBlack();
                $ops .= $self->_drawText($marginX, 36, 10, 'Data ritiro: '.$pickupDate->format('d/m/Y'), 'F2', 'left');
                $ops .= $self->_drawText($pageW - $marginX, 36, 10, 'Bordero N. '.$borderoNumber, 'F2', 'right');
                $ops .= $self->_drawText($pageW - $marginX, 50, 8, 'Pagina '.$pageNumber.' di '.$totalPages, 'F1', 'right');

                $tableTop = 72.0;
                if ($isFirst) {
                    $boxTop = 64.0;
                    $boxH = 56.0;
                    $boxW = ($pageW - 2 * $marginX);
                    $ops .= $self->_drawRect($marginX, $boxTop, $boxW, $boxH, 0.5, 0.96);
                    $ops .= $self->_drawText($marginX + 8, $boxTop + 14, 9, 'MITTENTE', 'F2', 'left');
                    $ops .= $self->_drawText($marginX + 8, $boxTop + 28, 9,
                        ($sender['name'] ?? 'SpediamoFacile S.r.l.').'  |  '.($sender['address'] ?? ''), 'F1', 'left');
                    $ops .= $self->_drawText($marginX + 8, $boxTop + 42, 9,
                        ($sender['city_line'] ?? '').'  |  Tel '.($sender['phone'] ?? 'n/d').'  |  P.IVA '.($sender['piva'] ?? 'n/d'),
                        'F1', 'left');

                    $ops .= $self->_setFillTeal();
                    $ops .= $self->_drawText($pageW - $marginX - 8, $boxTop + 14, 9, 'TOTALI', 'F2', 'right');
                    $ops .= $self->_setFillBlack();
                    $ops .= $self->_drawText($pageW - $marginX - 8, $boxTop + 28, 9,
                        'Ordini: '.$orders->count().'   Colli: '.$totalPackages.'   Peso: '.number_format($totalWeight, 2, ',', '.').' kg',
                        'F1', 'right');

                    $tableTop = $boxTop + $boxH + 14.0;
                }

                // Header tabella
                $ops .= $self->_drawFilledRect($tableX, $tableTop, $tableWidth, $headerHeight, 0.92);
                $ops .= $self->_drawRect($tableX, $tableTop, $tableWidth, $headerHeight, 0.7);
                $cx = $tableX;
                foreach ($cols as $col) {
                    $ops .= $self->_setFillTeal();
                    if ($col['align'] === 'center') {
                        $ops .= $self->_drawText($cx + $col['width'] / 2, $tableTop + 14, 8, $col['label'], 'F2', 'center', $col['width'] - 6);
                    } elseif ($col['align'] === 'right') {
                        $ops .= $self->_drawText($cx + $col['width'] - 4, $tableTop + 14, 8, $col['label'], 'F2', 'right', $col['width'] - 6);
                    } else {
                        $ops .= $self->_drawText($cx + 4, $tableTop + 14, 8, $col['label'], 'F2', 'left', $col['width'] - 6);
                    }
                    $ops .= $self->_setFillBlack();
                    $cx += $col['width'];
                    if ($cx < $tableX + $tableWidth - 0.5) {
                        $ops .= $self->_drawLine($cx, $tableTop, $cx, $tableTop + $headerHeight, 0.4);
                    }
                }

                // Righe dati
                $ry = $tableTop + $headerHeight;
                foreach ($chunk as $i => $row) {
                    if ($i % 2 === 1) {
                        $ops .= $self->_drawFilledRect($tableX, $ry, $tableWidth, $rowHeight, 0.97);
                    }
                    $ops .= $self->_drawRect($tableX, $ry, $tableWidth, $rowHeight, 0.4);
                    $cx = $tableX;
                    foreach ($cols as $col) {
                        $value = (string) ($row[$col['key']] ?? '');
                        if ($col['align'] === 'center') {
                            $ops .= $self->_drawText($cx + $col['width'] / 2, $ry + 13, 8, $value, 'F1', 'center', $col['width'] - 6);
                        } elseif ($col['align'] === 'right') {
                            $ops .= $self->_drawText($cx + $col['width'] - 4, $ry + 13, 8, $value, 'F1', 'right', $col['width'] - 6);
                        } else {
                            $ops .= $self->_drawText($cx + 4, $ry + 13, 8, $value, 'F1', 'left', $col['width'] - 6);
                        }
                        $cx += $col['width'];
                        if ($cx < $tableX + $tableWidth - 0.5) {
                            $ops .= $self->_drawLine($cx, $ry, $cx, $ry + $rowHeight, 0.3);
                        }
                    }
                    $ry += $rowHeight;
                }

                // Footer firme (solo ultima pagina)
                if ($isLast) {
                    $footerTop = $pageH - 78;
                    $signW = 220.0;
                    $sigLeftX = $marginX;
                    $sigRightX = $pageW - $marginX - $signW;

                    $ops .= $self->_drawText($sigLeftX, $footerTop, 9, 'Firma mittente', 'F2', 'left');
                    $ops .= $self->_drawLine($sigLeftX, $footerTop + 28, $sigLeftX + $signW, $footerTop + 28, 0.6);

                    $ops .= $self->_drawText($sigRightX, $footerTop, 9, 'Firma autista BRT', 'F2', 'left');
                    $ops .= $self->_drawLine($sigRightX, $footerTop + 28, $sigRightX + $signW, $footerTop + 28, 0.6);

                    $ops .= $self->_drawText($pageW / 2, $pageH - 22, 7,
                        'Documento generato automaticamente da SpediamoFacile - Bordero '.$borderoNumber, 'F1', 'center');
                }

                return $ops;
            });
        }

        return $this->renderPdf();
    }

    /**
     * Costruisce le righe della tabella bordereau per la lista ordini fornita.
     */
    private function buildBordereauRows(Collection $orders): array
    {
        $rows = [];
        $i = 1;
        foreach ($orders as $order) {
            /** @var Order $order */
            $order->loadMissing(['packages.destinationAddress', 'packages.service']);

            $first = $order->packages->first();
            $dest = $first?->destinationAddress;
            $service = $first?->service;

            $colli = (int) $order->packages->sum(fn (Package $p) => max(1, (int) ($p->quantity ?? 1)));
            $weight = (float) $order->packages->sum(fn (Package $p) => (float) $p->weight * max(1, (int) ($p->quantity ?? 1)));

            $services = [];
            if ($order->is_cod) {
                $services[] = 'COD';
            }
            if ((int) ($order->insurance_amount_cents ?? 0) > 0) {
                $services[] = 'Assicurato';
            }
            if ($service && $service->service_type && strtolower((string) $service->service_type) !== 'nessuno') {
                $services[] = ucfirst((string) $service->service_type);
            }
            if ($order->brt_pudo_id) {
                $services[] = 'PUDO';
            }

            $rows[] = [
                'n' => (string) $i,
                'code' => 'SF-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                'tracking' => (string) ($order->brt_tracking_number ?? '-'),
                'recipient' => (string) ($dest?->name ?? '-'),
                'address' => trim(((string) ($dest?->address ?? '')).' '.((string) ($dest?->address_number ?? ''))),
                'city' => trim(((string) ($dest?->postal_code ?? '')).' '.((string) ($dest?->city ?? '')).' ('.((string) ($dest?->province ?? '')).')'),
                'weight' => number_format($weight, 2, ',', '.'),
                'colli' => (string) $colli,
                'services' => implode(', ', $services) ?: '-',
            ];
            $i++;
        }

        return $rows;
    }

    /* ============================================================
     |  ETICHETTE 10x15 CM (UNA PER COLLO)
     * ============================================================*/

    /**
     * Costruisce un singolo PDF con una pagina 10x15 cm per ogni collo dell'ordine.
     */
    public function buildOrderLabels(Order $order): string
    {
        $this->resetDocument();
        $order->loadMissing([
            'user',
            'packages.originAddress',
            'packages.destinationAddress',
            'packages.service',
        ]);

        $totalColli = (int) $order->packages->sum(fn (Package $p) => max(1, (int) ($p->quantity ?? 1)));
        if ($totalColli < 1) {
            $totalColli = 1;
        }

        $idx = 0;
        foreach ($order->packages as $package) {
            $qty = max(1, (int) ($package->quantity ?? 1));
            for ($k = 0; $k < $qty; $k++) {
                $idx++;
                $current = $idx;
                $self = $this;
                $this->pushPage($this->labelWidth, $this->labelHeight, function () use ($self, $order, $package, $current, $totalColli) {
                    return $self->_renderLabelStream($order, $package, $current, $totalColli);
                });
            }
        }

        // Edge case: ordine senza pacchi -> pagina informativa
        if (empty($this->pages)) {
            $self = $this;
            $this->pushPage($this->labelWidth, $this->labelHeight, function () use ($self) {
                return $self->_drawText($self->_labelWidth() / 2, $self->_labelHeight() / 2, 10,
                    'Nessun collo configurato per questo ordine', 'F1', 'center');
            });
        }

        return $this->renderPdf();
    }

    /**
     * Stream PDF di una singola etichetta. Pubblico (prefisso _) per accesso da Closure.
     *
     * @internal
     */
    public function _renderLabelStream(Order $order, Package $package, int $colloN, int $colloTot): string
    {
        $w = $this->labelWidth;
        $h = $this->labelHeight;
        $pad = 10.0;
        $ops = '';

        // Cornice esterna
        $ops .= $this->_drawRect(2, 2, $w - 4, $h - 4, 1.0);

        // Banda superiore: logo BRT + servizio
        $bandH = 36.0;
        $ops .= $this->_drawFilledRect(2, 2, $w - 4, $bandH, 0.10);
        $ops .= $this->_setFillWhite();
        $ops .= $this->_drawText($pad + 2, 16, 18, 'BRT', 'F2', 'left');
        $ops .= $this->_drawText($pad + 2, 28, 7, 'CORRIERE ESPRESSO', 'F1', 'left');

        $serviceLabel = $this->resolveServiceLabel($order, $package);
        $ops .= $this->_drawText($w - $pad - 2, 16, 11, $serviceLabel, 'F2', 'right');
        $ops .= $this->_drawText($w - $pad - 2, 28, 7, 'COLLO '.$colloN.' / '.$colloTot, 'F1', 'right');
        $ops .= $this->_setFillBlack();

        // Sezione Mittente (compatta)
        $y = $bandH + 8;
        $origin = $package->originAddress;
        $ops .= $this->_drawText($pad, $y, 7, 'MITTENTE', 'F2', 'left');
        $y += 10;
        $ops .= $this->_drawText($pad, $y, 8, (string) ($origin->name ?? '-'), 'F2', 'left', $w - 2 * $pad);
        $y += 11;
        $ops .= $this->_drawText($pad, $y, 7,
            trim(((string) ($origin->address ?? '')).' '.((string) ($origin->address_number ?? ''))),
            'F1', 'left', $w - 2 * $pad);
        $y += 10;
        $ops .= $this->_drawText($pad, $y, 7,
            trim(((string) ($origin->postal_code ?? '')).' '.((string) ($origin->city ?? '')).' ('.((string) ($origin->province ?? '')).')'),
            'F1', 'left', $w - 2 * $pad);

        // Linea separatore
        $y += 14;
        $ops .= $this->_drawLine($pad, $y, $w - $pad, $y, 0.6);
        $y += 6;

        // Sezione Destinatario (grande)
        $dest = $package->destinationAddress;
        $ops .= $this->_setFillTeal();
        $ops .= $this->_drawText($pad, $y, 8, 'DESTINATARIO', 'F2', 'left');
        $ops .= $this->_setFillBlack();
        $y += 14;
        $ops .= $this->_drawText($pad, $y, 13, (string) ($dest->name ?? '-'), 'F2', 'left', $w - 2 * $pad);
        $y += 16;
        $ops .= $this->_drawText($pad, $y, 11,
            trim(((string) ($dest->address ?? '')).' '.((string) ($dest->address_number ?? ''))),
            'F1', 'left', $w - 2 * $pad);
        $y += 14;
        $ops .= $this->_drawText($pad, $y, 14,
            trim(((string) ($dest->postal_code ?? '')).' '.((string) ($dest->city ?? ''))),
            'F2', 'left', $w - 2 * $pad);
        $y += 16;
        $ops .= $this->_drawText($pad, $y, 11,
            'Provincia: '.((string) ($dest->province ?? '-')),
            'F1', 'left', $w - 2 * $pad);
        $y += 14;
        $ops .= $this->_drawText($pad, $y, 10,
            'Tel: '.((string) ($dest->telephone_number ?? '-')),
            'F1', 'left', $w - 2 * $pad);

        // Box riepilogo: peso, colli, codice
        $y += 16;
        $boxH = 30.0;
        $ops .= $this->_drawRect($pad, $y, $w - 2 * $pad, $boxH, 0.5, 0.96);
        $ops .= $this->_drawText($pad + 6, $y + 12, 8, 'Peso', 'F1', 'left');
        $ops .= $this->_drawText($pad + 6, $y + 24, 11, number_format((float) $package->weight, 2, ',', '.').' kg', 'F2', 'left');
        $ops .= $this->_drawText($pad + 90, $y + 12, 8, 'Collo', 'F1', 'left');
        $ops .= $this->_drawText($pad + 90, $y + 24, 11, $colloN.' / '.$colloTot, 'F2', 'left');
        $ops .= $this->_drawText($w - $pad - 6, $y + 12, 8, 'Ordine', 'F1', 'right');
        $ops .= $this->_drawText($w - $pad - 6, $y + 24, 11,
            'SF-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT), 'F2', 'right');

        // Chip servizi attivi
        $y += $boxH + 8;
        $chips = [];
        if ($order->is_cod) {
            $chips[] = 'COD '.number_format((int) ($order->cod_amount ?? 0) / 100, 2, ',', '.').' EUR';
        }
        if ((int) ($order->insurance_amount_cents ?? 0) > 0) {
            $chips[] = 'ASSICURATO '.number_format((int) $order->insurance_amount_cents / 100, 2, ',', '.').' EUR';
        }
        if ($order->brt_pudo_id) {
            $chips[] = 'RITIRO PUDO';
        }
        $cx = $pad;
        foreach ($chips as $chip) {
            $chipW = $this->estimateTextWidth($chip, 7) + 12;
            if ($cx + $chipW > $w - $pad) {
                break;
            }
            $ops .= $this->_drawFilledRect($cx, $y, $chipW, 14, 0.88);
            $ops .= $this->_setFillTeal();
            $ops .= $this->_drawText($cx + 6, $y + 10, 7, $chip, 'F2', 'left');
            $ops .= $this->_setFillBlack();
            $cx += $chipW + 4;
        }

        // Barcode Code128 del tracking
        $tracking = (string) ($order->brt_tracking_number ?: ('SF'.str_pad((string) $order->id, 8, '0', STR_PAD_LEFT)));
        $barcodeBoxTop = $h - 90;
        $barcodeHeight = 50.0;
        $ops .= $this->renderCode128($pad, $barcodeBoxTop, $w - 2 * $pad, $barcodeHeight, $tracking);
        $ops .= $this->_drawText($w / 2, $barcodeBoxTop + $barcodeHeight + 12, 11, $tracking, 'F2', 'center');

        return $ops;
    }

    private function resolveServiceLabel(Order $order, Package $package): string
    {
        $srv = strtolower((string) ($package->service?->service_type ?? ''));
        return match ($srv) {
            'express' => 'EXPRESS',
            'economy' => 'ECONOMY',
            'standard' => 'STANDARD',
            default => $order->brt_service_type ? strtoupper((string) $order->brt_service_type) : 'STANDARD',
        };
    }

    /* ============================================================
     |  BARCODE CODE128B (RENDERING VETTORIALE BASE)
     * ============================================================*/

    /** Tabella pattern Code128 (108 simboli, 11 moduli ciascuno + STOP). */
    private const CODE128_PATTERNS = [
        '11011001100', '11001101100', '11001100110', '10010011000', '10010001100',
        '10001001100', '10011001000', '10011000100', '10001100100', '11001001000',
        '11001000100', '11000100100', '10110011100', '10011011100', '10011001110',
        '10111001100', '10011101100', '10011100110', '11001110010', '11001011100',
        '11001001110', '11011100100', '11001110100', '11101101110', '11101001100',
        '11100101100', '11100100110', '11101100100', '11100110100', '11100110010',
        '11011011000', '11011000110', '11000110110', '10100011000', '10001011000',
        '10001000110', '10110001000', '10001101000', '10001100010', '11010001000',
        '11000101000', '11000100010', '10110111000', '10110001110', '10001101110',
        '10111011000', '10111000110', '10001110110', '11101110110', '11010001110',
        '11000101110', '11011101000', '11011100010', '11011101110', '11101011000',
        '11101000110', '11100010110', '11101101000', '11101100010', '11100011010',
        '11101111010', '11001000010', '11110001010', '10100110000', '10100001100',
        '10010110000', '10010000110', '10000101100', '10000100110', '10110010000',
        '10110000100', '10011010000', '10011000010', '10000110100', '10000110010',
        '11000010010', '11001010000', '11110111010', '11000010100', '10001111010',
        '10100111100', '10010111100', '10010011110', '10111100100', '10011110100',
        '10011110010', '11110100100', '11110010100', '11110010010', '11011011110',
        '11011110110', '11110110110', '10101111000', '10100011110', '10001011110',
        '10111101000', '10111100010', '11110101000', '11110100010', '10111011110',
        '10111101110', '11101011110', '11110101110', '11010000100', '11010010000',
        '11010011100', '11000111010',
    ];

    private const CODE128_START_B = 104;

    private const CODE128_STOP = 106;

    /**
     * Disegna un barcode Code128B come rettangoli neri.
     */
    private function renderCode128(float $x, float $top, float $width, float $height, string $data): string
    {
        $clean = preg_replace('/[^\x20-\x7E]/', '', $data) ?? '';
        if ($clean === '') {
            $clean = 'EMPTY';
        }

        $codes = [self::CODE128_START_B];
        $checksum = self::CODE128_START_B;
        $weight = 1;
        foreach (str_split($clean) as $ch) {
            $code = ord($ch) - 32; // mappatura set B (32 -> 0)
            if ($code < 0 || $code > 95) {
                continue;
            }
            $codes[] = $code;
            $checksum += $code * $weight;
            $weight++;
        }
        $codes[] = $checksum % 103;
        $codes[] = self::CODE128_STOP;

        $bits = '';
        foreach ($codes as $c) {
            $bits .= self::CODE128_PATTERNS[$c] ?? '';
        }
        $bits .= '11'; // termination bar

        $totalModules = strlen($bits);
        if ($totalModules === 0) {
            return '';
        }
        $module = $width / $totalModules;
        $bottomY = $this->currentPageHeight - ($top + $height);

        $ops = "q\n0 0 0 rg\n";
        $cursor = $x;
        $i = 0;
        $len = strlen($bits);
        while ($i < $len) {
            $bit = $bits[$i];
            $j = $i;
            while ($j < $len && $bits[$j] === $bit) {
                $j++;
            }
            $runWidth = ($j - $i) * $module;
            if ($bit === '1') {
                $ops .= $this->format($cursor).' '.$this->format($bottomY).' '
                    .$this->format($runWidth).' '.$this->format($height)." re f\n";
            }
            $cursor += $runWidth;
            $i = $j;
        }
        $ops .= "Q\n";

        return $ops;
    }

    /* ============================================================
     |  MOTORE PDF MULTI-PAGINA
     * ============================================================*/

    private function resetDocument(): void
    {
        $this->pages = [];
    }

    /**
     * Costruisce una pagina settando l'altezza corrente prima del rendering del content stream.
     * Garantisce conversione coordinate top->bottom corretta per pagine di dimensione diversa.
     */
    private function pushPage(float $width, float $height, callable $contentBuilder): void
    {
        $previous = $this->currentPageHeight;
        $this->currentPageHeight = $height;
        try {
            $stream = $contentBuilder();
        } finally {
            $this->currentPageHeight = $previous;
        }
        $this->pages[] = [
            'width' => $width,
            'height' => $height,
            'content' => $stream,
        ];
    }

    private function renderPdf(): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $nextId = 5;
        $pageObjectIds = [];
        foreach ($this->pages as $page) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageObjectIds[] = $pageId;

            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R '
                .'/MediaBox [0 0 '.$this->format($page['width']).' '.$this->format($page['height']).'] '
                .'/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> '
                .'/Contents '.$contentId.' 0 R >>';
            $objects[$contentId] = '<< /Length '.strlen($page['content'])." >>\nstream\n".$page['content']."\nendstream";
        }

        $kids = implode(' ', array_map(fn ($id) => $id.' 0 R', $pageObjectIds));
        $objects[2] = '<< /Type /Pages /Kids ['.$kids.'] /Count '.count($pageObjectIds).' >>';

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$body."\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= "xref\n0 ".($maxId + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxId; $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i] ?? 0)."\n";
        }
        $pdf .= "trailer\n<< /Size ".($maxId + 1)." /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }

    /* ============================================================
     |  PRIMITIVE DI DISEGNO (top-down, prefisso _ = accessibili da Closure)
     * ============================================================*/

    /** @internal */
    public function _drawText(
        float $x,
        float $top,
        float $fontSize,
        string $text,
        string $font = 'F1',
        string $align = 'left',
        ?float $maxWidth = null,
    ): string {
        $text = $this->normalizeText($text);
        if ($text === '') {
            return '';
        }
        if ($maxWidth !== null) {
            $text = $this->fitTextToWidth($text, $fontSize, $maxWidth);
        }
        $width = $this->estimateTextWidth($text, $fontSize);
        if ($align === 'center') {
            $x -= $width / 2;
        } elseif ($align === 'right') {
            $x -= $width;
        }
        $y = $this->currentPageHeight - $top;

        return 'BT /'.$font.' '.$this->format($fontSize).' Tf 1 0 0 1 '
            .$this->format($x).' '.$this->format($y)
            .' Tm ('.$this->escapePdfText($text).") Tj ET\n";
    }

    /** @internal */
    public function _drawLine(float $x1, float $top1, float $x2, float $top2, float $lineWidth = 0.5): string
    {
        $h = $this->currentPageHeight;
        return $this->format($lineWidth).' w '
            .$this->format($x1).' '.$this->format($h - $top1).' m '
            .$this->format($x2).' '.$this->format($h - $top2)." l S\n";
    }

    /** @internal */
    public function _drawRect(float $x, float $top, float $width, float $height, float $lineWidth = 0.5, ?float $fillGray = null): string
    {
        $h = $this->currentPageHeight;
        $bottomY = $h - ($top + $height);
        $ops = '';
        if ($fillGray !== null) {
            $fillGray = max(0.0, min(1.0, $fillGray));
            $ops .= "q\n".$this->format($fillGray)." g\n"
                .$this->format($x).' '.$this->format($bottomY).' '
                .$this->format($width).' '.$this->format($height)." re f\nQ\n";
        }
        $ops .= $this->format($lineWidth).' w '
            .$this->format($x).' '.$this->format($bottomY).' '
            .$this->format($width).' '.$this->format($height)." re S\n";

        return $ops;
    }

    /** @internal */
    public function _drawFilledRect(float $x, float $top, float $width, float $height, float $gray = 0.95): string
    {
        $h = $this->currentPageHeight;
        $gray = max(0.0, min(1.0, $gray));
        $bottomY = $h - ($top + $height);

        return "q\n".$this->format($gray)." g\n"
            .$this->format($x).' '.$this->format($bottomY).' '
            .$this->format($width).' '.$this->format($height)." re f\nQ\n";
    }

    /** @internal */
    public function _setFillTeal(): string
    {
        return $this->format(9 / 255).' '.$this->format(88 / 255).' '.$this->format(102 / 255)." rg\n";
    }

    /** @internal */
    public function _setFillBlack(): string
    {
        return "0 0 0 rg\n";
    }

    /** @internal */
    public function _setFillWhite(): string
    {
        return "1 1 1 rg\n";
    }

    /** @internal */
    public function _labelWidth(): float
    {
        return $this->labelWidth;
    }

    /** @internal */
    public function _labelHeight(): float
    {
        return $this->labelHeight;
    }

    /* ============================================================
     |  HELPERS TESTO
     * ============================================================*/

    private function fitTextToWidth(string $text, float $fontSize, float $maxWidth): string
    {
        if ($this->estimateTextWidth($text, $fontSize) <= $maxWidth) {
            return $text;
        }
        $suffix = '...';
        $fitted = $text;
        while ($fitted !== '' && $this->estimateTextWidth($fitted.$suffix, $fontSize) > $maxWidth) {
            $fitted = substr($fitted, 0, -1);
        }
        return rtrim($fitted).$suffix;
    }

    private function estimateTextWidth(string $text, float $fontSize): float
    {
        return strlen($text) * $fontSize * 0.52;
    }

    private function normalizeText(string $text): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($normalized !== '' && function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
            if ($converted !== false) {
                $normalized = $converted;
            }
        }

        return preg_replace('/[^\x20-\x7E]/', ' ', $normalized) ?? '';
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $text);
    }

    private function format(float $value): string
    {
        return number_format($value, 3, '.', '');
    }
}
