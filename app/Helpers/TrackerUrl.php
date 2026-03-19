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

namespace App\Helpers;

class TrackerUrl
{
    public static function announce(string $passkey): string
    {
        if (self::usesOcelot()) {
            $announceUrl = trim((string) config('announce.ocelot.announce_url'));

            if ($announceUrl !== '') {
                if (str_contains($announceUrl, '{passkey}')) {
                    return str_replace('{passkey}', $passkey, $announceUrl);
                }

                return rtrim($announceUrl, '/').'/'.$passkey;
            }
        }

        return route('announce', ['passkey' => $passkey]);
    }

    public static function usesOcelot(): bool
    {
        return config('announce.driver') === 'ocelot';
    }

    public static function usesExternalAnnounce(): bool
    {
        return self::usesOcelot() || config('announce.external_tracker.is_enabled') === true;
    }
}