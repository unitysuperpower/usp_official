<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PageView extends Model
{
    protected $fillable = [
        'url',
        'page_type',
        'viewable_id',
        'viewable_type',
        'user_agent',
        'ip_address',
        'referrer',
        'device_type',
        'browser',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function track($pageType, $viewable = null)
    {
        $userAgent = request()->userAgent() ?? '';

        return static::create([
            'url' => request()->fullUrl(),
            'page_type' => $pageType,
            'viewable_id' => $viewable?->id,
            'viewable_type' => $viewable ? get_class($viewable) : null,
            'user_agent' => $userAgent,
            'ip_address' => request()->ip(),
            'referrer' => request()->header('referer'),
            'device_type' => static::detectDeviceType($userAgent),
            'browser' => static::detectBrowser($userAgent),
            'viewed_at' => now(),
        ]);
    }

    private static function detectDeviceType($userAgent)
    {
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        } elseif (preg_match('/mobile/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private static function detectBrowser($userAgent)
    {
        if (preg_match('/edge|edg\//i', $userAgent)) {
            return 'Edge';
        }
        if (preg_match('/opera|opr\//i', $userAgent)) {
            return 'Opera';
        }
        if (preg_match('/chrome/i', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/firefox/i', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/safari/i', $userAgent)) {
            return 'Safari';
        }

        return 'Other';
    }
}
