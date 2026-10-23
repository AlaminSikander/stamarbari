<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Runs the Laravel scheduler (routes/console.php) where no cron daemon exists. On Vercel a cron job
 * calls this every minute with "Authorization: Bearer <CRON_SECRET>".
 */
class ScheduleController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = (string) config('services.cron.secret');
        if ($secret === '' || ! hash_equals("Bearer {$secret}", (string) $request->header('Authorization'))) {
            return ApiResponse::error('Unauthorized.', [], 401);
        }

        Artisan::call('schedule:run');

        return ApiResponse::success(['output' => trim(Artisan::output())]);
    }
}
