<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

    public static function mediaPath(string $metaMessageId): string
    {
        return 'wa-inbox/'.hash('sha256', $metaMessageId);
    }

    public function hasMedia(): bool
    {
        return $this->message_type === 'image'
            && (string) $this->meta_message_id !== ''
            && Storage::disk('local')->exists(self::mediaPath((string) $this->meta_message_id));
    }
}
