<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function index()
    {
        $requests = ServiceRequest::with(['service', 'user'])->latest()->paginate(20);
        return view('admin.requests.index', compact('requests'));
    }

    public function show(ServiceRequest $request)
    {
        $request->load(['service', 'user']);
        return view('admin.requests.show', compact('request'));
    }

    public function updateStatus(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'admin_notes' => 'nullable|string',
        ]);

        $serviceRequest->update($validated);

        return back()->with('success', 'Request status updated successfully.');
    }
}
