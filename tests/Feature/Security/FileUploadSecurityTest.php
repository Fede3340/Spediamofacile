<?php

namespace Tests\Feature\Security;

use App\Models\Article;
use App\Models\User;
use App\Services\Security\ImageSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File as TestingFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * SPRINT 6.7 — File Upload Security Hardening tests.
 *
 * Verifica che gli endpoint admin upload respingano:
 *  - File PHP rinominato in .jpg (magic byte check)
 *  - File oversize (> max KB)
 *  - Dimensioni invalide (< min / > max)
 *  - Path traversal nel nome file (hashName garantisce nome random)
 *  - Payload EXIF nascosto (re-encoding GD strippa metadata)
 */
class FileUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD non disponibile nell\'ambiente di test.');
        }
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        return $admin;
    }

    /**
     * Genera un JPEG reale (non placeholder testuale) di WxH pixel.
     * Necessario perche' il FormRequest usa `dimensions` e `image`
     * rules che leggono i pixel con GD.
     */
    private function genuineJpeg(int $width = 800, int $height = 600): UploadedFile
    {
        return UploadedFile::fake()->image('genuine.jpg', $width, $height);
    }

    public function test_rejects_php_payload_renamed_to_jpg(): void
    {
        $this->actingAsAdmin();
        $article = Article::create([
            'title' => 'Test', 'slug' => 'test', 'type' => 'guide', 'is_published' => false,
        ]);

        // File con magic byte non-immagine (<?php) ma nome/MIME spoofati
        $maliciousContent = "<?php echo 'pwned'; ?>\n" . str_repeat('A', 1024);
        $malicious = TestingFile::createWithContent('shell.jpg', $maliciousContent);

        $response = $this->post(
            "/api/admin/articles/{$article->id}/upload-image",
            ['image' => $malicious],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        // Il payload NON deve esistere nello storage
        $this->assertEmpty(Storage::disk('public')->files('articles'));
    }

    public function test_rejects_oversize_file(): void
    {
        $this->actingAsAdmin();
        $article = Article::create([
            'title' => 'Test', 'slug' => 'test-2', 'type' => 'guide', 'is_published' => false,
        ]);

        // 6 MB (sopra il limite 5 MB)
        $file = UploadedFile::fake()->image('huge.jpg', 1200, 800)->size(6 * 1024);

        $response = $this->post(
            "/api/admin/articles/{$article->id}/upload-image",
            ['image' => $file],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    public function test_rejects_invalid_dimensions_too_small(): void
    {
        $this->actingAsAdmin();
        $article = Article::create([
            'title' => 'Test', 'slug' => 'test-3', 'type' => 'guide', 'is_published' => false,
        ]);

        // 50x50: sotto min_width=100
        $file = UploadedFile::fake()->image('tiny.jpg', 50, 50);

        $response = $this->post(
            "/api/admin/articles/{$article->id}/upload-image",
            ['image' => $file],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    public function test_rejects_invalid_dimensions_too_large(): void
    {
        $this->actingAsAdmin();
        $article = Article::create([
            'title' => 'Test', 'slug' => 'test-4', 'type' => 'guide', 'is_published' => false,
        ]);

        // 7000x7000: sopra max_width=6000 (pixel-flood)
        $file = UploadedFile::fake()->image('bomb.jpg', 7000, 7000);

        $response = $this->post(
            "/api/admin/articles/{$article->id}/upload-image",
            ['image' => $file],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    public function test_valid_upload_succeeds_and_sanitizes_filename(): void
    {
        $this->actingAsAdmin();
        $article = Article::create([
            'title' => 'Test', 'slug' => 'test-5', 'type' => 'guide', 'is_published' => false,
        ]);

        // Nome pericoloso: path traversal + double extension
        $file = UploadedFile::fake()->image('../../../etc/passwd.jpg', 800, 600);

        $response = $this->post(
            "/api/admin/articles/{$article->id}/upload-image",
            ['image' => $file],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Il file salvato deve essere in articles/ con nome hash random,
        // mai con ".." o "passwd"
        $files = Storage::disk('public')->files('articles');
        $this->assertCount(1, $files);
        $this->assertStringNotContainsString('..', $files[0]);
        $this->assertStringNotContainsString('passwd', $files[0]);
        $this->assertStringNotContainsString('etc', $files[0]);
        $this->assertStringStartsWith('articles/', $files[0]);
        // Estensione forzata a jpg (non .php, .html, ecc.)
        $this->assertMatchesRegularExpression('/\.(jpg|png|webp)$/', $files[0]);
    }

    public function test_sanitizer_rejects_directory_outside_whitelist(): void
    {
        $sanitizer = app(ImageSanitizer::class);
        $file = $this->genuineJpeg();

        $this->expectException(\RuntimeException::class);
        $sanitizer->sanitizeAndStore($file, '../etc', 'public');
    }

    public function test_sanitizer_rejects_path_traversal_in_directory(): void
    {
        $sanitizer = app(ImageSanitizer::class);
        $file = $this->genuineJpeg();

        $this->expectException(\RuntimeException::class);
        $sanitizer->sanitizeAndStore($file, 'articles/../../../tmp', 'public');
    }

    public function test_sanitizer_strips_metadata_by_reencoding(): void
    {
        $sanitizer = app(ImageSanitizer::class);

        // Creiamo JPEG con "commento" embedded nei byte grezzi (simula EXIF payload)
        $tmp = tempnam(sys_get_temp_dir(), 'exif') . '.jpg';
        $im = imagecreatetruecolor(400, 300);
        imagefilledrectangle($im, 0, 0, 400, 300, imagecolorallocate($im, 10, 20, 30));
        imagejpeg($im, $tmp, 90);
        imagedestroy($im);

        // Appendiamo un "payload" dopo la fine del JPEG (classico smuggling)
        file_put_contents($tmp, "MALICIOUS_PAYLOAD_<?php phpinfo();?>", FILE_APPEND);

        $sizeBefore = filesize($tmp);
        $upload = new UploadedFile($tmp, 'x.jpg', 'image/jpeg', null, true);

        $path = $sanitizer->sanitizeAndStore($upload, 'articles', 'public');
        $sanitizedContent = Storage::disk('public')->get($path);

        // Il contenuto salvato NON deve contenere il payload originale
        $this->assertStringNotContainsString('MALICIOUS_PAYLOAD', $sanitizedContent);
        $this->assertStringNotContainsString('phpinfo', $sanitizedContent);
        // E dev'essere piu' piccolo o uguale al file originale con payload
        $this->assertLessThanOrEqual($sizeBefore, strlen($sanitizedContent));
    }

    public function test_homepage_upload_accepts_valid_image(): void
    {
        $this->actingAsAdmin();

        $file = UploadedFile::fake()->image('hero.jpg', 1920, 1080);

        $response = $this->post(
            '/api/admin/homepage-image',
            ['image' => $file],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $files = Storage::disk('public')->files('homepage');
        $this->assertCount(1, $files);
        $this->assertStringStartsWith('homepage/', $files[0]);
    }

    public function test_homepage_upload_rejects_svg_as_image_mime(): void
    {
        $this->actingAsAdmin();

        // SVG = XML, puo' contenere JS/XSS. NON nella whitelist.
        $svgContent = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $svg = TestingFile::createWithContent('xss.svg', $svgContent);

        $response = $this->post(
            '/api/admin/homepage-image',
            ['image' => $svg],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        $this->assertEmpty(Storage::disk('public')->files('homepage'));
    }
}
