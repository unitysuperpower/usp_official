<?php

namespace App\Support;

use App\Mail\NotificationMail;
use App\Models\ContactMessage;
use App\Models\CourseEnrollment;
use App\Models\ProductOrder;
use App\Models\ProductPayment;
use App\Models\ServiceRequest;

/** Builds and sends the customer and admin emails for orders, payments, enrollments and requests. */
class SiteNotifications
{
    private static function money(int $minor, string $currency): string
    {
        return $currency.' '.number_format($minor / 100, 2);
    }

    private static function method(?string $key): ?string
    {
        if (! $key) {
            return null;
        }
        if ($key === 'jazzcash_online') {
            return 'JazzCash (online)';
        }
        $methods = Education::methods();

        return $methods[$key]['label'] ?? $methods[$key]['name'] ?? ucwords(str_replace('_', ' ', $key));
    }

    private static function sites(?int $limit): string
    {
        return $limit === null ? 'Unlimited websites' : $limit.' '.($limit === 1 ? 'website' : 'websites');
    }

    /** Product payment approved (manually or by JazzCash): send the license and download details. */
    public static function orderConfirmed(ProductOrder $order): void
    {
        $order->loadMissing('user', 'license');
        $license = $order->license ?? ($order->renewal_license_id ? \App\Models\ProductLicense::find($order->renewal_license_id) : null);
        $renewal = (bool) $order->renewal_license_id;
        SiteMail::send($order->user?->email, 'order_confirmed', new NotificationMail(
            subjectLine: ($renewal ? 'License renewed: ' : 'Order confirmed: ').$order->product_name,
            heading: $renewal ? 'Your license has been renewed' : 'Thank you! Your order is confirmed',
            lines: [
                'Hello '.($order->user?->name ?: 'there').',',
                $renewal
                    ? 'We received your payment and extended your license for '.$order->product_name.'.'
                    : 'We received your payment for '.$order->product_name.'. Your license is active and ready to use.',
                'Download the plugin and manage the websites using this license from your account.',
            ],
            details: [
                'Product' => $order->product_name,
                'Plan' => $order->plan_name,
                'Websites' => self::sites($order->site_limit),
                'Amount paid' => self::money((int) $order->amount_minor, $order->currency),
                'Order reference' => $order->reference,
                'License key' => $license?->key_secret,
                'Valid until' => $license?->expires_at?->format('j F Y'),
            ],
            actionLabel: 'Download & manage license',
            actionUrl: $license ? url('/account/products/licenses/'.$license->id) : url('/account/products'),
            footnote: 'Keep your license key private. In WordPress, enter it on the plugin\'s license page to receive updates and support.',
        ));
    }

    /** Customer submitted a manual payment: confirm receipt and alert the admin. */
    public static function paymentReceived(ProductOrder $order, ProductPayment $payment): void
    {
        $order->loadMissing('user');
        $details = [
            'Product' => $order->product_name,
            'Plan' => $order->plan_name,
            'Amount' => self::money((int) $order->amount_minor, $order->currency),
            'Payment method' => self::method($payment->method),
            'Transaction reference' => $payment->transaction_reference,
            'Order reference' => $order->reference,
        ];
        SiteMail::send($order->user?->email, 'payment_received', new NotificationMail(
            subjectLine: 'Payment received for '.$order->product_name,
            heading: 'We received your payment details',
            lines: [
                'Hello '.($order->user?->name ?: 'there').',',
                'Thank you for your order. Our team is verifying your payment. You will receive your license key by email as soon as it is approved, usually within one business day.',
            ],
            details: $details,
            actionLabel: 'View your order',
            actionUrl: url('/account/products/orders/'.$order->reference),
        ));
        SiteMail::send(SiteMail::adminAddress(), 'admin_payment', new NotificationMail(
            subjectLine: 'Payment to verify: '.$order->product_name.' ('.($order->user?->name ?? 'customer').')',
            heading: 'A new product payment needs verification',
            lines: ['Check the payment proof, then approve the payment to issue the license automatically.'],
            details: ['Customer' => trim(($order->user?->name ?? '').' <'.($order->user?->email ?? '').'>')] + $details,
            actionLabel: 'Review payment',
            actionUrl: url('/admin/store/orders/'.$order->reference),
            replyToAddress: $order->user?->email,
        ));
    }

    public static function paymentRejected(ProductOrder $order, ProductPayment $payment): void
    {
        $order->loadMissing('user');
        SiteMail::send($order->user?->email, 'payment_rejected', new NotificationMail(
            subjectLine: 'Payment not accepted for '.$order->product_name,
            heading: 'We could not verify your payment',
            lines: [
                'Hello '.($order->user?->name ?: 'there').',',
                'We were unable to confirm the payment you submitted for '.$order->product_name.'. Please check the details below, then submit the payment again from your order or reply to this email.',
            ],
            details: [
                'Order reference' => $order->reference,
                'Transaction reference' => $payment->transaction_reference,
                'Reason' => $payment->review_note,
            ],
            actionLabel: 'Open your order',
            actionUrl: url('/account/products/orders/'.$order->reference),
            replyToAddress: SiteMail::adminAddress(),
        ));
    }

    /** Course payment approved: the student's place is confirmed. */
    public static function enrollmentConfirmed(CourseEnrollment $enrollment): void
    {
        $enrollment->loadMissing('student', 'batch.course');
        $batch = $enrollment->batch;
        $course = $batch?->course;
        SiteMail::send($enrollment->student?->email, 'enrollment_confirmed', new NotificationMail(
            subjectLine: 'Enrollment confirmed: '.($course->title ?? 'your course'),
            heading: 'Welcome aboard! Your place is confirmed',
            lines: [
                'Hello '.($enrollment->student?->name ?: 'there').',',
                'Your payment was received and your enrollment in '.($course->title ?? 'the course').' is confirmed. Your learning workspace is ready with the schedule, lessons and class links.',
            ],
            details: [
                'Course' => $course?->title,
                'Batch' => $batch?->name,
                'Starts' => $batch?->starts_on ? \Illuminate\Support\Carbon::parse($batch->starts_on)->format('j F Y') : null,
                'Schedule' => $batch?->schedule,
                'Location' => $batch?->location,
                'Fee paid' => $enrollment->fee_minor ? self::money((int) $enrollment->fee_minor, $enrollment->currency ?: 'PKR') : null,
            ],
            actionLabel: 'Open my learning workspace',
            actionUrl: url('/learn/'.$enrollment->id),
            replyToAddress: SiteMail::adminAddress(),
        ));
    }

    /** New service request from the website: confirm to the customer and alert the admin. */
    public static function serviceRequestReceived(ServiceRequest $request): void
    {
        $request->loadMissing('service');
        $service = $request->service?->title ?? 'our services';
        SiteMail::send($request->email, 'service_request', new NotificationMail(
            subjectLine: 'We received your request: '.$service,
            heading: 'Thank you for contacting us',
            lines: [
                'Hello '.($request->name ?: 'there').',',
                'Thank you for your interest in '.$service.'. Our team will review your requirements and contact you within one business day with the next steps.',
            ],
            details: [
                'Service' => $request->service?->title,
                'Starting price' => $request->service?->price ? '$'.number_format((float) $request->service->price, 2) : null,
                'Your message' => \Illuminate\Support\Str::limit((string) $request->message, 300),
                'Request number' => '#'.$request->id,
            ],
            actionLabel: $request->service ? 'View service details' : null,
            actionUrl: $request->service ? url('/services/'.$request->service->slug) : null,
            replyToAddress: SiteMail::adminAddress(),
        ));
        SiteMail::send(SiteMail::adminAddress(), 'admin_service_request', new NotificationMail(
            subjectLine: 'New service request: '.$service.' from '.$request->name,
            heading: 'New service request',
            lines: ['Reply to this email to answer the customer directly.'],
            details: [
                'Service' => $request->service?->title,
                'Name' => $request->name,
                'Email' => $request->email,
                'Phone' => $request->phone,
                'Company' => $request->company,
                'Message' => \Illuminate\Support\Str::limit((string) $request->message, 1000),
            ],
            actionLabel: 'Open in admin',
            actionUrl: url('/admin/requests/'.$request->id),
            replyToAddress: $request->email,
        ));
    }

    /** The project became visible to the client's account. */
    public static function projectShared(\App\Models\Project $project): void
    {
        $project->loadMissing('customer');
        SiteMail::send($project->customer?->email, 'project_shared', new NotificationMail(
            subjectLine: 'Your project workspace is ready: '.$project->title,
            heading: 'Follow your project online',
            lines: [
                'Hello '.($project->customer?->name ?: 'there').',',
                'Your project "'.$project->title.'" now has an online workspace. You can follow milestones, read updates, share files and approve deliverables there.',
            ],
            details: ['Project' => $project->title, 'Target date' => $project->due_on?->format('j F Y')],
            actionLabel: 'Open your project',
            actionUrl: url('/projects/'.$project->id),
            replyToAddress: SiteMail::adminAddress(),
        ));
    }

    /** A milestone was sent to the client for approval. */
    public static function milestoneReview(\App\Models\Project $project, \App\Models\ProjectMilestone $milestone): void
    {
        $project->loadMissing('customer');
        SiteMail::send($project->customer?->email, 'milestone_review', new NotificationMail(
            subjectLine: 'Please review: '.$milestone->title,
            heading: 'A milestone is ready for your review',
            lines: [
                'Hello '.($project->customer?->name ?: 'there').',',
                'We have finished "'.$milestone->title.'" for '.$project->title.'. Please review it and approve it, or tell us what to change.',
            ],
            details: [
                'Project' => $project->title,
                'Milestone' => $milestone->title,
                'Details' => \Illuminate\Support\Str::limit((string) $milestone->description, 500),
            ],
            actionLabel: 'Review and approve',
            actionUrl: url('/projects/'.$project->id),
            replyToAddress: SiteMail::adminAddress(),
        ));
    }

    /** The client approved a milestone or asked for changes. */
    public static function milestoneReviewed(\App\Models\Project $project, \App\Models\ProjectMilestone $milestone, bool $approved): void
    {
        $project->loadMissing('assignee', 'customer');
        $to = $project->assignee?->email ?: SiteMail::adminAddress();
        SiteMail::send($to, 'admin_milestone_review', new NotificationMail(
            subjectLine: ($approved ? 'Approved: ' : 'Changes requested: ').$milestone->title.' ('.$project->title.')',
            heading: $approved ? 'The client approved a milestone' : 'The client requested changes',
            lines: [($project->customer?->name ?: 'The client').($approved ? ' approved this milestone.' : ' asked for changes to this milestone.')],
            details: ['Project' => $project->title, 'Milestone' => $milestone->title, 'Client feedback' => $milestone->approval_note],
            actionLabel: 'Open project',
            actionUrl: url('/admin/projects/'.$project->id),
            replyToAddress: $project->customer?->email,
        ));
    }

    public static function contactReceived(ContactMessage $message): void
    {
        SiteMail::send(SiteMail::adminAddress(), 'admin_contact', new NotificationMail(
            subjectLine: 'New contact message: '.$message->subject,
            heading: 'New message from the contact form',
            lines: ['Reply to this email to answer '.$message->name.' directly.'],
            details: [
                'Name' => $message->name,
                'Email' => $message->email,
                'Phone' => $message->phone,
                'Subject' => $message->subject,
                'Message' => \Illuminate\Support\Str::limit((string) $message->message, 1500),
            ],
            actionLabel: 'Open in admin',
            actionUrl: url('/admin/contact-messages/'.$message->id),
            replyToAddress: $message->email,
        ));
    }
}
