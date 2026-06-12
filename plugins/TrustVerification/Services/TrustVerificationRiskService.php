<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvOrder;

class TrustVerificationRiskService
{
    /**
     * Suggest green / amber / red from check item statuses (Phase D rules).
     *
     * @return array{level: ?string, reason: string}
     */
    public static function suggestRiskLevel(TvOrder $order): array
    {
        $order->loadMissing('checkItems');

        $included = $order->checkItems->filter(fn ($item) => $item->status !== 'na');

        if ($included->isEmpty()) {
            return [
                'level' => null,
                'reason' => 'No applicable checks on this package yet.',
            ];
        }

        if ($included->contains(fn ($item) => $item->status === 'fail')) {
            return [
                'level' => 'red',
                'reason' => 'At least one check failed.',
            ];
        }

        if ($included->contains(fn ($item) => $item->status === 'pending')) {
            return [
                'level' => null,
                'reason' => 'Finish all checks before a risk suggestion is available.',
            ];
        }

        $passCount = $included->where('status', 'pass')->count();

        if ($passCount === $included->count()) {
            return [
                'level' => 'green',
                'reason' => 'All included checks passed.',
            ];
        }

        return [
            'level' => 'amber',
            'reason' => 'Mixed or inconclusive check results — review manually.',
        ];
    }
}
