<?php

namespace App\Plugins\Whatsapp\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Whatsapp\Models\WaMessage;
use App\Plugins\Whatsapp\Support\MetaApiErrorFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WhatsappDeliveryController extends Controller
{
    private function denyUnlessInbox(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('inbox', 'whatsapp')) {
            abort(403);
        }
    }

    public function index(Request $request): View
    {
        $this->denyUnlessInbox();

        $days = max(1, min(90, (int) $request->query('days', 7)));
        $since = now()->subDays($days);

        $summary = WaMessage::query()
            ->where('direction', 'out')
            ->where('created_at', '>=', $since)
            ->select([
                'template_key',
                'status',
                DB::raw('COUNT(*) as total'),
            ])
            ->groupBy('template_key', 'status')
            ->orderBy('template_key')
            ->get();

        $byEvent = [];
        foreach ($summary as $row) {
            $key = $row->template_key ?: __('whatsapp::whatsapp.unmapped_event');
            if (! isset($byEvent[$key])) {
                $byEvent[$key] = [
                    'event_key' => $key,
                    'sent' => 0,
                    'delivered' => 0,
                    'read' => 0,
                    'failed' => 0,
                    'total' => 0,
                ];
            }
            $status = (string) $row->status;
            if (isset($byEvent[$key][$status])) {
                $byEvent[$key][$status] = (int) $row->total;
            }
            $byEvent[$key]['total'] += (int) $row->total;
        }

        $recentFailures = WaMessage::query()
            ->where('direction', 'out')
            ->where('status', 'failed')
            ->where('created_at', '>=', $since)
            ->with(['contact'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function (WaMessage $m) {
                return [
                    'id' => $m->id,
                    'at' => $m->created_at,
                    'phone' => optional($m->contact)->phone,
                    'template_key' => $m->template_key,
                    'type' => $m->type,
                    'preview' => $m->displayBody(),
                    'error' => MetaApiErrorFormatter::summarize($m->error_json),
                ];
            });

        return view('whatsapp::admin.whatsapp.delivery', [
            'days' => $days,
            'since' => $since,
            'byEvent' => array_values($byEvent),
            'recentFailures' => $recentFailures,
        ]);
    }
}
