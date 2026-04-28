<?php

namespace App\Services\Security;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * SPRINT 6.7 — File Upload Security Hardening
 *
 * Sanitizza immagini caricate:
 *  1. Re-encoding via GD: strippa EXIF, ICC, commenti, payload nascosti.
 *     Un eventuale web-shell impacchettato nei metadati viene scartato
 *     perche' GD rigenera pixel puri e li riscrive come JPEG/PNG/WEBP.
 *  2. Resize conservativo se eccede max_side (default 2000px).
 *  3. Nome file hash-random (tramite hashName) — niente path traversal.
 *  4. Whitelist directory: solo dir dichiarate possono essere scritte.
 *
 * GD e' incluso di default nell'immagine PHP Laravel e nel Dockerfile
 * del progetto, nessuna dipendenza composer extra (Intervention NON
 * necessario).
 *
 * Uso tipico dal controller:
 *
 *   $path = app(ImageSanitizer::class)->sanitizeAndStore(
 *       $request->file('image'),
 *       'articles',
 *       'public'
 *   );
 */
class ImageSanitizer
{
    /**
     * Directory autorizzate a ricevere upload (path traversal guard).
     * Aggiungere qui ogni nuovo target.
     */
    public const ALLOWED_DIRECTORIES = [
        'attach',
        'articles',
        'promo',
        'homepage',
    ];

    /** Lato massimo in pixel: ridimensiona proporzionalmente oltre. */
    public const DEFAULT_MAX_SIDE = 2000;

    /** Qualita' JPEG/WEBP in riscrittura. */
    public const JPEG_QUALITY = 85;

    public const WEBP_QUALITY = 85;

    /**
     * Carica in memoria, strippa metadata, ridimensiona se serve, salva.
     *
     * @return string Path relativo nel disk (es. "articles/abc.jpg")
     */
    public function sanitizeAndStore(
        UploadedFile $file,
        string $directory,
        string $disk = 'public',
        int $maxSide = self::DEFAULT_MAX_SIDE
    ): string {
        $directory = $this->assertSafeDirectory($directory);

        $realMime = $this->detectRealMime($file);
        $filename = $file->hashName(); // hash casuale Laravel, sicuro

        // Forziamo l'estensione in base al MIME reale, NON al nome client.
        $filename = $this->ensureSafeExtension($filename, $realMime);

        $sanitizedBinary = $this->reencodeStrippingMetadata(
            $file->getRealPath(),
            $realMime,
            $maxSide
        );

        $path = trim($directory, '/') . '/' . $filename;
        Storage::disk($disk)->put($path, $sanitizedBinary);

        return $path;
    }

    /**
     * Valida che la directory target sia in whitelist (anti path traversal).
     */
    protected function assertSafeDirectory(string $directory): string
    {
        $directory = trim($directory, "/\\ \t\n");

        if ($directory === '' || str_contains($directory, '..') || str_contains($directory, "\0")) {
            throw new RuntimeException('Directory upload non valida.');
        }

        // Accettiamo sia "articles" sia "articles/2026" purche' la radice sia in whitelist.
        $root = explode('/', $directory, 2)[0];
        if (! in_array($root, self::ALLOWED_DIRECTORIES, true)) {
            throw new RuntimeException('Directory upload non autorizzata: ' . $root);
        }

        return $directory;
    }

    /**
     * Magic byte MIME reale via finfo. Ridondante col FormRequest, ma
     * difesa in profondita' se qualcuno chiama il sanitizer da altro
     * entry point.
     */
    protected function detectRealMime(UploadedFile $file): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('finfo non disponibile.');
        }

        $mime = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (! in_array($mime, $allowed, true)) {
            throw new RuntimeException('Tipo file non consentito: ' . $mime);
        }

        return $mime;
    }

    /**
     * Forza un'estensione sicura coerente col MIME reale.
     */
    protected function ensureSafeExtension(string $filename, string $realMime): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        $ext = $map[$realMime] ?? 'bin';
        $base = pathinfo($filename, PATHINFO_FILENAME);

        // Rimuoviamo qualunque cosa non sia hex/alfanumerico (hashName
        // produce hex, ma siamo paranoici).
        $base = preg_replace('/[^A-Za-z0-9_\-]/', '', $base);
        if ($base === '' || $base === null) {
            $base = bin2hex(random_bytes(16));
        }

        return $base . '.' . $ext;
    }

    /**
     * Re-encoding GD: legge pixel, li riscrive puliti. Elimina EXIF,
     * payload PHP embedded, stego grossolana, commenti JPEG.
     */
    protected function reencodeStrippingMetadata(string $sourcePath, string $mime, int $maxSide): string
    {
        if (! function_exists('imagecreatefromjpeg')) {
            throw new RuntimeException('Estensione GD non disponibile.');
        }

        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            default => false,
        };

        if ($src === false) {
            throw new RuntimeException('Immagine non decodificabile.');
        }

        try {
            $width = imagesx($src);
            $height = imagesy($src);

            $resized = $this->maybeResize($src, $width, $height, $maxSide);

            ob_start();
            try {
                $ok = match ($mime) {
                    'image/jpeg' => imagejpeg($resized, null, self::JPEG_QUALITY),
                    'image/png' => imagepng($resized, null, 6),
                    'image/webp' => imagewebp($resized, null, self::WEBP_QUALITY),
                    default => false,
                };

                if ($ok === false) {
                    throw new RuntimeException('Re-encoding fallito.');
                }

                return (string) ob_get_contents();
            } finally {
                ob_end_clean();
                if ($resized !== $src) {
                    imagedestroy($resized);
                }
            }
        } finally {
            imagedestroy($src);
        }
    }

    /**
     * Se eccede maxSide, ridimensiona proporzionalmente. Altrimenti
     * ritorna la risorsa originale.
     */
    protected function maybeResize(\GdImage $src, int $width, int $height, int $maxSide): \GdImage
    {
        if ($width <= $maxSide && $height <= $maxSide) {
            return $src;
        }

        $ratio = min($maxSide / $width, $maxSide / $height);
        $newW = (int) round($width * $ratio);
        $newH = (int) round($height * $ratio);

        $dst = imagecreatetruecolor($newW, $newH);

        // Preserviamo trasparenza PNG/WEBP
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);

        return $dst;
    }
}
