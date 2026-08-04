<?php

namespace App\Services;

use App\Models\Tentor;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TutorProfileService
{
    public function sync(User $user): void
    {
        if (! $user->isTutor()) {
            Tentor::query()->where('user_id', $user->id)->update(['user_id' => null]);

            return;
        }

        $tentor = Tentor::query()
            ->where('user_id', $user->id)
            ->orWhere(function ($query) use ($user): void {
                $query->whereNull('user_id')->where('email', $user->email);
            })
            ->first();

        if ($tentor) {
            if ($tentor->user_id && (int) $tentor->user_id !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'email' => 'Email ini sudah terhubung ke akun Tutor lain.',
                ]);
            }

            $tentor->update(['user_id' => $user->id]);

            return;
        }

        Tentor::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => true,
        ]);
    }
}
