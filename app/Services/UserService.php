<?php

namespace App\Services;

use App\Models\Corporation;
use App\Models\User;
use App\Notifications\WelcomeSetPasswordNotification;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserService
{
    public const DELETE_DELETED = 'deleted';
    public const DELETE_NOT_FOUND = 'not_found';
    public const DELETE_CURRENT_USER_FORBIDDEN = 'current_user_forbidden';

    /**
     * @param array $data
     * @return User
     */
    public function store(array $data): User
    {
        $user = null;

        if (isset($data['id'])) {
            $user = User::query()->find($data['id']);
        }

        $isNew = !$user;

        if (!$user) {
            $user = new User();
            $user->password = Hash::make(Str::random(48));
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (isset($data['corporation_id'])) {
            $user->corporation_id = $data['corporation_id'];
        } elseif ($isNew) {
            // Auto-assign to first corporation when creating new user
            $firstCorporation = Corporation::query()->first();
            if ($firstCorporation) {
                $user->corporation_id = $firstCorporation->id;
            }
        }
        $user->save();

        if ($isNew) {
            $broker = Password::broker();

            if ($broker instanceof PasswordBroker) {
                $token = $broker->createToken($user);
                $user->notify(new WelcomeSetPasswordNotification($token));
            }
        }

        return $user;
    }

    /**
     * @param int $id
     * @param int|null $currentUserId
     * @return string
     */
    public function deleteById(int $id, ?int $currentUserId): string
    {
        $user = User::query()->find($id);

        if (!$user) {
            return self::DELETE_NOT_FOUND;
        }

        if ($currentUserId !== null && $user->id === $currentUserId) {
            return self::DELETE_CURRENT_USER_FORBIDDEN;
        }

        $user->delete();

        return self::DELETE_DELETED;
    }
}
