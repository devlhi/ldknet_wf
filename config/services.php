<?php

return [
    'whatsapp_meta' => [
        // Catatan: App Secret Meta TIDAK di sini/.env — disimpan di setting gateway
        // (blob DB, menu WhatsApp Gateway), dibaca via WhatsAppGatewayResolver::metaAppSecret().
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
