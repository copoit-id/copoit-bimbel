<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Tentor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TentorAccountService
{
    public function sync(Tentor $tentor, array $accountData): User
    {
        $user = $tentor->user ?? new User();
        $isNewAccount = ! $user->exists;

        $user->fill([
            'name' => $tentor->name,
            'email' => $tentor->email,
            'username' => $accountData['username'],
            'status' => $tentor->is_active ? 'aktif' : 'nonaktif',
            'role' => 'tutor',
        ]);

        if (! empty($accountData['password'])) {
            $user->password = Hash::make($accountData['password']);
        }

        if ($isNewAccount) {
            $user->email_verified_at = now();
        }

        $user->save();

        $role = Role::query()->where('slug', 'tutor')->first();
        if ($role) {
            $user->roles()->sync([$role->id]);
        }

        if ((int) $tentor->user_id !== (int) $user->id) {
            $tentor->update(['user_id' => $user->id]);
        }

        return $user;
    }
}
