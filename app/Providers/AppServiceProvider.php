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

namespace App\Providers;

use App\Helpers\ByteUnits;
use App\Helpers\HiddenCaptcha;
use App\Interfaces\ByteUnitsInterface;
use App\Models\IgdbGame;
use App\Models\User;
use App\Observers\UserObserver;
use App\View\Composers\FooterComposer;
use App\View\Composers\TopNavComposer;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * This service provider is a great spot to register your various container
     * bindings with the application. As you can see, we are registering our
     * "Registrar" implementation here. You can add your own bindings too!
     */
    public function register(): void
    {
        // Hidden Captcha
        $this->app->bind('hiddencaptcha', HiddenCaptcha::class);

        // Gabrielelana byte-units
        $this->app->bind(ByteUnitsInterface::class, ByteUnits::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Request $request): void
    {
        if (!\class_exists(\App\Models\Game::class, false)) {
            \class_alias(IgdbGame::class, \App\Models\Game::class);
        }

        // Override mail config from DB site settings if SMTP host is set
        try {
            $siteSetting = \App\Models\SiteSetting::instance();

            if (!empty($siteSetting->smtp_host)) {
                config([
                    'mail.mailers.smtp.host'       => $siteSetting->smtp_host,
                    'mail.mailers.smtp.port'        => $siteSetting->smtp_port ?? 587,
                    'mail.mailers.smtp.encryption'  => $siteSetting->smtp_encryption ?: null,
                    'mail.mailers.smtp.username'    => $siteSetting->smtp_username,
                    'mail.mailers.smtp.password'    => $siteSetting->smtp_password,
                ]);
            }

            if (!empty($siteSetting->smtp_from_address)) {
                config([
                    'mail.from.address' => $siteSetting->smtp_from_address,
                    'mail.from.name'    => $siteSetting->smtp_from_name ?? config('other.title'),
                ]);
            }

            // Sync all DB settings into config so existing code reads live values
            config([
                // Registration & access
                'other.invite-only'                  => (bool) $siteSetting->invite_only,
                'other.registration_open'            => (bool) $siteSetting->registration_open,
                'other.invite_expire'                => $siteSetting->invite_expire ?? 14,
                'other.invites_restriced'            => (bool) ($siteSetting->invites_restricted ?? false),
                'other.invite_groups'                => $siteSetting->invite_groups ?? ['Administrator', 'Owner'],
                'other.max_unused_user_invites'      => $siteSetting->max_unused_user_invites ?? 1,
                'other.application_signups'          => (bool) ($siteSetting->application_signups ?? false),

                // Economy
                'other.freeleech'                    => (bool) ($siteSetting->freeleech ?? false),
                'other.freeleech_until'              => $siteSetting->freeleech_until,
                'other.doubleup'                     => (bool) ($siteSetting->doubleup ?? false),
                'other.refundable'                   => (bool) ($siteSetting->refundable ?? false),
                'other.ratio'                        => (float) ($siteSetting->min_ratio ?? 0.40),
                'other.bon.max-buffer-to-buy-upload' => $siteSetting->bon_max_buffer,

                // User defaults
                'other.default_upload'               => $siteSetting->default_upload ?? 53687091200,
                'other.default_download'             => $siteSetting->default_download ?? 1073741824,
                'other.default_style'                => $siteSetting->default_style ?? 12,
                'other.font-awesome'                 => $siteSetting->font_awesome ?? 'fas',
                'unit3d.homepage-banner-style'       => $siteSetting->homepage_banner_style ?? 'compact',

                // Page URLs
                'other.rules_url'                    => $siteSetting->rules_url,
                'other.faq_url'                      => $siteSetting->faq_url,
                'other.upload-guide_url'             => $siteSetting->upload_guide_url,

                // Staff forum
                'other.staff-forum-notify'           => $siteSetting->staff_forum_notify ? '1' : '0',
                'other.staff-forum-id'               => $siteSetting->staff_forum_id ?? '',

                // Thanks system
                'other.thanks-system.is-enabled'     => (bool) ($siteSetting->thanks_enabled ?? true),

                // Mail rate limiting
                'other.mail.allow'                   => $siteSetting->mail_rate_allow ?? 1,
                'other.mail.every'                   => $siteSetting->mail_rate_every ?? 5,

                // Tracker
                'announce.allow_same_user_peer_matching' => (bool) ($siteSetting->announce_allow_same_user_peer_matching ?? false),

                // External chat link
                'unit3d.chat-link-name'              => $siteSetting->chat_link_name ?? 'Discord',
                'unit3d.chat-link-icon'              => $siteSetting->chat_link_icon ?? 'fab fa-discord',
                'unit3d.chat-link-url'               => $siteSetting->chat_link_url ?? '',

                // Comment rate limit
                'unit3d.comment-rate-limit'          => $siteSetting->comment_rate_limit ?? 3,

                // Chat
                'chat.nerd_bot'                      => (bool) ($siteSetting->nerd_bot ?? true),
                'chat.system_chatroom'               => $siteSetting->system_chatroom ?? 'General',
                'chat.message_limit'                 => $siteSetting->chat_message_limit ?? 100,

                // Hit & Run
                'hitrun.enabled'                     => (bool) ($siteSetting->hitrun_enabled ?? true),
                'hitrun.seedtime'                    => $siteSetting->hitrun_seedtime ?? 604800,
                'hitrun.max_warnings'                => $siteSetting->hitrun_max_warnings ?? 3,
                'hitrun.grace'                       => $siteSetting->hitrun_grace ?? 3,
                'hitrun.buffer'                      => $siteSetting->hitrun_buffer ?? 10,
                'hitrun.expire'                      => $siteSetting->hitrun_expire ?? 14,
                'hitrun.prewarn'                     => $siteSetting->hitrun_prewarn ?? 1,

                // Torrent
                'torrent.download_check_page'        => (int) ($siteSetting->torrent_download_check_page ?? false),
                'torrent.source'                     => $siteSetting->torrent_source ?? 'BAS3D',
                'torrent.created_by'                 => $siteSetting->torrent_created_by ?? 'Edited by BAS3D',
                'torrent.created_by_append'          => (bool) ($siteSetting->torrent_created_by_append ?? true),
                'torrent.comment'                    => $siteSetting->torrent_comment ?? '',
                'torrent.magnet'                     => (int) ($siteSetting->torrent_magnet ?? false),

                // Donation
                'donation.is_enabled'                => (bool) ($siteSetting->donation_enabled ?? true),
                'donation.monthly_goal'              => $siteSetting->donation_monthly_goal ?? 100,
                'donation.currency'                  => $siteSetting->donation_currency ?? 'USD',
                'donation.description'               => $siteSetting->donation_description ?? '',

                // Graveyard
                'graveyard.enabled'                  => (bool) ($siteSetting->graveyard_enabled ?? true),
                'graveyard.time'                     => $siteSetting->graveyard_time ?? 2592000,
                'graveyard.reward'                   => $siteSetting->graveyard_reward ?? 5,
            ]);
        } catch (\Throwable) {
            // DB not ready (e.g. first migrate) — keep env defaults
        }

        // User Observer For Cache
        User::observe(UserObserver::class);

        // Hidden Captcha
        Blade::directive('hiddencaptcha', fn ($mustBeEmptyField = '_username') => \sprintf('<?= App\Helpers\HiddenCaptcha::render(%s); ?>', $mustBeEmptyField));

        // BBcode
        Blade::directive('bbcode', fn (?string $bbcodeString) => "<?php echo (new \hdvinnie\LaravelJoyPixels\LaravelJoyPixels())->toImage((new \App\Helpers\Linkify())->linky((new \App\Helpers\Bbcode())->parse({$bbcodeString}))); ?>");

        // Linkify
        Blade::directive('linkify', fn (?string $contentString) => "<?php echo (new \App\Helpers\Linkify)->linky(e({$contentString})); ?>");

        $this->app['validator']->extendImplicit(
            'hiddencaptcha',
            function ($attribute, $value, $parameters, $validator) {
                $minLimit = (isset($parameters[0]) && is_numeric($parameters[0])) ? $parameters[0] : 0;
                $maxLimit = (isset($parameters[1]) && is_numeric($parameters[1])) ? $parameters[1] : 1_200;

                if (!HiddenCaptcha::check($validator, $minLimit, $maxLimit)) {
                    $validator->setCustomMessages(['hiddencaptcha' => 'Captcha error']);

                    return false;
                }

                return true;
            }
        );

        // Add attributes to vite scripts and styles
        Vite::useScriptTagAttributes([
            'crossorigin' => 'anonymous',
        ]);

        Vite::useStyleTagAttributes([
            'crossorigin' => 'anonymous',
        ]);

        View::composer('partials.footer', FooterComposer::class);
        View::composer('partials.top-nav', TopNavComposer::class);

        TrimStrings::except([
            'current_password',
            'password',
            'password_confirmation',
            'info_hash',
            'peer_id',
        ]);

        Auth::viaRequest('rsskey', fn (Request $request) => User::query()->where('rsskey', '=', $request->route('rsskey'))->first());

        Context::add('url', $request->url());
    }
}
