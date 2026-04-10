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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Spatie\SslCertificate\SslCertificate;
use Exception;

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
            'siteServices'             => [
                'site_name'         => (string) config('app.name'),
                'site_title'        => (string) config('other.title'),
                'site_subtitle'     => (string) config('other.subTitle'),
                'site_url'          => (string) config('app.url'),
                'mail_mailer'       => (string) config('mail.default'),
                'owner_email'       => (string) (config('other.email') ?? ''),
                'mail_host'         => (string) (config('mail.mailers.smtp.host') ?? ''),
                'mail_port'         => (string) (config('mail.mailers.smtp.port') ?? ''),
                'mail_username'     => (string) (config('mail.mailers.smtp.username') ?? ''),
                'mail_password'     => (string) (config('mail.mailers.smtp.password') ?? ''),
                'mail_encryption'   => (string) (config('mail.mailers.smtp.encryption') ?? ''),
                'mail_from_address' => (string) (config('mail.from.address') ?? ''),
                'mail_from_name'    => (string) (config('mail.from.name') ?? ''),
            ],
        ]);
    }

    /**
     * Update the site header banner image shown above the top navigation.
     */
    public function updateBanner(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_banner' => ['required', 'image', 'mimetypes:image/png', 'max:12288'],
        ]);

        $directory = public_path('img/auth');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        /** @var \Illuminate\Http\UploadedFile $banner */
        $banner = $validated['site_banner'];
        $banner->move($directory, 'The_Void_Login_Page.png');

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
            'mail_encryption'   => ['nullable', 'string', Rule::in(['', 'tls', 'ssl'])],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name'    => ['nullable', 'string', 'max:255'],
        ]);

        $this->writeEnvValues([
            'APP_URL'           => $validated['site_url'],
            'MAIL_MAILER'       => $validated['mail_mailer'],
            'DEFAULT_OWNER_EMAIL' => (string) ($validated['owner_email'] ?? ''),
            'MAIL_HOST'         => (string) ($validated['mail_host'] ?? ''),
            'MAIL_PORT'         => (string) ($validated['mail_port'] ?? ''),
            'MAIL_USERNAME'     => (string) ($validated['mail_username'] ?? ''),
            'MAIL_PASSWORD'     => (string) ($validated['mail_password'] ?? ''),
            'MAIL_ENCRYPTION'   => $validated['mail_encryption'] ?? 'null',
            'MAIL_FROM_ADDRESS' => (string) ($validated['mail_from_address'] ?? ''),
            'MAIL_FROM_NAME'    => (string) ($validated['mail_from_name'] ?? ''),
        ]);

        $this->updateConfigStringValue(config_path('app.php'), 'name', $validated['site_name']);
        $this->updateConfigStringValue(config_path('other.php'), 'title', $validated['site_title']);
        $this->updateConfigStringValue(config_path('other.php'), 'subTitle', (string) ($validated['site_subtitle'] ?? ''));

        return to_route('staff.dashboard.index')->with('success', 'Site services updated. Run config cache clear/optimize on server to apply immediately.');
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

    private function updateConfigStringValue(string $filePath, string $key, string $value): void
    {
        if (! File::exists($filePath)) {
            return;
        }

        $content = File::get($filePath);
        $escaped = str_replace("'", "\\'", $value);
        $pattern = "/('".preg_quote($key, '/')."'\\s*=>\\s*)'[^']*'/";
        $replacement = "$1'{$escaped}'";
        $updated = preg_replace($pattern, $replacement, $content, 1);

        if (
            is_string($updated)
            && $updated !== $content
        ) {
            File::put($filePath, $updated);
        }
    }
}
