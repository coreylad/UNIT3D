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

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasskeyResetRequest extends Notification implements ShouldQueue
{
    use Queueable;

    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = url('/reset-passkey/'.$this->token);

        return (new MailMessage)
            ->subject('Reset Your Passkey')
            ->greeting('Hello '.$notifiable->username.',')
            ->line('You have requested to reset your passkey. If you did not request this, please ignore this email.')
            ->action('Reset Passkey', $resetUrl)
            ->line('This link will expire in 1 hour.')
            ->line('Thank you!')
            ->from(config('mail.from.address'), config('mail.from.name'));
    }
}
