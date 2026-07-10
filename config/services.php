<?php

return [
    'whatsapp_meta' => [
        // App Secret aplikasi Meta. Bila diisi (via env META_APP_SECRET), webhook
        // memvalidasi header X-Hub-Signature-256. Bila kosong, validasi dilewati
        // (fail-open) agar instalasi lama tidak langsung berhenti menerima pesan.
        'app_secret' => env('META_APP_SECRET'),
        'verify_token' => 'landaknet-meta-webhook',
        'graph_url' => 'https://graph.facebook.com/v20.0',
        'templates' => [
            'tagihan' => 'notif_tagihan',
            'pengingat' => 'notif_pengingat',
            'terbayar' => 'notif_tagihan_terbayar',
            'pelanggan_baru' => 'notif_pelanggan_baru',
        ],
        'template_languages' => [
            'tagihan' => 'id',
            'pengingat' => 'id',
            'terbayar' => 'id',
            'pelanggan_baru' => 'id',
        ],
    ],
];
