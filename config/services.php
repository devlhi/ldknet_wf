<?php

return [
    'whatsapp_meta' => [
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
