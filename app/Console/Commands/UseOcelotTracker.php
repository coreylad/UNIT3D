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
 * @author     BAS3D
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class UseOcelotTracker extends Command
{
    protected $signature = 'tracker:use-ocelot
        {announce_url? : Ocelot announce URL template. Example: https://tracker.example.com/{passkey}/announce}
        {--internal : Switch back to the internal tracker}';

    protected $description = 'Configure announce driver for Ocelot or switch back to internal tracker';

    public function handle(): int
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            $this->error('.env file not found. Create .env before configuring tracker mode.');

            return self::FAILURE;
        }

        if ($this->option('internal')) {
            $this->setEnvValue($envPath, 'ANNOUNCE_DRIVER', 'internal');
            $this->setEnvValue($envPath, 'TRACKER_EXTERNAL_ENABLED', 'false', false);
            $this->setEnvValue($envPath, 'TRACKER_HOST', '', false);
            $this->setEnvValue($envPath, 'TRACKER_PORT', '', false);
            $this->setEnvValue($envPath, 'TRACKER_UNIX_SOCKET', '', false);
            $this->setEnvValue($envPath, 'TRACKER_KEY', '', false);

            $this->clearConfigCache();
            $this->info('Tracker driver set to internal.');

            return self::SUCCESS;
        }

        $announceUrl = (string) ($this->argument('announce_url') ?? env('OCELOT_ANNOUNCE_URL', ''));
        $announceUrl = trim($announceUrl);

        if ($announceUrl === '') {
            $this->error('Provide announce_url. Example: php artisan tracker:use-ocelot "https://tracker.example.com/{passkey}/announce"');

            return self::FAILURE;
        }

        $parsedUrl = parse_url($announceUrl);

        if (!is_array($parsedUrl) || !isset($parsedUrl['host'])) {
            $this->error('Invalid announce_url. Expected full URL like https://tracker.example.com/announce/{passkey}');

            return self::FAILURE;
        }

        $trackerHost = (string) $parsedUrl['host'];
        $trackerPort = (string) ($parsedUrl['port'] ?? (($parsedUrl['scheme'] ?? 'http') === 'https' ? 443 : 80));

        $this->setEnvValue($envPath, 'ANNOUNCE_DRIVER', 'ocelot');
        $this->setEnvValue($envPath, 'OCELOT_ANNOUNCE_URL', $announceUrl);
        $this->setEnvValue($envPath, 'TRACKER_EXTERNAL_ENABLED', 'true', false);
        $this->setEnvValue($envPath, 'TRACKER_HOST', $trackerHost, false);
        $this->setEnvValue($envPath, 'TRACKER_PORT', $trackerPort, false);
        $this->setEnvValue($envPath, 'TRACKER_UNIX_SOCKET', '', false);
        $this->setEnvValue($envPath, 'TRACKER_KEY', '', false);

        $this->clearConfigCache();
        $this->info('Tracker driver set to ocelot.');

        return self::SUCCESS;
    }

    private function clearConfigCache(): void
    {
        try {
            Artisan::call('config:clear');
        } catch (\Throwable) {
        }
    }

    private function setEnvValue(string $envPath, string $key, string $value, bool $quote = true): void
    {
        $content = File::get($envPath);
        $replacementValue = $quote ? '"'.str_replace('"', '\\"', $value).'"' : $value;
        $line = $key.'='.$replacementValue;
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $content) === 1) {
            $updated = preg_replace($pattern, $line, $content);

            if ($updated !== null) {
                File::put($envPath, $updated);
            }

            return;
        }

        $suffix = str_ends_with($content, PHP_EOL) ? '' : PHP_EOL;
        File::put($envPath, $content.$suffix.$line.PHP_EOL);
    }
}