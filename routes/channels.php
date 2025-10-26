<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. These channels are used by Laravel Reverb and
| Laravel Echo to authenticate and authorize users who are listening
| to private or presence channels in real time.
|
*/

/**
 * 🔒 Private channel for each authenticated user
 * 
 * Example channel: App.Models.User.5
 * Laravel Echo automatically subscribes to this when you call:
 *     Echo.private('App.Models.User.' + userId)
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * 🔔 Example: Presence channel for a class (useful if you want
 * real-time online status, chat, or collaboration per classroom).
 * 
 * Only authenticated users who belong to the same class can join.
 */
Broadcast::channel('classroom.{classId}', function ($user, $classId) {
    return (int) $user->class_id === (int) $classId
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});

/**
 * 🧾 Example: Admin-only notification broadcast channel
 * (Optional — for dashboards that receive system-wide alerts)
 */
Broadcast::channel('admin.notifications', function ($user) {
    return $user->role === 'Admin';
});
