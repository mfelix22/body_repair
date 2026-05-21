<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;

class NotificationService
{
    /**
     * Send a notification to a specific user.
     */
    public static function send(int $userId, string $type, string $title, string $message, ?string $url = null): void
    {
        UserNotification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'url'     => $url,
        ]);
    }

    /**
     * Send a notification to all users who have any of the given roles.
     * Optionally exclude a specific user (e.g. the actor performing the action).
     */
    public static function sendToRole(
        string|array $roles,
        string $type,
        string $title,
        string $message,
        ?string $url = null,
        ?int $excludeUserId = null
    ): void {
        $roles = (array) $roles;

        User::all()
            ->filter(function (User $user) use ($roles, $excludeUserId) {
                if ($excludeUserId && $user->id === $excludeUserId) {
                    return false;
                }
                return $user->hasAnyRole($roles);
            })
            ->each(function (User $user) use ($type, $title, $message, $url) {
                static::send($user->id, $type, $title, $message, $url);
            });
    }
}
