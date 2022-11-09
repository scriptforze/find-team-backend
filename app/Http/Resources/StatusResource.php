<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StatusResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
        ];
    }

    public static function originalAttribute($index)
    {
        $attributes = [
            'id' => 'id',
            'name' => 'name',
            'type' => 'type',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }
}
