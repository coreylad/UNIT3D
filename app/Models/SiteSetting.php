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
 * @property int         $id
 * @property string      $title
 * @property string      $sub_title
 * @property string      $meta_description
 * @property string|null $login_message
 */
final class SiteSetting extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = ['id'];

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
            $fallback->title            = (string) config('other.title');
            $fallback->sub_title        = (string) config('other.subTitle');
            $fallback->meta_description = (string) config('other.meta_description');
            $fallback->login_message    = null;

            return $fallback;
        }
    }
}
