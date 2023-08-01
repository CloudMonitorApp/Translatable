<?php

namespace CloudMonitor\Translatable;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class TranslatableCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return json_decode($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => json_encode($value, JSON_UNESCAPED_UNICODE)];
    }
}
