<?php

declare(strict_types=1);

/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D Community Edition is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D Community Edition
 *
 * @author     HDVinnie <hdinnovations@protonmail.com>
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

namespace App\Http\Controllers\Staff;

use App\Enums\ModerationStatus;
use App\Helpers\SystemInformation;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\Unit3dAnnounce;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Intervention\Image\ImageManagerStatic as Image;
use Spatie\SslCertificate\SslCertificate;
use Exception;
use RuntimeException;
use Throwable;

/**
 * @see \Tests\Todo\Feature\Http\Controllers\Staff\HomeControllerTest
 */
class HomeController extends Controller
{
    /**
     * Display Staff Dashboard.
     *
     * @throws Exception
     */
    public function index(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        // SSL Info
        try {
            $certificate = $request->secure() ? SslCertificate::createForHostName(config('app.url')) : '';
        } catch (Exception) {
            $certificate = '';
        }

        // System Information
        $systemInformation = new SystemInformation();

        return view('Staff.dashboard.index', [
            'users' => cache()->flexible('dashboard_users', [60 * 5, 60 * 10], fn () => DB::table('users')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw('SUM(group_id = ?) AS banned', [Group::where('slug', '=', 'banned')->soleValue('id')])
                ->selectRaw('SUM(group_id = ?) AS validating', [Group::where('slug', '=', 'validating')->soleValue('id')])
                ->first()),
            'torrents' => cache()->flexible('dashboard_torrents', [60 * 5, 60 * 10], fn () => DB::table('torrents')
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw('SUM(status = 0) AS pending')
                ->selectRaw('SUM(status = 1) AS approved')
                ->selectRaw('SUM(status = 2) AS rejected')
                ->selectRaw('SUM(status = 3) AS postponed')
                ->first()),
            'peers' => cache()->flexible('dashboard_peers', [60 * 5, 60 * 10], fn () => DB::table('peers')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw('SUM(active = TRUE) AS active')
                ->selectRaw('SUM(active = FALSE) AS inactive')
                ->selectRaw('SUM(seeder = FALSE AND active = TRUE) AS leechers')
                ->selectRaw('SUM(seeder = TRUE AND active = TRUE) AS seeders')
                ->first()),
            'unsolvedReportsCount' => DB::table('reports')
                ->whereNull('snoozed_until')
                ->whereNull('solved_by')
                ->where(fn ($query) => $query->whereNull('assigned_to')->orWhere('assigned_to', '=', auth()->id()))
                ->count(),
            'pendingApplicationsCount' => DB::table('applications')->where('status', '=', ModerationStatus::PENDING)->count(),
            'certificate'              => $certificate,
            'uptime'                   => $systemInformation->uptime(),
            'ram'                      => $systemInformation->memory(),
            'disk'                     => $systemInformation->disk(),
            'avg'                      => $systemInformation->avg(),
            'basic'                    => $systemInformation->basic(),
            'file_permissions'         => $systemInformation->directoryPermissions(),
            'externalTrackerStats'     => Unit3dAnnounce::getStats(),
            'siteServices'             => $this->siteServicesPayload(),
        ]);
    }

    public function services(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.dashboard.services', [
            'siteServices' => $this->siteServicesPayload(),
        ]);
    }

    public function theme(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.dashboard.theme', [
            'themeAssets' => [
                'banner_url' => $this->resolveThemeAssetUrl('site-banner', asset('img/auth/The_Void_Login_Page.png')),
                'background_url' => $this->resolveThemeAssetUrl('site-background', asset('img/auth/The_Void_Login_Page.png')),
            ],
        ]);
    }

    public function twoFactor(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.dashboard.two-factor', [
            'twoFactorSettings' => [
                'force_2fa' => (bool) config('fortify.force_2fa'),
                'issuer'    => (string) config('fortify.two_factor_issuer', config('app.name')),
            ],
        ]);
    }

    public function sitePolicy(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.dashboard.site-policy', [
            'sitePolicySettings' => [
                'open_registration' => ! (bool) config('other.invite-only'),
                'freeleech'         => (bool) config('other.freeleech'),
            ],
        ]);
    }

    /**
     * Update the site header banner image shown above the top navigation.
     */
    public function updateBanner(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_banner' => ['required', 'file', 'image', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/bmp', 'max:51200'],
        ]);

        $directory = public_path('img/auth');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (! File::isWritable($directory)) {
            return to_route('staff.dashboard.index')->withErrors([
                'site_banner' => 'Banner directory is not writable: '.$directory,
            ]);
        }

        $this->assertImageProcessingAvailable();

        /** @var \Illuminate\Http\UploadedFile $banner */
        $banner = $validated['site_banner'];

        $outputPath = $directory.DIRECTORY_SEPARATOR.'The_Void_Login_Page.png';
        File::delete($outputPath);

        $this->processTransparentBannerCanvas(
            $banner,
            $directory.DIRECTORY_SEPARATOR.'The_Void_Login_Page',
            2400,
            520
        );

        @touch($outputPath);

        return to_route('staff.dashboard.index')->with('success', 'Site banner updated successfully.');
    }

    /**
     * Update frequently changed site and service settings from dashboard.
     */
    public function updateSiteServices(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name'         => ['required', 'string', 'max:100'],
            'site_title'        => ['required', 'string', 'max:100'],
            'site_subtitle'     => ['nullable', 'string', 'max:120'],
            'site_url'          => ['required', 'url', 'max:255'],
            'mail_mailer'       => ['required', 'string', Rule::in(['smtp', 'sendmail', 'mailgun', 'ses', 'postmark', 'log', 'array', 'failover'])],
            'owner_email'       => ['nullable', 'email', 'max:255'],
            'mail_host'         => ['nullable', 'string', 'max:255'],
            'mail_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username'     => ['nullable', 'string', 'max:255'],
            'mail_password'     => ['nullable', 'string', 'max:255'],
            'clear_mail_password' => ['nullable', 'boolean'],
            'mail_encryption'   => ['nullable', 'string', Rule::in(['', 'tls', 'ssl'])],
            'mail_allow_self_signed' => ['nullable', 'boolean'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name'     => ['nullable', 'string', 'max:255'],
            'mail_sendmail_path' => ['nullable', 'string', 'max:500'],
        ]);

        $envValues = [
            'APP_NAME'           => $validated['site_name'],
            'APP_URL'            => $validated['site_url'],
            'SITE_TITLE'         => $validated['site_title'],
            'SITE_SUBTITLE'      => (string) ($validated['site_subtitle'] ?? ''),
            'MAIL_MAILER'        => $validated['mail_mailer'],
            'DEFAULT_OWNER_EMAIL' => (string) ($validated['owner_email'] ?? ''),
            'MAIL_HOST'          => (string) ($validated['mail_host'] ?? ''),
            'MAIL_PORT'          => (string) ($validated['mail_port'] ?? ''),
            'MAIL_USERNAME'      => (string) ($validated['mail_username'] ?? ''),
            'MAIL_ENCRYPTION'    => $validated['mail_encryption'] ?? '',
            'MAIL_ALLOW_SELF_SIGNED' => $request->boolean('mail_allow_self_signed') ? 'true' : 'false',
            'MAIL_VERIFY_PEER'      => $request->boolean('mail_allow_self_signed') ? 'false' : 'true',
            'MAIL_VERIFY_PEER_NAME' => $request->boolean('mail_allow_self_signed') ? 'false' : 'true',
            'MAIL_FROM_ADDRESS'  => (string) ($validated['mail_from_address'] ?? ''),
            'MAIL_FROM_NAME'     => (string) ($validated['mail_from_name'] ?? ''),
            'MAIL_SENDMAIL_PATH' => (string) ($validated['mail_sendmail_path'] ?? '/usr/sbin/sendmail -bs -i'),
        ];

        if ($request->boolean('clear_mail_password')) {
            $envValues['MAIL_PASSWORD'] = '';
        } elseif (! empty($validated['mail_password']) && $validated['mail_password'] !== '********') {
            $envValues['MAIL_PASSWORD'] = $validated['mail_password'];
        }

        $this->writeEnvValues($envValues);

        try {
            Artisan::call('optimize:clear');
        } catch (Throwable $throwable) {
            Log::warning('Failed clearing caches after site services update', [
                'message' => $throwable->getMessage(),
            ]);
        }

        return to_route('staff.dashboard.services.index')->with('success', 'Site services updated and persisted to environment configuration.');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'theme_banner'     => ['nullable', 'file', 'image', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/bmp', 'max:51200'],
                'theme_background' => ['nullable', 'file', 'image', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/bmp', 'max:51200'],
            ],
            [
                'theme_banner.image' => 'Banner must be a valid image file.',
                'theme_banner.mimetypes' => 'Banner must be one of: JPG, PNG, WEBP, GIF, BMP.',
                'theme_banner.max' => 'Banner must be 50MB or smaller.',
                'theme_background.image' => 'Background must be a valid image file.',
                'theme_background.mimetypes' => 'Background must be one of: JPG, PNG, WEBP, GIF, BMP.',
                'theme_background.max' => 'Background must be 50MB or smaller.',
            ]
        );

        $debugContext = $this->buildThemeUploadDebugContext($request);

        if ($validator->fails()) {
            return to_route('staff.dashboard.theme.index')
                ->withErrors($validator)
                ->with('theme_upload_debug', $debugContext)
                ->with('theme_upload_message', 'Validation failed before image processing started.');
        }

        /** @var array<string,\Illuminate\Http\UploadedFile> $validated */
        $validated = $validator->validated();

        $contentLength = (int) ($request->server('CONTENT_LENGTH') ?? 0);
        $postMaxSizeBytes = $this->parseIniSizeToBytes((string) ini_get('post_max_size'));
        if ($contentLength > 0 && $postMaxSizeBytes > 0 && $contentLength > $postMaxSizeBytes) {
            return to_route('staff.dashboard.theme.index')
                ->withErrors([
                    'theme_background' => 'Request payload exceeded PHP post_max_size ('.ini_get('post_max_size').').',
                ])
                ->with('theme_upload_debug', $debugContext)
                ->with('theme_upload_message', 'Upload rejected by PHP before Laravel received the files.');
        }

        if (! isset($validated['theme_banner']) && ! isset($validated['theme_background'])) {
            return to_route('staff.dashboard.theme.index')
                ->withErrors(['theme_banner' => 'Upload at least one image to update the theme.'])
                ->with('theme_upload_debug', $debugContext)
                ->with('theme_upload_message', 'No files were attached in the request.');
        }

        $directory = public_path('img/theme');
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (! File::isWritable($directory)) {
            return to_route('staff.dashboard.theme.index')
                ->withErrors(['theme_background' => 'Theme directory is not writable: '.$directory])
                ->with('theme_upload_debug', $debugContext)
                ->with('theme_upload_message', 'Filesystem permissions blocked the upload.');
        }

        $uploadErrors = [];
        $processedTargets = [];
        if (isset($validated['theme_banner'])) {
            try {
                $bannerResult = $this->processTransparentBannerCanvas(
                    $validated['theme_banner'],
                    $directory.DIRECTORY_SEPARATOR.'site-banner',
                    2400,
                    520
                );
                $processedTargets[] = 'site-banner ('.$bannerResult['saved_as'].')';
            } catch (Throwable $throwable) {
                $uploadErrors['theme_banner'] = 'Banner upload failed during processing. '.$throwable->getMessage();
            }
        }

        if (isset($validated['theme_background'])) {
            try {
                $backgroundResult = $this->processThemeImage(
                    $validated['theme_background'],
                    $directory.DIRECTORY_SEPARATOR.'site-background',
                    2560,
                    1440
                );
                $processedTargets[] = 'site-background ('.$backgroundResult['saved_as'].')';
            } catch (Throwable $throwable) {
                $uploadErrors['theme_background'] = 'Background upload failed during processing. '.$throwable->getMessage();
            }
        }

        if ($uploadErrors !== []) {
            Log::error('Theme upload failed', [
                'errors' => $uploadErrors,
                'debug' => $debugContext,
            ]);

            return to_route('staff.dashboard.theme.index')
                ->withErrors($uploadErrors)
                ->with('theme_upload_debug', $debugContext)
                ->with('theme_upload_message', 'Upload reached image processing but one or more files failed.');
        }

        return to_route('staff.dashboard.theme.index')
            ->with('success', 'Theme assets updated successfully.')
            ->with('theme_upload_message', 'Theme upload completed and images were resized to fixed dimensions.')
            ->with('theme_upload_debug', array_merge($debugContext, [
                'result' => [
                    'processed_targets' => $processedTargets,
                    'storage_directory' => $directory,
                ],
            ]));
    }

    public function updateTwoFactor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'force_2fa' => ['nullable', 'boolean'],
            'issuer'    => ['required', 'string', 'max:120'],
        ]);

        $this->writeEnvValues([
            'FORCE_2FA'          => $request->boolean('force_2fa') ? 'true' : 'false',
            'TWO_FACTOR_ISSUER'  => $validated['issuer'],
        ]);

        return to_route('staff.dashboard.twofactor.index')->with('success', '2FA policy updated. Run config cache clear/optimize on server to apply immediately.');
    }

    public function updateSitePolicy(Request $request): RedirectResponse
    {
        $request->validate([
            'open_registration' => ['nullable', 'boolean'],
            'freeleech'         => ['nullable', 'boolean'],
        ]);

        $openRegistration = $request->boolean('open_registration');
        $wasOpenRegistration = ! (bool) config('other.invite-only');
        $openedRegistrationNow = $openRegistration && ! $wasOpenRegistration;

        $this->writeEnvValues([
            'INVITE_ONLY' => $openRegistration ? 'false' : 'true',
            'FREELEECH'   => $request->boolean('freeleech') ? 'true' : 'false',
        ]);

        try {
            if ($openedRegistrationNow) {
                foreach (['optimize:clear', 'config:clear', 'route:clear', 'view:clear', 'event:clear'] as $command) {
                    Artisan::call($command);
                }

                if (function_exists('opcache_reset')) {
                    @opcache_reset();
                }
            } else {
                Artisan::call('optimize:clear');
            }
        } catch (Throwable $throwable) {
            Log::warning('Failed clearing caches after site policy update', [
                'message' => $throwable->getMessage(),
            ]);
        }

        return to_route('staff.dashboard.sitepolicy.index')->with(
            'success',
            $openedRegistrationNow
                ? 'Site policy updated. Open Registration was enabled and a forced cache reset was executed.'
                : 'Site policy updated and persisted to environment configuration.'
        );
    }

    /**
     * @return array<string,bool|string>
     */
    private function siteServicesPayload(): array
    {
        return [
            'site_name'         => (string) config('app.name'),
            'site_title'        => (string) config('other.title'),
            'site_subtitle'     => (string) config('other.subTitle'),
            'site_url'          => (string) config('app.url'),
            'mail_mailer'       => (string) config('mail.default'),
            'owner_email'       => (string) (config('other.email') ?? ''),
            'mail_host'         => (string) (config('mail.mailers.smtp.host') ?? ''),
            'mail_port'         => (string) (config('mail.mailers.smtp.port') ?? ''),
            'mail_username'     => (string) (config('mail.mailers.smtp.username') ?? ''),
            'mail_password_set' => ! empty(config('mail.mailers.smtp.password')),
            'mail_password_mask' => ! empty(config('mail.mailers.smtp.password')) ? '********' : '',
            'mail_encryption'   => (string) (config('mail.mailers.smtp.encryption') ?? ''),
            'mail_allow_self_signed' => (bool) (config('mail.mailers.smtp.stream.ssl.allow_self_signed') ?? false),
            'mail_from_address' => (string) (config('mail.from.address') ?? ''),
            'mail_from_name'    => (string) (config('mail.from.name') ?? ''),
            'mail_sendmail_path' => (string) (config('mail.mailers.sendmail.path') ?? '/usr/sbin/sendmail -bs -i'),
        ];
    }

    /**
     * @param array<string,string> $values
     */
    private function writeEnvValues(array $values): void
    {
        $envPath = base_path('.env');
        $envContent = File::exists($envPath) ? File::get($envPath) : '';

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->formatEnvValue($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';

            if (preg_match($pattern, $envContent) === 1) {
                $envContent = (string) preg_replace($pattern, $line, $envContent);
            } else {
                $envContent .= ($envContent === '' ? '' : PHP_EOL).$line;
            }
        }

        File::put($envPath, $envContent);
    }

    private function formatEnvValue(string $value): string
    {
        if ($value === 'null') {
            return 'null';
        }

        if ($value === '') {
            return '';
        }

        if (preg_match('/\s|#|"|=/', $value) === 1) {
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

            return '"'.$escaped.'"';
        }

        return $value;
    }

    /**
     * @return array{mode:string,saved_as:string}
     */
    private function processThemeImage(\Illuminate\Http\UploadedFile $file, string $outputPathWithoutExtension, int $targetWidth, int $targetHeight): array
    {
        $this->assertImageProcessingAvailable();

        $this->deleteThemeAssetVariants($outputPathWithoutExtension, []);

        $image = Image::make($file->getRealPath())
            ->orientate()
            ->fit($targetWidth, $targetHeight, function ($constraint): void {
                $constraint->upsize();
            });

        try {
            $savedPath = $outputPathWithoutExtension.'.webp';
            $image->encode('webp', 88)->save($savedPath);
            $this->deleteThemeAssetVariants($outputPathWithoutExtension, ['webp']);
            @touch($savedPath);

            return [
                'mode' => 'processed',
                'saved_as' => basename($savedPath),
            ];
        } catch (Exception) {
            $savedPath = $outputPathWithoutExtension.'.jpg';
            $image->encode('jpg', 88)->save($savedPath);
            $this->deleteThemeAssetVariants($outputPathWithoutExtension, ['jpg']);
            @touch($savedPath);

            return [
                'mode' => 'processed',
                'saved_as' => basename($savedPath),
            ];
        }
    }

    /**
     * Resize into a transparent banner canvas so logos keep alpha and do not get aggressively cropped.
     *
     * @return array{mode:string,saved_as:string}
     */
    private function processTransparentBannerCanvas(\Illuminate\Http\UploadedFile $file, string $outputPathWithoutExtension, int $targetWidth, int $targetHeight): array
    {
        $this->assertImageProcessingAvailable();
        $this->deleteThemeAssetVariants($outputPathWithoutExtension, []);

        $image = Image::make($file->getRealPath())->orientate();
        $canvas = Image::canvas($targetWidth, $targetHeight, [0, 0, 0, 0]);

        // Keep logo/text readable while letting it occupy more of the banner.
        $safeWidth = max($targetWidth - 60, 1);
        $safeHeight = max($targetHeight - 24, 1);

        $image->resize($safeWidth, $safeHeight, function ($constraint): void {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $canvas->insert($image, 'center');

        try {
            $savedPath = $outputPathWithoutExtension.'.webp';
            $canvas->encode('webp', 90)->save($savedPath);
            $this->deleteThemeAssetVariants($outputPathWithoutExtension, ['webp']);
            @touch($savedPath);

            return [
                'mode' => 'processed',
                'saved_as' => basename($savedPath),
            ];
        } catch (Exception) {
            $savedPath = $outputPathWithoutExtension.'.png';
            $canvas->encode('png')->save($savedPath);
            $this->deleteThemeAssetVariants($outputPathWithoutExtension, ['png']);
            @touch($savedPath);

            return [
                'mode' => 'processed',
                'saved_as' => basename($savedPath),
            ];
        }
    }

    private function assertImageProcessingAvailable(): void
    {
        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            throw new RuntimeException(
                'Image resizing requires GD or Imagick. Enable one PHP extension, then retry upload.'
            );
        }
    }

    private function resolveThemeAssetUrl(string $baseName, string $fallbackUrl): string
    {
        foreach (['webp', 'jpg', 'jpeg', 'png', 'gif', 'bmp'] as $extension) {
            $absolutePath = public_path("img/theme/{$baseName}.{$extension}");

            if (file_exists($absolutePath)) {
                return asset("img/theme/{$baseName}.{$extension}");
            }
        }

        return $fallbackUrl;
    }

    /**
     * @return array<string,mixed>
     */
    private function buildThemeUploadDebugContext(Request $request): array
    {
        $buildFileMeta = static function (?\Illuminate\Http\UploadedFile $file): array {
            if (! $file instanceof \Illuminate\Http\UploadedFile) {
                return ['present' => false];
            }

            return [
                'present' => true,
                'name' => $file->getClientOriginalName(),
                'client_mime' => $file->getClientMimeType(),
                'detected_mime' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'is_valid' => $file->isValid(),
                'error_code' => $file->getError(),
            ];
        };

        $themeDirectory = public_path('img/theme');

        return [
            'request' => [
                'content_length' => (int) ($request->server('CONTENT_LENGTH') ?? 0),
            ],
            'php' => [
                'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
                'post_max_size' => (string) ini_get('post_max_size'),
                'memory_limit' => (string) ini_get('memory_limit'),
                'gd_loaded' => extension_loaded('gd'),
                'imagick_loaded' => extension_loaded('imagick'),
                'webp_supported' => function_exists('imagewebp'),
                'avif_supported' => function_exists('imageavif'),
            ],
            'filesystem' => [
                'theme_dir' => $themeDirectory,
                'theme_dir_exists' => File::isDirectory($themeDirectory),
                'theme_dir_writable' => File::isDirectory($themeDirectory) ? File::isWritable($themeDirectory) : null,
            ],
            'files' => [
                'theme_banner' => $buildFileMeta($request->file('theme_banner')),
                'theme_background' => $buildFileMeta($request->file('theme_background')),
            ],
        ];
    }

    private function parseIniSizeToBytes(string $value): int
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return 0;
        }

        $unit = strtolower(substr($trimmed, -1));
        $number = (int) $trimmed;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $trimmed,
        };
    }

    /**
     * @param array<int,string> $keepExtensions
     */
    private function deleteThemeAssetVariants(string $basePathWithoutExtension, array $keepExtensions): void
    {
        foreach (['webp', 'jpg', 'jpeg', 'png', 'gif', 'bmp'] as $extension) {
            if (in_array($extension, $keepExtensions, true)) {
                continue;
            }

            $candidate = $basePathWithoutExtension.'.'.$extension;
            if (file_exists($candidate)) {
                File::delete($candidate);
            }
        }
    }
}
