<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Every guest at the venue reaches a public host through ONE shared
        // public IP, so per-IP limits would throttle the whole room. Limit by
        // phone/player instead, keeping a generous per-IP ceiling as abuse cover.
        RateLimiter::for('per-phone', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone'));

            return [
                Limit::perMinute(15)->by('phone:'.($phone !== '' ? $phone : $request->ip())),
                Limit::perMinute(600)->by('phone-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('per-player', function (Request $request) {
            $playerId = (string) $request->input('player_id');

            return [
                Limit::perMinute(60)->by('player:'.($playerId !== '' ? $playerId : $request->ip())),
                Limit::perMinute(2000)->by('player-ip:'.$request->ip()),
            ];
        });
    }
}
