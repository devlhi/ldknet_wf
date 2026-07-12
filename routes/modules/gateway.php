<?php

use App\Http\Controllers\Admin\GatewayController;
use App\Http\Controllers\Admin\TemplateMessageController;
use App\Http\Controllers\Admin\WhatsAppInboxController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    // Payment Gateway
    Route::get('gateway/payment', [GatewayController::class, 'payment']);
    Route::get('gateway/payment/method', [GatewayController::class, 'methodpayment']);
    Route::post('gateway/payment/method/add', [GatewayController::class, 'addpaymentmethod']);
    Route::post('gateway/payment/set-default', [GatewayController::class, 'setDefault']);
    Route::post('gateway/payment/method/update', [GatewayController::class, 'updateMethod']);
    Route::post('gateway/payment/update', [GatewayController::class, 'updateMerchant']);

    // Email Gateway (Brevo)
    Route::get('gateway/email', [GatewayController::class, 'smtp']);
    Route::get('gateway/email/message', [GatewayController::class, 'smtpTest']);
    Route::post('gateway/email/message/send', [GatewayController::class, 'sendsmtp']);

    // Whatsapp Gateway (path lama CI4: add() -> GET|POST)
    Route::match(['get', 'post'], 'gateway/whatsapp', [GatewayController::class, 'whatsapp']);
    Route::match(['get', 'post'], 'gateway/whatsapp/setup', [GatewayController::class, 'whatsappSetup']);
    Route::post('gateway/whatsapp/update', [GatewayController::class, 'whatsappUpdate']);
    Route::post('gateway/whatsapp/update/addon', [GatewayController::class, 'whatsappUpdateAddon']);

    // Whatsapp
    Route::match(['get', 'post'], 'whatsapp', [GatewayController::class, 'whatsapp']);
    Route::match(['get', 'post'], 'whatsapp/edit/{any}', [GatewayController::class, 'whatsappEdit']);
    Route::match(['get', 'post'], 'whatsapp/setup', [GatewayController::class, 'whatsappSetup']);
    Route::post('whatsapp/add/number', [GatewayController::class, 'whatsappAdd']);
    Route::match(['get', 'post'], 'whatsapp/message/text-message', [GatewayController::class, 'whatsappTextMessage']);
    Route::post('whatsapp/message/text-message/send', [GatewayController::class, 'whatsappSendMessage']);
    Route::get('whatsapp/inbox', [WhatsAppInboxController::class, 'index']);
    Route::get('whatsapp/inbox/poll', [WhatsAppInboxController::class, 'poll']);
    Route::get('whatsapp/inbox/media/{message}', [WhatsAppInboxController::class, 'media']);
    Route::post('whatsapp/inbox/send', [WhatsAppInboxController::class, 'send']);
    Route::post('whatsapp/inbox/send-image', [WhatsAppInboxController::class, 'sendImage']);
    Route::match(['get', 'post'], 'whatsapp/template/message', [GatewayController::class, 'whatsappTemplate']);
    Route::post('whatsapp/update/setting', [GatewayController::class, 'whatsappUpdateSetting']);
    Route::post('whatsapp/update/number', [GatewayController::class, 'whatsappUpdateNumber']);
    Route::post('whatsapp/update/template', [GatewayController::class, 'whatsappUpdateTemplate']);
    Route::post('whatsapp/send/message', [GatewayController::class, 'whatsappSendMessage']);
    Route::get('whatsapp/meta/templates', [GatewayController::class, 'whatsappMetaTemplates']);
    Route::post('whatsapp/meta/create-templates', [GatewayController::class, 'whatsappMetaCreateTemplates']);
    Route::post('whatsapp/meta/test', [GatewayController::class, 'whatsappMetaTest']);

    // SMTP Setting
    Route::get('setting/smtp', [GatewayController::class, 'smtp']);
    Route::post('setting/smtp/update', [GatewayController::class, 'smtpUpdate']);
    Route::post('setting/smtp/send', [GatewayController::class, 'sendsmtp']);
    Route::match(['get', 'post'], 'setting/smtp/test', [GatewayController::class, 'smtpTest']);

    // Webhook setting
    Route::get('webhook', [GatewayController::class, 'webhook']);
    Route::post('webhook/update', [GatewayController::class, 'webhookUpdate']);

    // Template Message
    Route::get('template/message', [TemplateMessageController::class, 'templateMessage']);
    Route::post('template/update', [TemplateMessageController::class, 'templateMessageUpdate']);
});
