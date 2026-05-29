<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    public const TYPE_NASIONAL = 'nasional';

    public const TYPE_CUTI_BERSAMA = 'cuti_bersama';

    public const TYPES = [
        self::TYPE_NASIONAL      => 'Libur Nasional',
        self::TYPE_CUTI_BERSAMA  => 'Cuti Bersama',
    ];

    protected $fillable = [
        'date',
        'name',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
