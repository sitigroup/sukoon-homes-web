<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvCheckItem;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvReferenceContact;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class TrustVerificationReferenceService
{
    public const TYPE_PREVIOUS_LANDLORD = 'previous_landlord';

    public const TYPE_EMPLOYER = 'employer';

    public const TYPE_FAMILY = 'family_reference';

  /** @var list<string> */
    public const STATUSES = [
        'pending',
        'contacted',
        'verified',
        'failed',
        'no_response',
    ];

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        self::TYPE_PREVIOUS_LANDLORD => 'Previous landlord',
        self::TYPE_EMPLOYER => 'Employer',
        self::TYPE_FAMILY => 'Family reference',
    ];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'contacted' => 'Contacted',
        'verified' => 'Verified',
        'failed' => 'Failed',
        'no_response' => 'No response',
    ];

    public static function orderHasReferenceCheck(TvOrder $order): bool
    {
        $order->loadMissing('checkItems');

        return $order->checkItems->contains(
            fn (TvCheckItem $item) => $item->check_key === 'reference_check'
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, TvReferenceContact>
     */
    public static function createManyForCustomer(TvOrder $order, array $rows, ?Request $request = null): Collection
    {
        if (! self::orderHasReferenceCheck($order)) {
            throw new InvalidArgumentException('Reference check is not included in this order package.');
        }

        $validated = self::validateCustomerRows($rows);
        $created = collect();

        DB::transaction(function () use ($order, $validated, $request, &$created) {
            $sort = (int) $order->referenceContacts()->max('sort_order');

            foreach ($validated as $row) {
                $sort++;
                $contact = $order->referenceContacts()->create([
                    'reference_type' => $row['reference_type'],
                    'name' => $row['name'],
                    'relation' => $row['relation'] ?? null,
                    'mobile' => $row['mobile'],
                    'email' => $row['email'] ?? null,
                    'notes' => $row['notes'] ?? null,
                    'status' => 'pending',
                    'last_status_at' => now(),
                    'sort_order' => $sort,
                ]);
                $created->push($contact);
            }

            self::syncReferenceCheckItem($order->fresh(['checkItems', 'referenceContacts']));

            TrustVerificationAuditLogService::logCustomer(
                $request,
                $order,
                TrustVerificationAuditLogService::ACTION_CUSTOMER_SUBMITTED_REFERENCES,
                'Customer submitted '.$created->count().' reference contact(s)',
                ['reference_ids' => $created->pluck('id')->all()]
            );
        });

        return $created;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function updateFromAdmin(
        TvReferenceContact $contact,
        array $data,
        ?Request $request = null
    ): TvReferenceContact {
        $validator = Validator::make($data, [
            'status' => 'nullable|in:'.implode(',', self::STATUSES),
            'admin_notes' => 'nullable|string|max:5000',
            'call_outcome' => 'nullable|string|max:500',
            'contacted_at' => 'nullable|date',
            'verified_at' => 'nullable|date',
            'name' => 'nullable|string|max:120',
            'relation' => 'nullable|string|max:80',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:190',
            'notes' => 'nullable|string|max:2000',
            'reference_type' => 'nullable|in:'.implode(',', array_keys(self::TYPE_LABELS)),
            'external_ref_id' => 'nullable|string|max:64',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        $payload = $validator->validated();
        $previousStatus = $contact->status;

        foreach (['name', 'relation', 'mobile', 'email', 'notes', 'reference_type', 'admin_notes', 'call_outcome', 'external_ref_id'] as $field) {
            if (array_key_exists($field, $payload)) {
                $contact->{$field} = $payload[$field];
            }
        }

        if (array_key_exists('contacted_at', $payload)) {
            $contact->contacted_at = $payload['contacted_at'] ?: null;
        }

        if (array_key_exists('verified_at', $payload)) {
            $contact->verified_at = $payload['verified_at'] ?: null;
        }

        if (! empty($payload['status']) && $payload['status'] !== $contact->status) {
            $contact->status = $payload['status'];
            $contact->last_status_at = now();

            if ($payload['status'] === 'contacted' && ! $contact->contacted_at) {
                $contact->contacted_at = now();
            }

            if ($payload['status'] === 'verified' && ! $contact->verified_at) {
                $contact->verified_at = now();
            }
        }

        $contact->save();

        $order = $contact->order;
        self::syncReferenceCheckItem($order->fresh(['checkItems', 'referenceContacts']));

        TrustVerificationAuditLogService::logAdmin(
            $request,
            $order,
            TrustVerificationAuditLogService::ACTION_ADMIN_UPDATED_REFERENCE,
            'Reference contact #'.$contact->id.' updated',
            [
                'reference_id' => $contact->id,
                'from_status' => $previousStatus,
                'to_status' => $contact->status,
            ]
        );

        return $contact->fresh();
    }

  /**
   * @param  array<int, array<string, mixed>>  $rows
   * @return list<array<string, mixed>>
   */
    public static function validateCustomerRows(array $rows): array
    {
        if ($rows === []) {
            throw new InvalidArgumentException('At least one reference contact is required.');
        }

        $validator = Validator::make(
            ['references' => $rows],
            [
                'references' => 'required|array|min:1|max:10',
                'references.*.reference_type' => 'required|in:'.implode(',', array_keys(self::TYPE_LABELS)),
                'references.*.name' => 'required|string|max:120',
                'references.*.relation' => 'nullable|string|max:80',
                'references.*.mobile' => 'required|string|max:20',
                'references.*.email' => 'nullable|email|max:190',
                'references.*.notes' => 'nullable|string|max:2000',
            ]
        );

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        return $validator->validated()['references'];
    }

    public static function syncReferenceCheckItem(TvOrder $order): void
    {
        if (! self::orderHasReferenceCheck($order)) {
            return;
        }

        $order->loadMissing('referenceContacts');
        $refs = $order->referenceContacts;

        if ($refs->isEmpty()) {
            return;
        }

        $check = $order->checkItems->firstWhere('check_key', 'reference_check');
        if (! $check) {
            return;
        }

        $verified = $refs->where('status', 'verified')->count();
        $failed = $refs->whereIn('status', ['failed', 'no_response'])->count();
        $total = $refs->count();

        if ($verified === $total) {
            $check->status = 'pass';
            $check->completed_at = $check->completed_at ?? now();
            $check->notes = 'All '.$total.' reference(s) verified.';
        } elseif ($failed > 0 && $verified === 0) {
            $check->status = 'fail';
            $check->notes = $failed.' reference(s) failed or had no response.';
        } elseif ($verified > 0) {
            $check->status = 'pending';
            $check->notes = $verified.' of '.$total.' reference(s) verified.';
        } else {
            $inProgress = $refs->whereIn('status', ['contacted', 'pending'])->count();
            $check->status = 'pending';
            $check->notes = $inProgress > 0
                ? 'Reference checks in progress ('.$total.' submitted).'
                : 'References submitted ('.$total.').';
        }

        $check->save();

        TrustVerificationTrustBadgeService::onOrderEvent($order->fresh(), 'reference_verified');
    }

    /**
     * @return array<string, mixed>
     */
    public static function progressForOrder(TvOrder $order): array
    {
        $required = self::orderHasReferenceCheck($order);
        $order->loadMissing('referenceContacts');
        $refs = $order->referenceContacts;

        if (! $required) {
            return [
                'required' => false,
                'total' => 0,
                'submitted' => 0,
                'verified' => 0,
                'contacted' => 0,
                'failed' => 0,
                'pending' => 0,
                'status' => 'not_required',
                'summary' => null,
            ];
        }

        $total = $refs->count();
        $verified = $refs->where('status', 'verified')->count();
        $contacted = $refs->where('status', 'contacted')->count();
        $failed = $refs->whereIn('status', ['failed', 'no_response'])->count();
        $pending = $refs->where('status', 'pending')->count();

        if ($total === 0) {
            return [
                'required' => true,
                'total' => 0,
                'submitted' => 0,
                'verified' => 0,
                'contacted' => 0,
                'failed' => 0,
                'pending' => 0,
                'status' => 'pending_submission',
                'summary' => 'Reference details not submitted yet',
            ];
        }

        if ($verified === $total) {
            $status = 'complete';
            $summary = 'All '.$total.' reference'.($total === 1 ? '' : 's').' verified';
        } elseif ($verified > 0) {
            $status = 'in_progress';
            $summary = $verified.' of '.$total.' references verified';
        } elseif ($contacted > 0) {
            $status = 'in_progress';
            $summary = $contacted.' of '.$total.' references contacted';
        } elseif ($failed > 0) {
            $status = 'in_progress';
            $summary = $failed.' reference'.($failed === 1 ? '' : 's').' could not be verified';
        } else {
            $status = 'in_progress';
            $summary = $total.' reference'.($total === 1 ? '' : 's').' submitted — verification pending';
        }

        return [
            'required' => true,
            'total' => $total,
            'submitted' => $total,
            'verified' => $verified,
            'contacted' => $contacted,
            'failed' => $failed,
            'pending' => $pending,
            'status' => $status,
            'summary' => $summary,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function formatListForCustomer(TvOrder $order): array
    {
        if (! self::orderHasReferenceCheck($order)) {
            return [];
        }

        $order->loadMissing('referenceContacts');

        return $order->referenceContacts->map(
            fn (TvReferenceContact $contact) => self::formatContactForCustomer($contact)
        )->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatContactForCustomer(TvReferenceContact $contact): array
    {
        return [
            'id' => $contact->id,
            'reference_type' => $contact->reference_type,
            'reference_type_label' => self::TYPE_LABELS[$contact->reference_type] ?? $contact->reference_type,
            'name' => $contact->name,
            'relation' => $contact->relation,
            'mobile' => $contact->mobile,
            'email' => $contact->email,
            'notes' => $contact->notes,
            'status' => $contact->status,
            'status_label' => self::STATUS_LABELS[$contact->status] ?? $contact->status,
            'contacted_at' => optional($contact->contacted_at)?->toIso8601String(),
            'verified_at' => optional($contact->verified_at)?->toIso8601String(),
            'created_at' => optional($contact->created_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatContactForAdmin(TvReferenceContact $contact): array
    {
        return array_merge(self::formatContactForCustomer($contact), [
            'admin_notes' => $contact->admin_notes,
            'call_outcome' => $contact->call_outcome,
            'last_status_at' => optional($contact->last_status_at)?->toIso8601String(),
            'external_ref_id' => $contact->external_ref_id,
            'updated_at' => optional($contact->updated_at)?->toIso8601String(),
        ]);
    }

    public static function referencesSubmittedAt(TvOrder $order): ?\Carbon\Carbon
    {
        $order->loadMissing('referenceContacts');
        if ($order->referenceContacts->isEmpty()) {
            return null;
        }

        return $order->referenceContacts->min('created_at');
    }

    public static function referencesCompletedAt(TvOrder $order): ?\Carbon\Carbon
    {
        $progress = self::progressForOrder($order);
        if ($progress['status'] !== 'complete' || $progress['total'] === 0) {
            return null;
        }

        $order->loadMissing('referenceContacts');

        return $order->referenceContacts->max('verified_at')
            ?? $order->referenceContacts->max('updated_at');
    }
}
