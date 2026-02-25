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
use App\Http\Requests\Staff\UpdateEmailSettingRequest;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class EmailSettingController extends Controller
{
    /**
     * Show the email settings edit form.
     */
    public function edit(): \Illuminate\Contracts\View\Factory|\Illuminate\View\View
    {
        return view('Staff.email-setting.edit', [
            'siteSetting' => SiteSetting::instance(),
        ]);
    }

    /**
     * Update the email settings.
     */
    public function update(UpdateEmailSettingRequest $request): \Illuminate\Http\RedirectResponse
    {
        $setting = SiteSetting::firstOrNew([]);
        $scalar  = $request->safe()->all();

        $setting->fill($scalar);
        $setting->save();
        Cache::forget('site_settings');

        return to_route('staff.email_settings.edit')
            ->with('success', 'Email settings have been updated successfully.');
    }
}
