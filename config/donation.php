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

return [
    /*
    |--------------------------------------------------------------------------
    | Donation System
    |--------------------------------------------------------------------------
    |
    | Configure site to use Donation System
    |
    */
    'is_enabled'   => true,
    'monthly_goal' => 100,
    'currency'     => 'USD',
    'description'  => 'Help keep the site alive by donating to our monthly goal.',

    /*
    |--------------------------------------------------------------------------
    | Donation Webhook Secret
    |--------------------------------------------------------------------------
    |
    | A secret token shared with your external payment processor. Set this
    | in your .env file as DONATION_WEBHOOK_SECRET. When set, the automatic
    | donation webhook endpoint will verify incoming requests against this
    | value before processing any upgrades.
    |
    */
    'webhook_secret' => env('DONATION_WEBHOOK_SECRET', ''),
];
