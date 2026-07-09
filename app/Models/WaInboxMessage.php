<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaInboxMessage extends Model
{
    protected $table = 'wa_inbox_messages';

    public $timestamps = false;

    protected $fillable = [
        'from_number',
        'from_name',
        'direction',
        'body',
        'message_type',
        'meta_message_id',
        'status',
        'sent_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
