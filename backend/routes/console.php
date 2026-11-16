<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// php artisan app:make-admin someone@example.com
// Gives an existing account admin rights (deliberately impossible from the website).
Artisan::command('app:make-admin {email}', function (string $email) {
    $user = User::where('email', $email)->first();
    if (! $user) {
        $this->error("No account with email {$email}. Register on the site first.");

        return 1;
    }

    $user->forceFill(['is_admin' => true])->save();
    $this->info("{$email} is now an admin.");

    return 0;
})->purpose('Make an existing user an admin');

// Delete expired login tokens once a day (they already stop working after SANCTUM_EXPIRATION).
Schedule::command('sanctum:prune-expired --hours=24')->daily();
