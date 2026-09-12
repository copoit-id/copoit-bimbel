<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserPasswordResetService
{
    /**
     * Reset a password to the local part of the user's email address.
     */
    public function reset(User $user): void
    {
        $user->forceFill([
            'password' => Hash::make($this->defaultPasswordFor($user->email)),
            'remember_token' => null,
        ])->save();
    }

    /**
     * Reset users in bounded batches to avoid loading all selected users at once.
     *
     * @param Builder<User> $users
     */
    public function resetMany(Builder $users): int
    {
        $resetCount = 0;

        $users->chunkById(100, function (Collection $users) use (&$resetCount): void {
            $users->each(function (User $user) use (&$resetCount): void {
                $this->reset($user);
                $resetCount++;
            });
        });

        return $resetCount;
    }

    public function defaultPasswordFor(string $email): string
    {
        return Str::before($email, '@');
    }
}
