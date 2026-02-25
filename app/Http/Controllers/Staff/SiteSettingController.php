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
     * Show the site settings edit form.
     */
    public function edit(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.site-setting.edit', [
            'siteSetting' => SiteSetting::instance(),
        ]);
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
     * Update the site settings.
     */
    public function update(UpdateSiteSettingRequest $request): \Illuminate\Http\RedirectResponse
    {
        $setting = SiteSetting::firstOrNew([]);
        $scalar  = $request->safe()->except(['header_image', 'remove_header_image']);

        // Coerce checkboxes (unchecked = absent from POST)
        $scalar['registration_open']       = $request->boolean('registration_open');
        $scalar['invite_only']             = $request->boolean('invite_only');
        $scalar['category_filter_enabled'] = $request->boolean('category_filter_enabled');

        $setting->fill($scalar);

        // Header image removal
        if ($request->boolean('remove_header_image') && $setting->header_image) {
            $oldPath = public_path('img/' . $setting->header_image);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }

            $setting->header_image = null;
        }

        // Header image upload
        if ($request->hasFile('header_image')) {
            if ($setting->header_image) {
                $oldPath = public_path('img/' . $setting->header_image);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $ext      = $request->file('header_image')->getClientOriginalExtension();
            $filename = 'header_banner.' . strtolower($ext);
            $request->file('header_image')->move(public_path('img'), $filename);
            $setting->header_image = $filename;
        }

        $setting->save();
        Cache::forget('site_settings');

        return to_route('staff.site_settings.edit')
            ->with('success', 'Site settings have been updated successfully.');
    }
}
