<?php

use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
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

// php artisan app:repair-keys
// Key codes are encrypted with APP_KEY. If APP_KEY changes (e.g. the API is redeployed as a
// new service), the old codes can't be decrypted any more and every page showing a key fails.
// They are demo codes, so unreadable ones are replaced with new fake codes. Runs on each deploy.
Artisan::command('app:repair-keys', function () {
    $replaced = 0;

    // Raw rows, not models: the model's 'encrypted' cast would throw on an unreadable code.
    DB::table('product_keys')->select(['id', 'code'])->chunkById(500, function ($keys) use (&$replaced) {
        foreach ($keys as $key) {
            try {
                Crypt::decryptString($key->code);
            } catch (DecryptException) {
                DB::table('product_keys')->where('id', $key->id)
                    ->update(['code' => Crypt::encryptString(ProductKey::fakeCode())]);
                $replaced++;
            }
        }
    });

    $this->info($replaced === 0 ? 'All key codes are readable.' : "Replaced {$replaced} unreadable key codes.");

    return 0;
})->purpose('Replace key codes that the current APP_KEY cannot decrypt');

// Delete expired login tokens once a day (they already stop working after SANCTUM_EXPIRATION).
Schedule::command('sanctum:prune-expired --hours=24')->daily();
