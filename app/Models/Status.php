<?php

namespace App\Models;

use App\Http\Resources\StatusResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
    ];

    public $timestamps = false;

    public $transformer = StatusResource::class;

    // Statuses
    const ENABLED = 'enabled';
    const DISABLED = 'disabled';

    // Modules
    const GENERAL = 'general';

    const STATUSES = [
        ['name' => self::ENABLED, 'type' => self::GENERAL],
        ['name' => self::DISABLED, 'type' => self::GENERAL],
    ];

    public function scopeEnabled($query)
    {
        return $query->where('name', self::ENABLED);
    }

    public function scopeDisabled($query)
    {
        return $query->where('name', self::DISABLED);
    }
}
