<?php

namespace App\Services;

use App\Models\SmtpSetting;
use App\Models\Website;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;

class NewAccountMailer
{
    public function sendAccountInfo(string $nama, string $emailTo, string $password): void
    {
        $smtp = SmtpSetting::firstOrFail();
        $website = Website::first();

        $html = view('emails.new-account', [
            'nama' => $nama,
            'email' => $emailTo,
            'password' => $password,
            'logo' => $website->logo ?? '',
            'titletext' => $website->title ?? '',
        ])->render();

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $smtp->key);
        $api = new TransactionalEmailsApi(new Client, $config);

        $email = new SendSmtpEmail;
        $email['subject'] = 'Akun Client Area anda telah aktif';
        $email['htmlContent'] = $html;
        $email['sender'] = ['name' => $smtp->nama, 'email' => $smtp->email];
        $email['to'] = [['email' => $emailTo]];

        $api->sendTransacEmail($email);
    }
}
