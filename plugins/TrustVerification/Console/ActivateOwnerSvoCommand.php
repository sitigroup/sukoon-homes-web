<?php

namespace App\Plugins\TrustVerification\Console;

use App\Plugins\TrustVerification\Models\TvCheckItem;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvPackage;
use App\Plugins\TrustVerification\Models\TvReport;
use App\Plugins\TrustVerification\Models\TvSubject;
use App\Plugins\TrustVerification\Models\TvVerificationBadge;
use App\Plugins\TrustVerification\Services\TrustVerificationIssuedBadgeService;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Ops: activate SVO for a property lister who has completed tenant verification but no owner order yet.
 */
class ActivateOwnerSvoCommand extends Command
{
    protected $signature = 'trust-verification:activate-owner-svo
                            {customer_id : Customer ID (property lister)}
                            {--dry-run : Show plan only}
                            {--from-order= : Template completed order ID to clone checks from}';

    protected $description = 'Issue verified SVO for a customer (creates completed owner order if missing)';

    public function handle(): int
    {
        $customerId = (int) $this->argument('customer_id');
        $dryRun = (bool) $this->option('dry-run');

        if (TrustVerificationIssuedBadgeService::customerHasActiveOwnerBadge($customerId)) {
            $existing = TvVerificationBadge::query()
                ->where('customer_id', $customerId)
                ->where('badge_type', TvVerificationBadge::TYPE_OWNER)
                ->where('status', TvVerificationBadge::STATUS_VERIFIED)
                ->orderByDesc('id')
                ->first();
            $this->info("Customer {$customerId} already has active SVO: {$existing?->badge_number}");

            return self::SUCCESS;
        }

        $ownerOrder = TvOrder::query()
            ->where('customer_id', $customerId)
            ->where('order_type', TvVerificationBadge::TYPE_OWNER)
            ->where('status', 'completed')
            ->whereIn('payment_status', ['paid', 'waived'])
            ->whereNotIn('id', TvVerificationBadge::query()->select('order_id'))
            ->orderByDesc('id')
            ->first();

        if (! $ownerOrder) {
            $templateId = $this->option('from-order');
            $template = $templateId
                ? TvOrder::find($templateId)
                : TvOrder::query()
                    ->where('customer_id', $customerId)
                    ->where('status', 'completed')
                    ->whereIn('payment_status', ['paid', 'waived'])
                    ->orderByDesc('id')
                    ->first();

            if (! $template) {
                $this->error("No completed order to use as template for customer {$customerId}.");

                return self::FAILURE;
            }

            if ($dryRun) {
                $this->info("Would create completed owner order from template #{$template->id} for customer {$customerId}");

                return self::SUCCESS;
            }

            $ownerOrder = $this->createCompletedOwnerOrderFromTemplate($template);
            $this->info("Created owner order #{$ownerOrder->id} ({$ownerOrder->order_number})");
        }

        if ($dryRun) {
            $this->info("Would issue verified SVO for order #{$ownerOrder->id}, customer {$customerId}");

            return self::SUCCESS;
        }

        $badge = TrustVerificationIssuedBadgeService::issueOwnerBadgeForOrder($ownerOrder, null, true);
        $this->line('Owner badge issued:');
        $this->line('customer_id: '.$badge->customer_id);
        $this->line('order_id: '.$badge->order_id);
        $this->line('badge_number: '.$badge->badge_number);

        return self::SUCCESS;
    }

    protected function createCompletedOwnerOrderFromTemplate(TvOrder $template): TvOrder
    {
        return DB::transaction(function () use ($template) {
            $template->loadMissing(['subject', 'checkItems', 'package']);

            $package = TvPackage::query()
                ->where('type', TvVerificationBadge::TYPE_OWNER)
                ->where('is_active', true)
                ->when($template->city_slug, fn ($q) => $q->where('city_slug', $template->city_slug))
                ->orderBy('sort_order')
                ->first();

            if (! $package) {
                throw new \RuntimeException('No active owner verification package found.');
            }

            $order = TvOrder::create([
                'order_number' => TrustVerificationService::generateOrderNumber($template->city_slug),
                'customer_id' => $template->customer_id,
                'package_id' => $package->id,
                'order_type' => TvVerificationBadge::TYPE_OWNER,
                'city_slug' => $template->city_slug,
                'requester_name' => $template->requester_name,
                'requester_email' => $template->requester_email,
                'requester_phone' => $template->requester_phone,
                'status' => 'completed',
                'payment_status' => 'paid',
                'amount' => $package->price,
                'completed_at' => now(),
                'admin_notes' => 'Owner SVO activation (TASK-16E) from order #'.$template->id,
            ]);

            $subject = $template->subject;
            if ($subject) {
                TvSubject::create([
                    'order_id' => $order->id,
                    'subject_type' => TvVerificationBadge::TYPE_OWNER,
                    'full_name' => $subject->full_name,
                    'phone' => $subject->phone,
                    'email' => $subject->email,
                    'current_address' => $subject->current_address,
                    'permanent_address' => $subject->permanent_address,
                    'property_address' => $subject->property_address,
                    'id_type' => $subject->id_type,
                    'id_number_hint' => $subject->id_number_hint,
                    'employment_company' => $subject->employment_company,
                    'employment_role' => $subject->employment_role,
                    'consent_given' => true,
                    'consent_at' => now(),
                ]);
            }

            foreach (TrustVerificationService::defaultChecksForType(TvVerificationBadge::TYPE_OWNER) as $check) {
                $included = TrustVerificationService::packageIncludesCheck($package, $check['check_key']);
                $templateItem = $template->checkItems->firstWhere('check_key', $check['check_key']);
                $status = $included
                    ? ($templateItem && $templateItem->status === 'pass' ? 'pass' : 'pass')
                    : 'na';

                TvCheckItem::create([
                    'order_id' => $order->id,
                    'check_key' => $check['check_key'],
                    'label' => $check['label'],
                    'status' => $status,
                    'completed_at' => $status === 'pass' ? now() : null,
                ]);
            }

            TvReport::create(['order_id' => $order->id]);

            return $order->fresh(['package', 'checkItems', 'subject']);
        });
    }
}
