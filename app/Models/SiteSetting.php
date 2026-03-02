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
 * @property int              $id
 * @property string           $title
 * @property string           $sub_title
 * @property string           $meta_description
 * @property string|null      $login_message
 * @property string|null      $header_image
 * @property string|null      $smtp_host
 * @property int              $smtp_port
 * @property string|null      $smtp_encryption
 * @property string|null      $smtp_username
 * @property string|null      $smtp_password
 * @property string|null      $smtp_from_address
 * @property string|null      $smtp_from_name
 * @property bool             $registration_open
 * @property bool             $invite_only
 * @property int              $invite_expire
 * @property bool             $invites_restricted
 * @property array|null       $invite_groups
 * @property int              $max_unused_user_invites
 * @property bool             $application_signups
 * @property int              $default_download_slots
 * @property int              $default_upload
 * @property int              $default_download
 * @property int              $default_style
 * @property string           $font_awesome
 * @property string|null      $rules_url
 * @property string|null      $faq_url
 * @property string|null      $upload_guide_url
 * @property bool             $staff_forum_notify
 * @property int|null         $staff_forum_id
 * @property bool             $thanks_enabled
 * @property int              $mail_rate_allow
 * @property int              $mail_rate_every
 * @property int              $announce_interval
 * @property bool             $category_filter_enabled
 * @property bool             $nerd_bot
 * @property bool             $freeleech
 * @property string|null      $freeleech_until
 * @property bool             $doubleup
 * @property bool             $refundable
 * @property float            $min_ratio
 * @property int|null         $bon_max_buffer
 * @property string           $chat_link_name
 * @property string           $chat_link_icon
 * @property string|null      $chat_link_url
 * @property int              $comment_rate_limit
 * @property bool             $hitrun_enabled
 * @property int              $hitrun_seedtime
 * @property int              $hitrun_max_warnings
 * @property int              $hitrun_grace
 * @property int              $hitrun_buffer
 * @property int              $hitrun_expire
 * @property int              $hitrun_prewarn
 * @property string           $system_chatroom
 * @property int              $chat_message_limit
 * @property bool             $torrent_download_check_page
 * @property string           $torrent_source
 * @property string           $torrent_created_by
 * @property bool             $torrent_created_by_append
 * @property string|null      $torrent_comment
 * @property bool             $torrent_magnet
 * @property bool             $donation_enabled
 * @property int              $donation_monthly_goal
 * @property string           $donation_currency
 * @property string|null      $donation_description
 * @property bool             $graveyard_enabled
 * @property int              $graveyard_time
 * @property int              $graveyard_reward
 * @property string|null      $discord_url
 * @property string|null      $twitter_url
 * @property string|null      $github_url
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
        'registration_open'           => 'boolean',
        'invite_only'                 => 'boolean',
        'invite_expire'               => 'integer',
        'invites_restricted'          => 'boolean',
        'invite_groups'               => 'array',
        'max_unused_user_invites'     => 'integer',
        'application_signups'         => 'boolean',
        'category_filter_enabled'     => 'boolean',
        'nerd_bot'                    => 'boolean',
        'smtp_port'                   => 'integer',
        'default_download_slots'      => 'integer',
        'default_upload'              => 'integer',
        'default_download'            => 'integer',
        'default_style'               => 'integer',
        'staff_forum_notify'          => 'boolean',
        'staff_forum_id'              => 'integer',
        'thanks_enabled'              => 'boolean',
        'mail_rate_allow'             => 'integer',
        'mail_rate_every'             => 'integer',
        'announce_interval'           => 'integer',
        'freeleech'                   => 'boolean',
        'doubleup'                    => 'boolean',
        'refundable'                  => 'boolean',
        'min_ratio'                   => 'decimal:2',
        'bon_max_buffer'              => 'integer',
        'comment_rate_limit'          => 'integer',
        'hitrun_enabled'              => 'boolean',
        'hitrun_seedtime'             => 'integer',
        'hitrun_max_warnings'         => 'integer',
        'hitrun_grace'                => 'integer',
        'hitrun_buffer'               => 'integer',
        'hitrun_expire'               => 'integer',
        'hitrun_prewarn'              => 'integer',
        'chat_message_limit'          => 'integer',
        'torrent_download_check_page' => 'boolean',
        'torrent_created_by_append'   => 'boolean',
        'torrent_magnet'              => 'boolean',
        'donation_enabled'            => 'boolean',
        'donation_monthly_goal'       => 'integer',
        'graveyard_enabled'           => 'boolean',
        'graveyard_time'              => 'integer',
        'graveyard_reward'            => 'integer',
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
            $fallback->title                      = (string) config('other.title');
            $fallback->sub_title                  = (string) config('other.subTitle');
            $fallback->meta_description           = (string) config('other.meta_description');
            $fallback->login_message              = null;
            $fallback->header_image               = null;
            $fallback->smtp_host                  = null;
            $fallback->smtp_port                  = 587;
            $fallback->smtp_encryption            = null;
            $fallback->smtp_username              = null;
            $fallback->smtp_password              = null;
            $fallback->smtp_from_address          = null;
            $fallback->smtp_from_name             = null;
            $fallback->registration_open          = true;
            $fallback->invite_only                = false;
            $fallback->invite_expire              = 14;
            $fallback->invites_restricted         = false;
            $fallback->invite_groups              = null;
            $fallback->max_unused_user_invites    = 1;
            $fallback->application_signups        = false;
            $fallback->default_download_slots     = 8;
            $fallback->default_upload             = 53687091200;
            $fallback->default_download           = 1073741824;
            $fallback->default_style              = 12;
            $fallback->font_awesome               = 'fas';
            $fallback->rules_url                  = null;
            $fallback->faq_url                    = null;
            $fallback->upload_guide_url           = null;
            $fallback->staff_forum_notify         = false;
            $fallback->staff_forum_id             = null;
            $fallback->thanks_enabled             = true;
            $fallback->mail_rate_allow            = 1;
            $fallback->mail_rate_every            = 5;
            $fallback->announce_interval          = 1800;
            $fallback->category_filter_enabled    = true;
            $fallback->nerd_bot                   = true;
            $fallback->freeleech                  = false;
            $fallback->freeleech_until            = null;
            $fallback->doubleup                   = false;
            $fallback->refundable                 = false;
            $fallback->min_ratio                  = 0.40;
            $fallback->bon_max_buffer             = null;
            $fallback->chat_link_name             = 'Discord';
            $fallback->chat_link_icon             = 'fab fa-discord';
            $fallback->chat_link_url              = null;
            $fallback->comment_rate_limit         = 3;
            $fallback->hitrun_enabled             = true;
            $fallback->hitrun_seedtime            = 604800;
            $fallback->hitrun_max_warnings        = 3;
            $fallback->hitrun_grace               = 3;
            $fallback->hitrun_buffer              = 10;
            $fallback->hitrun_expire              = 14;
            $fallback->hitrun_prewarn             = 1;
            $fallback->system_chatroom            = 'General';
            $fallback->chat_message_limit         = 100;
            $fallback->torrent_download_check_page = false;
            $fallback->torrent_source             = 'BAS3D';
            $fallback->torrent_created_by         = 'Edited by BAS3D';
            $fallback->torrent_created_by_append  = true;
            $fallback->torrent_comment            = null;
            $fallback->torrent_magnet             = false;
            $fallback->donation_enabled           = true;
            $fallback->donation_monthly_goal      = 100;
            $fallback->donation_currency          = 'USD';
            $fallback->donation_description       = null;
            $fallback->graveyard_enabled          = true;
            $fallback->graveyard_time             = 2592000;
            $fallback->graveyard_reward           = 5;
            $fallback->discord_url                = null;
            $fallback->twitter_url                = null;
            $fallback->github_url                 = null;

            return $fallback;
        }
    }
}
