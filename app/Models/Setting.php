<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function asArray(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }

    public static function getCompany(): array
    {
        return [
            'name'     => static::get('company_name', 'Gigateam Solutions Limited'),
            'tagline'  => static::get('company_tagline', 'Connected & Secured'),
            'address1' => static::get('company_address', 'White Angle House, 1st Floor – Suite 62'),
            'po_box'   => static::get('company_po_box', 'Nairobi'),
            'phone1'   => static::get('company_phone1', '+254 111292948'),
            'phone2'   => static::get('company_phone2', '718811661'),
            'email1'   => static::get('company_email1', 'sales@gigateamltd.com'),
            'email2'   => static::get('company_email2', 'gigateamsolutions@gmail.com'),
            'website'  => static::get('company_website', 'www.gigateamsolutions.co.ke'),
            'kra_pin'  => static::get('company_kra_pin', 'P051892936Q'),
            'vat_no'   => static::get('company_vat_no', 'P051892936Q'),
        ];
    }
}