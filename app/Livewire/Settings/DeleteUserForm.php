<?php

namespace App\Livewire\Settings;

use App\Livewire\Actions\Logout;
use App\Models\User;
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
        $user = Auth::user();

        // Hanya Super Admin yang bisa menghapus akun sendiri
        if (! $user->isSuperAdmin()) {
            session()->flash('delete-error', 'Anda tidak memiliki izin untuk menghapus akun sendiri. Hubungi Super Admin jika perlu menghapus akun Anda.');

            return;
        }

        // Prevent deleting the last super admin
        if ($user->isSuperAdmin() && User::where('role', 'super_admin')->count() <= 1) {
            session()->flash('delete-error', 'Tidak dapat menghapus akun Super Admin terakhir di sistem.');

            return;
        }

        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap($user, $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}
