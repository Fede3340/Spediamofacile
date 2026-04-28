<?php

namespace App\Services;

/**
 * Generatore PDF minimale (senza dipendenze esterne) per bordero spedizione.
 * Output: una pagina A4 orizzontale, leggibile e con tabella operativa.
 */
class BorderoPdfBuilder
{
    private float $pageWidth = 842.0;   // A4 landscape width (pt)

    private float $pageHeight = 595.0;  // A4 landscape height (pt)

    public function build(array $data): string
    {
        $ops = '';

        $tableColumns = [
            ['label_lines' => ['Localita'],                'key' => 'localita',           'width' => 118.0, 'align' => 'left',   'header_align' => 'left'],
            ['label_lines' => ['Prov.'],                   'key' => 'prov',               'width' => 38.0,  'align' => 'center', 'header_align' => 'center'],
            ['label_lines' => ['LNA'],                     'key' => 'lna',                'width' => 42.0,  'align' => 'center', 'header_align' => 'center'],
            ['label_lines' => ['Rif. mittente', 'numerico'], 'key' => 'rif_num',           'width' => 86.0,  'align' => 'center', 'header_align' => 'center'],
            ['label_lines' => ['Rif. mittente', 'alfanum.'], 'key' => 'rif_alpha',         'width' => 96.0,  'align' => 'center', 'header_align' => 'center'],
            ['label_lines' => ['Cod', 'Bolla'],            'key' => 'cod_bolla',          'width' => 78.0,  'align' => 'center', 'header_align' => 'center'],
            ['label_lines' => ['Tipo', 'incasso/Cass'],    'key' => 'incasso',            'width' => 88.0,  'align' => 'center', 'header_align' => 'center'],
            ['label_lines' => ['Importo', 'C/assegno'],    'key' => 'importo_incasso',    'width' => 78.0,  'align' => 'right',  'header_align' => 'center'],
            ['label_lines' => ['Importo', 'da assicurare'], 'key' => 'importo_assicurare', 'width' => 90.0,  'align' => 'right',  'header_align' => 'center'],
            ['label_lines' => ['Colli'],                   'key' => 'colli',              'width' => 36.0,  'align' => 'center', 'header_align' => 'center'],
        ];

        $tableWidth = array_sum(array_column($tableColumns, 'width'));
        $tableX = ($this->pageWidth - $tableWidth) / 2;
        $tableTop = 132.0;
        $headerHeight = 30.0;
        $rowHeight = 26.0;

        $borderoDate = $this->normalizeText($data['bordero_date'] ?? '');
        $borderoNumber = $this->normalizeText($data['bordero_number'] ?? '');
        $borderoRef = $this->normalizeText($data['bordero_reference'] ?? '');

        $ops .= $this->drawText($this->pageWidth / 2, 44, 15, 'Bordero per BRT', 'F2', 'center');
        $ops .= $this->drawText(56, 72, 9, 'Bordero n. '.$borderoNumber, 'F2');
        $ops .= $this->drawText($this->pageWidth / 2, 72, 9, 'del '.$borderoDate, 'F2', 'center');
        $ops .= $this->drawText($this->pageWidth - 56, 72, 8, 'Rif. '.$borderoRef, 'F1', 'right');
        $ops .= $this->drawLine(46, 84, $this->pageWidth - 46, 84, 1.1);

        // Tabella principale (header + singola riga dati)
        $ops .= $this->drawFilledRect($tableX, $tableTop, $tableWidth, $headerHeight, 0.93);
        $ops .= $this->drawFilledRect($tableX, $tableTop + $headerHeight, $tableWidth, $rowHeight, 0.985);
        $ops .= $this->drawRect($tableX, $tableTop, $tableWidth, $headerHeight + $rowHeight, 0.9);
        $ops .= $this->drawLine($tableX, $tableTop + $headerHeight, $tableX + $tableWidth, $tableTop + $headerHeight, 0.7);

        $currentX = $tableX;
        foreach ($tableColumns as $column) {
            $headerAlign = $column['header_align'];
            $labelLines = $column['label_lines'];
            $headerLineTop = $tableTop + 11;
            foreach ($labelLines as $index => $line) {
                if ($headerAlign === 'center') {
                    $ops .= $this->drawText($currentX + ($column['width'] / 2), $headerLineTop + ($index * 8), 6.6, $line, 'F2', 'center', $column['width'] - 8);
                } else {
                    $ops .= $this->drawText($currentX + 4, $headerLineTop + ($index * 8), 6.6, $line, 'F2', 'left', $column['width'] - 8);
                }
            }

            $value = $this->normalizeText((string) ($data[$column['key']] ?? ''));
            if ($column['align'] === 'right') {
                $ops .= $this->drawText($currentX + $column['width'] - 4, $tableTop + $headerHeight + 16, 8, $value, 'F1', 'right', $column['width'] - 8);
            } elseif ($column['align'] === 'center') {
                $ops .= $this->drawText($currentX + ($column['width'] / 2), $tableTop + $headerHeight + 16, 8, $value, 'F1', 'center', $column['width'] - 8);
            } else {
                $ops .= $this->drawText($currentX + 4, $tableTop + $headerHeight + 16, 8, $value, 'F1', 'left', $column['width'] - 8);
            }

            $currentX += $column['width'];
            if ($currentX < ($tableX + $tableWidth - 0.5)) {
                $ops .= $this->drawLine($currentX, $tableTop, $currentX, $tableTop + $headerHeight + $rowHeight, 0.55);
            }
        }

        // Box info mittente/destinatario
        $detailsTop = $tableTop + $headerHeight + $rowHeight + 24;
        $detailsGap = 12.0;
        $detailsWidth = ($tableWidth - $detailsGap) / 2;
        $detailsHeight = 96.0;

        $leftX = $tableX;
        $rightX = $tableX + $detailsWidth + $detailsGap;

        $ops .= $this->drawRect($leftX, $detailsTop, $detailsWidth, $detailsHeight, 0.7);
        $ops .= $this->drawRect($rightX, $detailsTop, $detailsWidth, $detailsHeight, 0.7);

        $ops .= $this->drawText($leftX + 8, $detailsTop + 14, 9, 'Mittente', 'F2');
        $ops .= $this->drawText($leftX + 8, $detailsTop + 34, 8, 'Nome: '.$this->normalizeText($data['sender_name'] ?? ''), 'F1', 'left', $detailsWidth - 16);
        $ops .= $this->drawText($leftX + 8, $detailsTop + 50, 8, 'Indirizzo: '.$this->normalizeText($data['sender_address'] ?? ''), 'F1', 'left', $detailsWidth - 16);
        $ops .= $this->drawText($leftX + 8, $detailsTop + 66, 8, 'Localita: '.$this->normalizeText($data['sender_city_line'] ?? ''), 'F1', 'left', $detailsWidth - 16);
        $ops .= $this->drawText($leftX + 8, $detailsTop + 82, 8, 'Telefono: '.$this->normalizeText($data['sender_phone'] ?? 'n/d'), 'F1', 'left', $detailsWidth - 16);

        $ops .= $this->drawText($rightX + 8, $detailsTop + 14, 9, 'Destinatario', 'F2');
        $ops .= $this->drawText($rightX + 8, $detailsTop + 34, 8, 'Nome: '.$this->normalizeText($data['recipient_name'] ?? ''), 'F1', 'left', $detailsWidth - 16);
        $ops .= $this->drawText($rightX + 8, $detailsTop + 50, 8, 'Indirizzo: '.$this->normalizeText($data['recipient_address'] ?? ''), 'F1', 'left', $detailsWidth - 16);
        $ops .= $this->drawText($rightX + 8, $detailsTop + 66, 8, 'Localita: '.$this->normalizeText($data['recipient_city_line'] ?? ''), 'F1', 'left', $detailsWidth - 16);
        $ops .= $this->drawText($rightX + 8, $detailsTop + 82, 8, 'Telefono: '.$this->normalizeText($data['recipient_phone'] ?? 'n/d'), 'F1', 'left', $detailsWidth - 16);

        // Totali/footer
        $footerTop = $detailsTop + $detailsHeight + 34;
        $ops .= $this->drawLine(46, $footerTop, $this->pageWidth - 46, $footerTop, 1.1);
        $ops .= $this->drawText(46, $footerTop + 16, 8, 'Creato il: '.$this->normalizeText($data['created_at'] ?? ''), 'F1');
        $ops .= $this->drawText($this->pageWidth - 46, $footerTop + 16, 9, 'Totali colli: '.$this->normalizeText($data['colli'] ?? '0'), 'F2', 'right');

        return $this->buildPdfDocument($ops);
    }

    private function buildPdfDocument(string $contentStream): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.$this->format($this->pageWidth).' '.$this->format($this->pageHeight).'] /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>',
            4 => '<< /Length '.strlen($contentStream)." >>\nstream\n".$contentStream."\nendstream",
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            6 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $objectContent) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$objectContent."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= 'xref'."\n";
        $pdf .= '0 '.(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i] ?? 0)."\n";
        }

        $pdf .= "trailer\n";
        $pdf .= '<< /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\n";
        $pdf .= "startxref\n";
        $pdf .= $xrefOffset."\n";
        $pdf .= '%%EOF';

        return $pdf;
    }

    private function drawText(
        float $x,
        float $top,
        float $fontSize,
        string $text,
        string $font = 'F1',
        string $align = 'left',
        ?float $maxWidth = null
    ): string {
        $text = $this->normalizeText($text);
        if ($maxWidth !== null) {
            $text = $this->fitTextToWidth($text, $fontSize, $maxWidth);
        }

        $textWidth = $this->estimateTextWidth($text, $fontSize);
        if ($align === 'center') {
            $x -= ($textWidth / 2);
        } elseif ($align === 'right') {
            $x -= $textWidth;
        }

        return 'BT /'.$font.' '.$this->format($fontSize).' Tf 1 0 0 1 '
            .$this->format($x).' '.$this->format($this->toPdfY($top))
            .' Tm ('.$this->escapePdfText($text).") Tj ET\n";
    }

    private function drawLine(float $x1, float $top1, float $x2, float $top2, float $lineWidth = 0.7): string
    {
        return $this->format($lineWidth).' w '
            .$this->format($x1).' '.$this->format($this->toPdfY($top1)).' m '
            .$this->format($x2).' '.$this->format($this->toPdfY($top2))." l S\n";
    }

    private function drawRect(float $x, float $top, float $width, float $height, float $lineWidth = 0.7): string
    {
        $bottomY = $this->toPdfY($top + $height);

        return $this->format($lineWidth).' w '
            .$this->format($x).' '.$this->format($bottomY).' '
            .$this->format($width).' '.$this->format($height)." re S\n";
    }

    private function drawFilledRect(float $x, float $top, float $width, float $height, float $gray = 0.95): string
    {
        $gray = max(0.0, min(1.0, $gray));
        $bottomY = $this->toPdfY($top + $height);

        return "q\n"
            .$this->format($gray)." g\n"
            .$this->format($x).' '.$this->format($bottomY).' '
            .$this->format($width).' '.$this->format($height)." re f\n"
            ."Q\n";
    }

    private function toPdfY(float $top): float
    {
        return $this->pageHeight - $top;
    }

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

        // Manteniamo solo caratteri stampabili ASCII per evitare problemi con font base PDF.
        return preg_replace('/[^\x20-\x7E]/', ' ', $normalized) ?? '';
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\(', '\)'],
            $text
        );
    }

    private function format(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
