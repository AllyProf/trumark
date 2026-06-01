<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function surveyLink(string $uuid): string
    {
        $baseUrl = rtrim(self::get('survey_public_url', 'https://trumark.emca.tech'), '/');

        return $baseUrl . '/feedback/' . $uuid;
    }
}
