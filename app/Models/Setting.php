<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'description'];

    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key
     */
    public static function set($key, $value)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Check if initial inventory mode is active
     */
    public static function isInitialMode()
    {
        return self::get('initial_inventory_mode') === 'true';
    }

    /**
     * Get initial cash balance
     */
    public static function getInitialCash()
    {
        return (float) self::get('initial_cash_balance', 0);
    }

    /**
     * Get initial Nequi balance
     */
    public static function getInitialNequi()
    {
        return (float) self::get('initial_nequi_balance', 0);
    }

    /**
     * Get initial Bancolombia balance
     */
    public static function getInitialBancolombia()
    {
        return (float) self::get('initial_bancolombia_balance', 0);
    }

    /**
     * Get reset timestamps for bases
     */
    public static function getResetTimestamp($type)
    {
        return self::get("initial_{$type}_at", '1970-01-01 00:00:00');
    }

    /**
     * Set reset timestamp for a base
     */
    public static function setResetTimestamp($type, $timestamp = null)
    {
        return self::set("initial_{$type}_at", $timestamp ?? now()->toDateTimeString());
    }

    /**
     * Check if initial mode has been closed
     */
    public static function isInitialModeClosed()
    {
        return self::get('initial_mode_closed_at') !== null;
    }
}
