<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['password'];

    protected $casts = [
        'enabled' => 'boolean',
        'port' => 'integer',
        'password' => 'encrypted',
        'notifications' => 'array',
        'last_tested_at' => 'datetime',
    ];

    /** Emails the site can send, with the label shown in the admin. All are on by default. */
    public const TYPES = [
        'order_confirmed' => 'Product order confirmed (license key and download)',
        'payment_received' => 'Product payment received, awaiting verification',
        'payment_rejected' => 'Product payment not accepted',
        'enrollment_confirmed' => 'Course enrollment confirmed',
        'service_request' => 'Service request received',
        'admin_payment' => 'Alert admin: new product payment to verify',
        'admin_service_request' => 'Alert admin: new service request',
        'admin_contact' => 'Alert admin: new contact message',
    ];

    public static function current(): self
    {
        return static::firstOrNew(['id' => 1]);
    }

    public function wants(string $type): bool
    {
        return (bool) (($this->notifications ?? [])[$type] ?? true);
    }
}
