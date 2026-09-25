<?php

namespace App\Http\Controllers;

use App\Events\NewContactMessage as NewContactMessageEvent;
use App\Models\ContactMessage;
use App\Models\Service;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        return \App\Support\ReactPage::render('contact');
    }

    public function store(Request $request)
    {
        // Check if this is a service request or general contact
        if ($request->has('service_id')) {
            return $this->storeServiceRequest($request);
        }

        return $this->storeContactMessage($request);
    }

    protected function storeServiceRequest(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $validated['user_id'] = $request->user()?->id;

        ServiceRequest::create($validated);

        return back()->with('success', 'Your request has been submitted successfully. We will contact you soon.');
    }

    protected function storeContactMessage(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        $contactMessage = ContactMessage::create($validated);

        // Broadcast the new contact message to admins
        broadcast(new NewContactMessageEvent($contactMessage));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you for contacting us! We will get back to you soon.',
            ]);
        }

        return back()->with('success', 'Thank you for contacting us! We will get back to you soon.');
    }
}
