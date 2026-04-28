<?php

namespace App\Services\Brt;

/**
 * Primitive PDF a basso livello (rendering, layout, testo, codici a barre).
 *
 * Estratto da BrtBordereauGenerator per ridurre il file a < 400 LOC e isolare
 * la generazione PDF "pura" dalla logica business della distinta/etichetta.
 *
 * Stato condiviso atteso (proprietà del trait usato dalla classe host):
 *   - array  $pages              array di { width, height, content }
 *   - float  $currentPageHeight  altezza pagina corrente per conversione coordinate
 *   - float  $labelWidth         dimensione etichetta in pt
 *   - float  $labelHeight        dimensione etichetta in pt
 *
 * I metodi con prefisso `_` sono pubblici intenzionalmente: il content stream
 * delle pagine usa Closure che li richiama dall'esterno della classe.
 */
trait PdfRenderingPrimitives
{
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
