<?php

/**
 * InvoicePdfService — Genera PDF ricevuta per un ordine di spedizione.
 * Endpoint: GET /api/orders/{id}/invoice (OrderDetailController).
 * Prezzi in centesimi (890 = 8,90 EUR), IVA 22%.
 *
 * Le primitive PDF (drawText/drawLine/...) vivono in InvoicePdfRenderer.
 */

namespace App\Services;

use App\Models\Order;
use App\Services\Invoice\InvoicePdfRenderer;

class InvoicePdfService
{
    private float $marginLeft = 50.0;

    private float $marginRight = 50.0;

    private InvoicePdfRenderer $r;

    public function __construct(?InvoicePdfRenderer $renderer = null)
    {
        $this->r = $renderer ?? new InvoicePdfRenderer;
    }

    /**
     * Genera il contenuto PDF della fattura per un ordine.
     */
    public function generate(Order $order): string
    {
        $order->loadMissing(['user', 'packages.originAddress', 'packages.destinationAddress']);

        $ops = '';
        $y = 50.0;
        $newY = 0.0;

        $ops .= $this->renderHeader($y);
        $y += 62;
        $ops .= $this->renderOrderDetails($order, $y, $newY);
        $y = $newY;
        $ops .= $this->renderCustomerData($order, $y, $newY);
        $y = $newY;
        $ops .= $this->renderShippingAddresses($order, $y, $newY);
        $y = $newY;
        $ops .= $this->renderPackagesTable($order, $y, $newY);
        $y = $newY;
        $ops .= $this->renderTotals($order, $y, $newY);
        $y = $newY;
        $ops .= $this->renderPaymentMethod($order, $y, $newY);
        $ops .= $this->renderFooter();

        return $this->r->buildPdfDocument($ops);
    }

    private function renderHeader(float $y): string
    {
        $r = $this->r;
        $ops = $r->drawText($r->pageWidth / 2, $y, 18, 'SpediamoFacile', 'F2', 'center');
        $ops .= $r->drawText($r->pageWidth / 2, $y + 22, 10, 'Ricevuta di spedizione', 'F1', 'center');
        $ops .= $r->drawLine($this->marginLeft, $y + 42, $r->pageWidth - $this->marginRight, $y + 42, 1.2);

        return $ops;
    }

    private function renderOrderDetails(Order $order, float $y, float &$newY): string
    {
        $r = $this->r;
        $ops = $r->drawText($this->marginLeft, $y, 11, 'Dettagli ordine', 'F2');
        $y += 18;

        $orderDate = $order->created_at ? $order->created_at->format('d/m/Y H:i') : 'n/d';
        $orderNumber = 'SF-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);

        $ops .= $r->drawText($this->marginLeft, $y, 9, 'Numero ordine:', 'F2');
        $ops .= $r->drawText($this->marginLeft + 100, $y, 9, $orderNumber, 'F1');
        $y += 14;
        $ops .= $r->drawText($this->marginLeft, $y, 9, 'Data:', 'F2');
        $ops .= $r->drawText($this->marginLeft + 100, $y, 9, $orderDate, 'F1');
        $y += 14;

        if ($order->brt_parcel_id) {
            $ops .= $r->drawText($this->marginLeft, $y, 9, 'Codice BRT:', 'F2');
            $ops .= $r->drawText($this->marginLeft + 100, $y, 9, (string) $order->brt_parcel_id, 'F1');
            $y += 14;
        }
        if ($order->brt_tracking_number) {
            $ops .= $r->drawText($this->marginLeft, $y, 9, 'Tracking:', 'F2');
            $ops .= $r->drawText($this->marginLeft + 100, $y, 9, (string) $order->brt_tracking_number, 'F1');
            $y += 14;
        }
        $newY = $y + 8;

        return $ops;
    }

    private function renderCustomerData(Order $order, float $y, float &$newY): string
    {
        $r = $this->r;
        $ops = $r->drawText($this->marginLeft, $y, 11, 'Dati cliente', 'F2');
        $y += 18;

        $userName = $order->user
            ? trim(($order->user->name ?? '').' '.($order->user->surname ?? ''))
            : '';

        $billingData = $order->billing_data;
        if (is_array($billingData) && ! empty($billingData)) {
            [$ops, $y] = $this->renderBillingDataLines($billingData, $userName, $ops, $y);
        } else {
            $ops .= $this->drawLabelValue($this->marginLeft, $y, 'Nome:', $userName);
            $y += 14;
            if ($order->user && $order->user->email) {
                $ops .= $this->drawLabelValue($this->marginLeft, $y, 'Email:', $order->user->email);
                $y += 14;
            }
        }

        $newY = (float) ($y + 8);

        return $ops;
    }

    /**
     * Supporta chiavi vecchie (name/vat_number/fiscal_code) e nuove
     * (F07: ragione_sociale/p_iva/codice_fiscale/codice_sdi/pec) con fallback.
     */
    private function renderBillingDataLines(array $billingData, string $userName, string $ops, float $y): array
    {
        $subjectType = $billingData['subject_type'] ?? ($billingData['is_business'] ?? false ? 'azienda' : 'privato');
        $isBusiness = $subjectType === 'azienda' || ($billingData['is_business'] ?? false) === true;

        $intestatario = $isBusiness
            ? ($billingData['ragione_sociale'] ?? $billingData['company_name'] ?? $billingData['name'] ?? $userName)
            : ($billingData['nome_completo'] ?? $billingData['name'] ?? $userName);

        $ops .= $this->drawLabelValue($this->marginLeft, $y, $isBusiness ? 'Ragione soc.:' : 'Nome:', (string) $intestatario);
        $y += 14;

        $fields = [
            ['P.IVA:', $billingData['p_iva'] ?? $billingData['vat_number'] ?? null],
            ['Cod. Fiscale:', $billingData['codice_fiscale'] ?? $billingData['fiscal_code'] ?? null],
        ];
        foreach ($fields as [$label, $value]) {
            if (! empty($value)) {
                $ops .= $this->drawLabelValue($this->marginLeft, $y, $label, (string) $value);
                $y += 14;
            }
        }

        $sdi = $billingData['codice_sdi'] ?? $billingData['sdi_code'] ?? null;
        if (! empty($sdi) && $sdi !== '0000000') {
            $ops .= $this->drawLabelValue($this->marginLeft, $y, 'Codice SDI:', (string) $sdi);
            $y += 14;
        }

        $pec = $billingData['pec'] ?? $billingData['pec_email'] ?? null;
        if (! empty($pec)) {
            $ops .= $this->drawLabelValue($this->marginLeft, $y, 'PEC:', (string) $pec);
            $y += 14;
        }

        $address = $billingData['indirizzo'] ?? $billingData['address'] ?? '';
        if (! empty($address)) {
            $ops .= $this->drawLabelValue($this->marginLeft, $y, 'Indirizzo:', (string) $address);
            $y += 14;
        }

        $cityLine = trim(($billingData['postal_code'] ?? '').' '.($billingData['city'] ?? '').' '.($billingData['province'] ?? ''));
        if ($cityLine !== '') {
            $ops .= $this->drawLabelValue($this->marginLeft, $y, 'Localita:', $cityLine);
            $y += 14;
        }

        return [$ops, $y];
    }

    private function renderShippingAddresses(Order $order, float $y, float &$newY): string
    {
        $r = $this->r;
        $first = $order->packages->first();
        $origin = $first?->originAddress;
        $destination = $first?->destinationAddress;

        if (! $origin && ! $destination) {
            $newY = $y;

            return '';
        }

        $ops = $r->drawText($this->marginLeft, $y, 11, 'Indirizzi spedizione', 'F2');
        $y += 18;

        foreach ([['MITTENTE', $origin], ['DESTINATARIO', $destination]] as [$label, $address]) {
            if (! $address) {
                continue;
            }
            $ops .= $r->drawText($this->marginLeft, $y, 9, $label, 'F2');
            $y += 14;
            $ops .= $r->drawText($this->marginLeft, $y, 9, $r->normalizeText($address->name ?? ''), 'F1');
            $y += 12;
            $ops .= $r->drawText($this->marginLeft, $y, 8, $r->normalizeText(($address->address ?? '').' '.($address->address_number ?? '')), 'F1');
            $y += 12;
            $ops .= $r->drawText($this->marginLeft, $y, 8, $r->normalizeText(($address->postal_code ?? '').' '.($address->city ?? '').' ('.($address->province ?? '').')'), 'F1');
            $y += 16;
        }

        $newY = $y + 4;

        return $ops;
    }

    private function renderPackagesTable(Order $order, float $y, float &$newY): string
    {
        $r = $this->r;
        $ops = $r->drawText($this->marginLeft, $y, 11, 'Pacchi', 'F2');
        $y += 18;

        $tableWidth = $r->pageWidth - $this->marginLeft - $this->marginRight;
        $ops .= $r->drawFilledRect($this->marginLeft, $y - 4, $tableWidth, 18, 0.93);

        $cols = [0, 80, 200, 330, 395];
        $ops .= $r->drawText($this->marginLeft + 4, $y + 8, 7.5, 'N.', 'F2');
        $ops .= $r->drawText($this->marginLeft + $cols[1], $y + 8, 7.5, 'Tipo', 'F2');
        $ops .= $r->drawText($this->marginLeft + $cols[2], $y + 8, 7.5, 'Dimensioni (cm)', 'F2');
        $ops .= $r->drawText($this->marginLeft + $cols[3], $y + 8, 7.5, 'Peso', 'F2');
        $ops .= $r->drawText($this->marginLeft + $cols[4], $y + 8, 7.5, 'Prezzo', 'F2');
        $y += 20;

        foreach ($order->packages as $index => $package) {
            $priceCents = $package->getRawOriginal('single_price') ?? 0;
            $priceStr = number_format($priceCents / 100, 2, ',', '.').' EUR';
            $dims = ($package->first_size ?? '?').' x '.($package->second_size ?? '?').' x '.($package->third_size ?? '?');

            $ops .= $r->drawText($this->marginLeft + 4, $y, 8, (string) ($index + 1), 'F1');
            $ops .= $r->drawText($this->marginLeft + $cols[1], $y, 8, $r->normalizeText($package->package_type ?? 'Pacco'), 'F1');
            $ops .= $r->drawText($this->marginLeft + $cols[2], $y, 8, $dims, 'F1');
            $ops .= $r->drawText($this->marginLeft + $cols[3], $y, 8, ($package->weight ?? '?').' kg', 'F1');
            $ops .= $r->drawText($this->marginLeft + $cols[4], $y, 8, $priceStr, 'F1');
            $y += 16;
        }

        $y += 4;
        $ops .= $r->drawLine($this->marginLeft, $y, $r->pageWidth - $this->marginRight, $y, 0.7);
        $newY = $y + 16;

        return $ops;
    }

    private function renderTotals(Order $order, float $y, float &$newY): string
    {
        $r = $this->r;
        $grossSubtotalCents = $order->grossSubtotalCents();
        $discountCents = $order->discountAmountCents();
        $subtotalCents = $order->payableTotalCents();
        $vatRate = 22;
        $imponibileCents = (int) round($subtotalCents / (1 + $vatRate / 100));
        $ivaCents = $subtotalCents - $imponibileCents;
        $rightX = $r->pageWidth - $this->marginRight;

        $ops = '';
        if ($discountCents > 0) {
            $ops .= $r->drawText($rightX - 180, $y, 9, 'Totale lordo:', 'F1');
            $ops .= $r->drawText($rightX, $y, 9, number_format($grossSubtotalCents / 100, 2, ',', '.').' EUR', 'F1', 'right');
            $y += 14;
            $ops .= $r->drawText($rightX - 180, $y, 9, 'Sconto:', 'F1');
            $ops .= $r->drawText($rightX, $y, 9, '-'.number_format($discountCents / 100, 2, ',', '.').' EUR', 'F1', 'right');
            $y += 14;
        }

        $ops .= $r->drawText($rightX - 180, $y, 9, 'Imponibile:', 'F1');
        $ops .= $r->drawText($rightX, $y, 9, number_format($imponibileCents / 100, 2, ',', '.').' EUR', 'F1', 'right');
        $y += 14;
        $ops .= $r->drawText($rightX - 180, $y, 9, 'IVA ('.$vatRate.'%):', 'F1');
        $ops .= $r->drawText($rightX, $y, 9, number_format($ivaCents / 100, 2, ',', '.').' EUR', 'F1', 'right');
        $y += 16;

        $ops .= $r->drawLine($rightX - 200, $y, $rightX, $y, 1.0);
        $y += 14;
        $ops .= $r->drawText($rightX - 180, $y, 12, 'TOTALE:', 'F2');
        $ops .= $r->drawText($rightX, $y, 12, number_format($subtotalCents / 100, 2, ',', '.').' EUR', 'F2', 'right');

        $newY = $y + 30;

        return $ops;
    }

    private function renderPaymentMethod(Order $order, float $y, float &$newY): string
    {
        $paymentMethod = $order->payment_method ?? null;
        if (! $paymentMethod) {
            $newY = $y;

            return '';
        }

        $methodLabels = [
            'stripe' => 'Carta di credito (Stripe)',
            'wallet' => 'Portafoglio virtuale',
            'bonifico' => 'Bonifico bancario',
        ];

        $ops = $this->drawLabelValue($this->marginLeft, $y, 'Pagamento:', $methodLabels[$paymentMethod] ?? $paymentMethod);
        $newY = $y + 20;

        return $ops;
    }

    private function renderFooter(): string
    {
        $r = $this->r;
        $footerY = $r->pageHeight - 60;
        $ops = $r->drawLine($this->marginLeft, $footerY, $r->pageWidth - $this->marginRight, $footerY, 0.7);
        $footerY += 14;
        $ops .= $r->drawText($r->pageWidth / 2, $footerY, 8, 'SpediamoFacile - Spedizioni semplici, veloci e convenienti', 'F1', 'center');
        $footerY += 12;
        $ops .= $r->drawText($r->pageWidth / 2, $footerY, 7, 'Documento generato automaticamente - Non costituisce fattura fiscale', 'F1', 'center');
        $footerY += 10;
        $ops .= $r->drawText($r->pageWidth / 2, $footerY, 7, 'assistenza@spediamofacile.it', 'F1', 'center');

        return $ops;
    }

    private function drawLabelValue(float $x, float $y, string $label, string $value): string
    {
        return $this->r->drawText($x, $y, 9, $label, 'F2')
            .$this->r->drawText($x + 100, $y, 9, $this->r->normalizeText($value), 'F1');
    }
}
