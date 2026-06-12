<?php

namespace App\Plugins\RenewalReminder\Http\Controllers\Admin;

use App\Plugins\RenewalReminder\Models\RenewalIntention;
use App\Plugins\RenewalReminder\Models\RenewalReminderLog;
use App\Plugins\RenewalReminder\Models\RenewalSettings;
use App\Plugins\RenewalReminder\Services\RenewalReminderService;
use App\Plugins\RentalAgreement\Models\RentalAgreement;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RenewalReminderAdminController extends Controller
{
    public function __construct(private readonly RenewalReminderService $service) {}

    // ── Dashboard ──────────────────────────────────────────────────────

    public function dashboard()
    {
        $settings = RenewalSettings::current();

        // Agreements expiring in next 60 days
        $expiring = RentalAgreement::whereIn('status', ['generated', 'signed_copy_uploaded'])
            ->whereDate('end_date', '<=', now()->addDays(60)->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('end_date')
            ->get()
            ->map(function ($a) use ($settings) {
                $intention = RenewalIntention::where('agreement_id', $a->id)->first();
                $lastReminder = RenewalReminderLog::where('agreement_id', $a->id)->latest('sent_at')->first();
                return [
                    'agreement'    => $a,
                    'days_left'    => (int) now()->diffInDays($a->end_date),
                    'intention'    => $intention,
                    'last_reminder'=> $lastReminder,
                    'suggested_rent'=> $settings->suggestNewRent($a->monthly_rent),
                ];
            });

        $stats = [
            'expiring_60' => $expiring->count(),
            'wants_renewal'   => RenewalIntention::where('intention', 'wants_renewal')->count(),
            'wants_to_vacate' => RenewalIntention::where('intention', 'wants_to_vacate')->count(),
            'undecided'       => RenewalIntention::where('intention', 'undecided')->count(),
            'reminders_sent'  => RenewalReminderLog::whereDate('sent_at', '>=', now()->subDays(7))->count(),
        ];

        // All recorded intentions — not just expiring agreements
        $allIntentions = RenewalIntention::with('agreement')
            ->orderByDesc('responded_at')
            ->get();

        return view('renewal-reminder::admin.dashboard', compact('expiring', 'stats', 'settings', 'allIntentions'));
    }

    // ── Settings ───────────────────────────────────────────────────────

    public function settings()
    {
        $settings = RenewalSettings::current();
        return view('renewal-reminder::admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'reminder_days'       => 'required|array',
            'reminder_days.*'     => 'integer|min:1|max:180',
            'rent_increase_type'  => 'required|in:percentage,fixed,none',
            'rent_increase_value' => 'required|numeric|min:0',
            'send_email'          => 'boolean',
            'send_push'           => 'boolean',
            'send_whatsapp'       => 'boolean',
        ]);

        RenewalSettings::current()->update([
            'reminder_days'       => $request->reminder_days,
            'rent_increase_type'  => $request->rent_increase_type,
            'rent_increase_value' => $request->rent_increase_value,
            'send_email'          => $request->boolean('send_email'),
            'send_push'           => $request->boolean('send_push'),
            'send_whatsapp'       => $request->boolean('send_whatsapp'),
        ]);

        return back()->with('success', __('Renewal settings updated.'));
    }

    // ── Create renewal ─────────────────────────────────────────────────

    public function renew(Request $request, RentalAgreement $agreement)
    {
        $request->validate([
            'new_rent' => 'nullable|integer|min:1',
        ]);

        $renewal = $this->service->createRenewal(
            $agreement,
            $request->new_rent ? (int) $request->new_rent : null,
            auth()->id()
        );

        return redirect()
            ->route('admin.rental-agreements.show', $renewal)
            ->with('success', __('Agreement renewed. New agreement: ') . $renewal->agreement_number);
    }

    // ── Send reminder manually ─────────────────────────────────────────

    public function runCheck()
    {
        $report = $this->service->runDailyCheck();
        return back()->with('success', __('Reminder check done. Sent: ') . $report['reminders_sent']);
    }
}
