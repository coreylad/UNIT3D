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
            // Branding (sometimes: only validated when field is present in the form)
            'title' => ['sometimes', 'required', 'string', 'max:100'],
            'sub_title' => ['sometimes', 'required', 'string', 'max:200'],
            'meta_description' => ['sometimes', 'required', 'string', 'max:500'],
            'login_message' => ['nullable', 'string', 'max:1000'],
            'header_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'remove_header_image' => ['nullable', 'boolean'],

            // Registration
            'registration_open' => ['nullable', 'boolean'],
            'invite_only' => ['nullable', 'boolean'],
            'default_download_slots' => ['nullable', 'integer', 'between:1,999'],

            // Tracker
            'announce_interval' => ['nullable', 'integer', 'between:60,86400'],
            'category_filter_enabled' => ['nullable', 'boolean'],
            'nerd_bot' => ['nullable', 'boolean'],

            // Social
            'discord_url' => ['nullable', 'url', 'max:500'],
            'twitter_url' => ['nullable', 'url', 'max:500'],
            'github_url' => ['nullable', 'url', 'max:500'],
        ];
    }
}
