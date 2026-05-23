<?php

namespace Domain\Order\Models\Entities;

use Illuminate\Database\Eloquent\Model;

class UnprocessedOrder extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
