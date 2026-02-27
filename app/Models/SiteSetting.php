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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SiteSetting.
 *
 * @property int          $id
 * @property string       $title
 * @property string       $sub_title
 * @property string       $meta_description
 * @property string|null  $login_message
 * @property string|null  $header_image
 * @property string|null  $smtp_host
 * @property int          $smtp_port
 * @property string|null  $smtp_encryption
 * @property string|null  $smtp_username
 * @property string|null  $smtp_password
 * @property string|null  $smtp_from_address
 * @property string|null  $smtp_from_name
 * @property bool         $registration_open
 * @property bool         $invite_only
 * @property int          $default_download_slots
 * @property int          $announce_interval
 * @property bool         $category_filter_enabled
 * @property bool         $nerd_bot
 * @property string|null  $discord_url
 * @property string|null  $twitter_url
 * @property string|null  $github_url
 */
final class SiteSetting extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = ['id'];

    protected $casts = [
        'registration_open'       => 'boolean',
        'invite_only'             => 'boolean',
        'category_filter_enabled' => 'boolean',
        'nerd_bot'                => 'boolean',
        'smtp_port'               => 'integer',
        'default_download_slots'  => 'integer',
        'announce_interval'       => 'integer',
    ];

    /**
     * Retrieve the singleton site settings record, creating it if needed.
     * Falls back to config values if the database is unavailable.
     */
    public static function instance(): self
    {
        try {
            return cache()->flexible('site_settings', [3600, 7200], fn () => self::firstOrCreate([], [
                'title'            => config('other.title'),
                'sub_title'        => config('other.subTitle'),
                'meta_description' => config('other.meta_description'),
                'login_message'    => null,
            ]));
        } catch (\Throwable) {
            $fallback = new self();
            $fallback->title                   = (string) config('other.title');
            $fallback->sub_title               = (string) config('other.subTitle');
            $fallback->meta_description        = (string) config('other.meta_description');
            $fallback->login_message           = null;
            $fallback->header_image            = null;
            $fallback->smtp_host               = null;
            $fallback->smtp_port               = 587;
            $fallback->smtp_encryption         = null;
            $fallback->smtp_username           = null;
            $fallback->smtp_password           = null;
            $fallback->smtp_from_address       = null;
            $fallback->smtp_from_name          = null;
            $fallback->registration_open       = true;
            $fallback->invite_only             = false;
            $fallback->default_download_slots  = 8;
            $fallback->announce_interval       = 1800;
            $fallback->category_filter_enabled = true;
            $fallback->discord_url             = null;
            $fallback->twitter_url             = null;
            $fallback->github_url              = null;

            return $fallback;
        }
    }
}
