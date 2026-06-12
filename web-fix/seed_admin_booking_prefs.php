<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\AgentBookingPreference;

$admin = User::where('type', 0)->first();
if (!$admin) {
    echo "no_admin_user\n";
    exit(1);
}

$pref = AgentBookingPreference::where(['admin_id' => $admin->id, 'is_admin_data' => 1])->first();
if ($pref) {
    echo "already_exists id={$pref->id}\n";
    exit(0);
}

AgentBookingPreference::create([
    'is_admin_data' => 1,
    'admin_id' => $admin->id,
    'meeting_duration_minutes' => 30,
    'lead_time_minutes' => 60,
    'buffer_time_minutes' => 15,
    'auto_confirm' => 0,
    'cancel_reschedule_buffer_minutes' => 60,
    'auto_cancel_after_minutes' => 1440,
    'auto_cancel_message' => 'Appointment auto-cancelled due to no response.',
    'daily_booking_limit' => 0,
    'availability_types' => 'phone,virtual,in_person',
    'anti_spam_enabled' => 1,
    'timezone' => 'Asia/Kolkata',
]);

echo "created_admin_booking_preferences\n";
