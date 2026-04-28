<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * SPRINT 6.7 — File Upload Security Hardening
 *
 * FormRequest condiviso per TUTTI gli endpoint upload immagine admin.
 *
 * Protezioni implementate (OWASP File Upload Cheatsheet):
 *  - Estensione whitelist (mimes)
 *  - MIME dichiarato whitelist (mimetypes)
 *  - Magic byte reale verificato post-validate (withValidator)
 *  - Dimensioni min/max in pixel (evita pixel-flood 1x1 o bombe >10k)
 *  - Size max in KB (evita storage exhaustion)
 *  - required/file (evita null, array, path injection)
 *
 * La sanitizzazione filename (hashName) e lo stripping EXIF sono
 * delegati al controller via ImageSanitizer.
 *
 * Uso:
 *   public function uploadImage(ImageUploadRequest $request) { ... }
 *
 * Override campo:
 *   class HeroImageRequest extends ImageUploadRequest {
 *     protected function fieldName(): string { return 'hero_image'; }
 *     protected function maxKilobytes(): int { return 10240; }
 *   }
 */
class ImageUploadRequest extends FormRequest
{
    /**
     * MIME types accettati come magic byte reale.
     * Allineato a `mimetypes` rule qui sotto.
     */
    protected const ALLOWED_REAL_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Nome del campo file nel payload. Override nelle subclass se serve.
     */
    protected function fieldName(): string
    {
        return 'image';
    }

    /**
     * Il campo file e' obbligatorio? Override a false per PATCH parziali
     * (es. HomepageImageController che puo' aggiornare solo la config).
     */
    protected function fileIsRequired(): bool
    {
        return true;
    }

    /**
     * Dimensione massima in KB. Default 5MB.
     */
    protected function maxKilobytes(): int
    {
        return 5120;
    }

    /**
     * Larghezza minima in pixel.
     */
    protected function minWidth(): int
    {
        return 100;
    }

    /**
     * Altezza minima in pixel.
     */
    protected function minHeight(): int
    {
        return 100;
    }

    /**
     * Larghezza massima in pixel (anti-bomba pixel-flood).
     */
    protected function maxWidth(): int
    {
        return 6000;
    }

    /**
     * Altezza massima in pixel (anti-bomba pixel-flood).
     */
    protected function maxHeight(): int
    {
        return 6000;
    }

    public function authorize(): bool
    {
        // L'autorizzazione admin e' gia' gestita dal middleware CheckAdmin
        // sulle route admin. Qui ci fidiamo.
        return true;
    }

    public function rules(): array
    {
        $field = $this->fieldName();
        $required = $this->fileIsRequired() ? 'required' : 'nullable';

        return [
            $field => [
                $required,
                'file',
                // Blocca estensioni eseguibili rinominate (.php, .phtml, .phar, .html, .svg)
                'mimes:jpg,jpeg,png,webp',
                // MIME dichiarato dal browser deve essere immagine reale
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:' . $this->maxKilobytes(),
                sprintf(
                    'dimensions:min_width=%d,min_height=%d,max_width=%d,max_height=%d',
                    $this->minWidth(),
                    $this->minHeight(),
                    $this->maxWidth(),
                    $this->maxHeight()
                ),
            ],
        ];
    }

    public function messages(): array
    {
        $field = $this->fieldName();

        return [
            $field . '.required' => 'Immagine obbligatoria.',
            $field . '.file' => 'Payload non valido: atteso un file.',
            $field . '.mimes' => 'Formato non supportato. Usa JPG, PNG o WEBP.',
            $field . '.mimetypes' => 'Tipo MIME non valido.',
            $field . '.max' => 'File troppo grande (max ' . ($this->maxKilobytes() / 1024) . ' MB).',
            $field . '.dimensions' => sprintf(
                'Dimensioni non valide (min %dx%dpx, max %dx%dpx).',
                $this->minWidth(),
                $this->minHeight(),
                $this->maxWidth(),
                $this->maxHeight()
            ),
        ];
    }

    /**
     * Magic byte check reale via finfo: blocca file con MIME client
     * spoofato (es. .php rinominato .jpg con header fake). Questo e'
     * l'ultimo bastione prima dello storage.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->hasFile($this->fieldName())) {
                return;
            }

            $file = $this->file($this->fieldName());
            if (! $file || ! $file->isValid()) {
                $v->errors()->add($this->fieldName(), 'File corrotto o non caricato correttamente.');
                return;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo === false) {
                $v->errors()->add($this->fieldName(), 'Impossibile verificare il tipo di file.');
                return;
            }

            $realMime = finfo_file($finfo, $file->getRealPath());
            finfo_close($finfo);

            if (! in_array($realMime, self::ALLOWED_REAL_MIMES, true)) {
                $v->errors()->add(
                    $this->fieldName(),
                    'Il contenuto del file non corrisponde a un\'immagine valida.'
                );
            }
        });
    }

    /**
     * Risposta JSON consistente per i client (frontend Nuxt).
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validazione immagine fallita.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
