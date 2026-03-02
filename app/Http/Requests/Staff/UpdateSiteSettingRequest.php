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

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array<\Illuminate\Contracts\Validation\Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            // Branding
            'title'            => ['sometimes', 'required', 'string', 'max:100'],
            'sub_title'        => ['sometimes', 'required', 'string', 'max:200'],
            'meta_description' => ['sometimes', 'required', 'string', 'max:500'],
            'login_message'    => ['nullable', 'string', 'max:1000'],
            'header_image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'remove_header_image' => ['nullable', 'boolean'],

            // Registration
            'registration_open'       => ['nullable', 'boolean'],
            'invite_only'             => ['nullable', 'boolean'],
            'invite_expire'           => ['nullable', 'integer', 'between:1,365'],
            'invites_restricted'      => ['nullable', 'boolean'],
            'invite_groups'           => ['nullable', 'array'],
            'invite_groups.*'         => ['string', 'max:100'],
            'max_unused_user_invites' => ['nullable', 'integer', 'between:0,100'],
            'application_signups'     => ['nullable', 'boolean'],
            'default_download_slots'  => ['nullable', 'integer', 'between:1,999'],

            // User defaults
            'default_upload'   => ['nullable', 'integer', 'min:0'],
            'default_download' => ['nullable', 'integer', 'min:0'],
            'default_style'    => ['nullable', 'integer', 'between:0,50'],
            'font_awesome'     => ['nullable', 'string', 'in:fas,far,fal'],

            // Page URLs
            'rules_url'        => ['nullable', 'string', 'max:500'],
            'faq_url'          => ['nullable', 'string', 'max:500'],
            'upload_guide_url' => ['nullable', 'string', 'max:500'],

            // Staff forum
            'staff_forum_notify' => ['nullable', 'boolean'],
            'staff_forum_id'     => ['nullable', 'integer', 'min:0'],

            // Thanks system
            'thanks_enabled' => ['nullable', 'boolean'],

            // Mail rate limiting
            'mail_rate_allow' => ['nullable', 'integer', 'between:1,100'],
            'mail_rate_every' => ['nullable', 'integer', 'between:1,3600'],

            // Tracker
            'announce_interval'       => ['nullable', 'integer', 'between:60,86400'],
            'category_filter_enabled' => ['nullable', 'boolean'],
            'nerd_bot'                => ['nullable', 'boolean'],

            // Economy
            'freeleech'       => ['nullable', 'boolean'],
            'freeleech_until' => ['nullable', 'string', 'max:100'],
            'doubleup'        => ['nullable', 'boolean'],
            'refundable'      => ['nullable', 'boolean'],
            'min_ratio'       => ['nullable', 'numeric', 'between:0,99.99'],
            'bon_max_buffer'  => ['nullable', 'integer', 'min:0'],

            // External chat link
            'chat_link_name' => ['nullable', 'string', 'max:50'],
            'chat_link_icon' => ['nullable', 'string', 'max:50'],
            'chat_link_url'  => ['nullable', 'url', 'max:500'],

            // Comment rate limit
            'comment_rate_limit' => ['nullable', 'integer', 'between:1,60'],

            // Hit & Run
            'hitrun_enabled'      => ['nullable', 'boolean'],
            'hitrun_seedtime'     => ['nullable', 'integer', 'between:0,31536000'],
            'hitrun_max_warnings' => ['nullable', 'integer', 'between:1,100'],
            'hitrun_grace'        => ['nullable', 'integer', 'between:0,365'],
            'hitrun_buffer'       => ['nullable', 'integer', 'between:0,100'],
            'hitrun_expire'       => ['nullable', 'integer', 'between:1,365'],
            'hitrun_prewarn'      => ['nullable', 'integer', 'between:0,365'],

            // Chat
            'system_chatroom'   => ['nullable', 'string', 'max:100'],
            'chat_message_limit' => ['nullable', 'integer', 'between:10,1000'],

            // Torrent
            'torrent_download_check_page' => ['nullable', 'boolean'],
            'torrent_source'              => ['nullable', 'string', 'max:100'],
            'torrent_created_by'          => ['nullable', 'string', 'max:255'],
            'torrent_created_by_append'   => ['nullable', 'boolean'],
            'torrent_comment'             => ['nullable', 'string', 'max:500'],
            'torrent_magnet'              => ['nullable', 'boolean'],

            // Donation
            'donation_enabled'      => ['nullable', 'boolean'],
            'donation_monthly_goal' => ['nullable', 'integer', 'between:0,1000000'],
            'donation_currency'     => ['nullable', 'string', 'max:10'],
            'donation_description'  => ['nullable', 'string', 'max:500'],

            // Graveyard
            'graveyard_enabled' => ['nullable', 'boolean'],
            'graveyard_time'    => ['nullable', 'integer', 'between:0,31536000'],
            'graveyard_reward'  => ['nullable', 'integer', 'between:0,1000'],

            // Social
            'discord_url' => ['nullable', 'url', 'max:500'],
            'twitter_url' => ['nullable', 'url', 'max:500'],
            'github_url'  => ['nullable', 'url', 'max:500'],
        ];
    }
}
