<?php

namespace App\Services;

use App\Models\SmtpSetting;
use App\Models\User;
use App\Models\Website;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;

class ForgotPasswordMailer
{
    public function sendResetLink(User $user, string $token): void
    {
        $smtp = SmtpSetting::firstOrFail();
        $website = Website::first();

        $html = view('emails.reset-password', [
            'nama' => $user->nama,
            'logo' => $website->logo ?? '',
            'titletext' => $website->title ?? '',
            'resetUrl' => url('auth/reset-password/'.$token),
        ])->render();

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $smtp->key);
        $api = new TransactionalEmailsApi(new Client, $config);

        $email = new SendSmtpEmail;
        $email['subject'] = 'Request Reset Password Client Area';
        $email['htmlContent'] = $html;
        $email['sender'] = ['name' => $smtp->nama, 'email' => $smtp->email];
        $email['to'] = [['email' => $user->email]];
        $email['replyTo'] = ['email' => $smtp->email, 'name' => $smtp->nama];

        $api->sendTransacEmail($email);
    }
}
