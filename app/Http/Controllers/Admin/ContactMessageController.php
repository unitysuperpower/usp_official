<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactReplyMail;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::with('repliedBy')->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $messages = $query->paginate(20)->withQueryString();
        $pendingCount = ContactMessage::pending()->count();
        $unreadCount = ContactMessage::unread()->count();
        $repliedCount = ContactMessage::where('status', 'replied')->count();

        return \App\Support\ReactPage::render('admin.contact-messages.index', compact('messages', 'pendingCount', 'unreadCount', 'repliedCount'));
    }

    public function show(ContactMessage $message)
    {
        $message->markAsRead();
        return \App\Support\ReactPage::render('admin.contact-messages.show', compact('message'));
    }

    public function update(Request $request, ContactMessage $message)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,read,replied,archived',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validated['status'] === 'replied') {
            $validated['replied_by'] = auth()->id();
        }

        $message->update($validated);

        return back()->with('success', 'Contact message updated successfully.');
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();
        return redirect()->route('admin.contact-messages.index')->with('success', 'Contact message deleted successfully.');
    }

    public function reply(Request $request, ContactMessage $message)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'reply_message' => 'required|string|min:10',
            'mark_as_replied' => 'nullable|boolean',
        ]);

        // Here you would typically send an email using Mail facade
        // For now, we'll just update the status and add admin notes
        
        // Prepare email content (you can integrate with Mail facade later)
        $replyContent = $validated['reply_message'];
        
        // Update message status if checkbox is checked
        if ($request->has('mark_as_replied')) {
            $message->update([
                'status' => 'replied',
                'replied_by' => auth()->id(),
                'admin_notes' => ($message->admin_notes ? $message->admin_notes . "\n\n" : '') 
                    . "Reply sent on " . now()->format('Y-m-d H:i:s') . ":\n" . $replyContent
            ]);
        }

        Mail::to($message->email)->send(new ContactReplyMail($message, $validated['subject'], $replyContent));

        return back()->with('reply_success', 'Reply sent successfully!');
    }
}
