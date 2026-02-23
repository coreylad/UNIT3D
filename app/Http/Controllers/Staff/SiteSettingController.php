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
     * Update the site settings.
     */
    public function update(UpdateSiteSettingRequest $request): \Illuminate\Http\RedirectResponse
    {
        $setting = SiteSetting::firstOrNew([]);
        $setting->fill($request->validated());
        $setting->save();

        Cache::forget('site_settings');

        return to_route('staff.site_settings.edit')
            ->with('success', 'Site settings have been updated successfully.');
    }
}
