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

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\UpdateSiteSettingRequest;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SiteSettingController extends Controller
{
    /**
     * Redirect the generic edit URL to the branding section.
     */
    public function edit(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('staff.site_settings.branding');
    }

    /**
     * Show the branding edit form.
     */
    public function editBranding(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.branding', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Show the registration edit form.
     */
    public function editRegistration(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.registration', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Show the tracker settings edit form.
     */
    public function editTracker(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.tracker', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Show the social links edit form.
     */
    public function editSocial(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.social', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Show the economy settings edit form.
     */
    public function editEconomy(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.economy', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Show the invite settings edit form.
     */
    public function editInvites(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.invites', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Show the user defaults edit form.
     */
    public function editUserDefaults(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.user-defaults', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Show the hit & run settings edit form.
     */
    public function editHitRun(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.hit-run', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Show the chat settings edit form.
     */
    public function editChat(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.chat', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Show the torrent settings edit form.
     */
    public function editTorrent(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.torrent-settings', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Show the donation settings edit form.
     */
    public function editDonation(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.donation', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Show the graveyard settings edit form.
     */
    public function editGraveyard(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.graveyard', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Update the site settings.
     */
    public function update(UpdateSiteSettingRequest $request): \Illuminate\Http\RedirectResponse
    {
        $setting = SiteSetting::firstOrNew([]);
        $scalar  = $request->safe()->except(['header_image', 'remove_header_image']);

        // Coerce checkboxes (unchecked = absent from POST) — only for fields in the current section
        $booleanFields = [
            'registration_open',
            'invite_only',
            'invites_restricted',
            'application_signups',
            'category_filter_enabled',
            'nerd_bot',
            'freeleech',
            'doubleup',
            'refundable',
            'staff_forum_notify',
            'thanks_enabled',
            'hitrun_enabled',
            'torrent_download_check_page',
            'torrent_created_by_append',
            'torrent_magnet',
            'donation_enabled',
            'graveyard_enabled',
        ];

        $section = $request->input('_section');

        foreach ($booleanFields as $field) {
            if ($request->has($field) || $section) {
                $scalar[$field] = $request->boolean($field);
            }
        }

        $setting->fill($scalar);

        // Header image removal
        if ($request->boolean('remove_header_image') && $setting->header_image) {
            $oldPath = public_path('img/'.$setting->header_image);

            if (file_exists($oldPath)) {
                unlink($oldPath);
            }

            $setting->header_image = null;
        }

        // Header image upload
        if ($request->hasFile('header_image')) {
            if ($setting->header_image) {
                $oldPath = public_path('img/'.$setting->header_image);

                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $ext      = $request->file('header_image')->getClientOriginalExtension();
            $filename = 'header_banner.'.strtolower($ext);
            $request->file('header_image')->move(public_path('img'), $filename);
            $setting->header_image = $filename;
        }

        $setting->save();
        Cache::forget('site_settings');

        return redirect()->back()
            ->with('success', 'Site settings have been updated successfully.');
    }
}
