<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateMessage extends Model
{
    protected $table = 'template_message';

    public $timestamps = false;

    protected $guarded = ['id'];
}
