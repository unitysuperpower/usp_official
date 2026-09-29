<?php

namespace App\Livewire\Settings;

use App\Livewire\Actions\Logout;
use App\Models\CourseEnrollment;
use App\Support\Education;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeleteUserForm extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        Education::ensure(! CourseEnrollment::where('user_id', auth()->id())->exists(), 'Your account has education records. Contact support to arrange account closure.', 'password');

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}
