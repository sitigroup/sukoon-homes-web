<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Models\WaMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsappMaintenanceMediaService
{
    public function attachMessageToRequest(WaMessage $message, int $maintenanceRequestId, ?int $adminId = null): string
    {
        $path = trim((string) ($message->media_url ?? ''));
        if ($path === '' || ! Storage::disk('local')->exists($path)) {
            throw new \InvalidArgumentException(__('whatsapp::whatsapp.media_missing'));
        }

        if (! class_exists(\App\Plugins\Maintenance\Models\MaintenanceRequest::class)) {
            throw new \RuntimeException(__('whatsapp::whatsapp.maintenance_plugin_missing'));
        }

        $request = \App\Plugins\Maintenance\Models\MaintenanceRequest::query()->findOrFail($maintenanceRequestId);
        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
        $dest = 'maintenance/'.$request->id.'/whatsapp-'.$message->id.'-'.uniqid('', true).'.'.$ext;

        Storage::disk('local')->put($dest, Storage::disk('local')->get($path));

        if (class_exists(\App\Plugins\Maintenance\Models\MaintenancePhoto::class)) {
            \App\Plugins\Maintenance\Models\MaintenancePhoto::query()->create([
                'request_id' => $request->id,
                'photo_path' => $dest,
                'type' => in_array($message->type, ['document'], true) ? 'document' : 'before',
                'caption' => trim((string) ($message->body ?? '')) ?: 'WhatsApp inbound',
                'uploaded_by' => $adminId,
                'uploaded_at' => now(),
            ]);
        }

        Log::info('whatsapp.media_attached_to_mr', [
            'message_id' => $message->id,
            'request_id' => $request->id,
            'path' => $dest,
        ]);

        return $dest;
    }
}
