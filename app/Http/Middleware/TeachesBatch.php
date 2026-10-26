<?php

namespace App\Http\Middleware;

use App\Models\CourseBatch;
use App\Models\EducationSubmission;
use App\Support\Education;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Classroom routes: only course managers and the course's assigned instructors may continue.
class TeachesBatch
{
    public function handle(Request $request, Closure $next): Response
    {
        $batch = $request->route('batch');
        $submission = $request->route('submission');
        if ($submission instanceof EducationSubmission) {
            $batch = $submission->assignment->batch;
        }
        abort_unless($batch instanceof CourseBatch && Education::teaches($request->user(), $batch), 403, 'You are not an instructor for this course.');

        return $next($request);
    }
}
