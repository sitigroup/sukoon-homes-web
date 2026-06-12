<?php

namespace App\Plugins\TrustVerification\Console;

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvVerificationBadge;
use App\Plugins\TrustVerification\Services\TrustVerificationIssuedBadgeService;
use Illuminate\Console\Command;

class IssueMissingVerificationBadgesCommand extends Command
{
    protected $signature = 'trust-verification:issue-missing-badges
                            {--dry-run : List actions without writing}
                            {--admin-verify : When issuing, mark verified immediately (admin backfill)}
                            {--owners-only : Only process completed owner orders (SVO)}';

    protected $description = 'Issue SVO/SVT verification badges for completed orders missing a badge row';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $adminVerify = (bool) $this->option('admin-verify');
        $ownersOnly = (bool) $this->option('owners-only');

        $query = TvOrder::query()
            ->where('status', 'completed')
            ->whereIn('payment_status', ['paid', 'waived'])
            ->whereNotIn('id', TvVerificationBadge::query()->select('order_id'))
            ->orderBy('id');

        if ($ownersOnly) {
            $query->where('order_type', TvVerificationBadge::TYPE_OWNER);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->info($ownersOnly
                ? 'No completed paid owner orders without an SVO badge.'
                : 'No completed paid orders without a verification badge.');

            return self::SUCCESS;
        }

        $issued = 0;
        $skipped = 0;
        $ownerIssued = 0;

        foreach ($orders as $order) {
            $blockers = TrustVerificationIssuedBadgeService::eligibilityBlockers($order);
            $eligible = TrustVerificationIssuedBadgeService::isEligible($order);
            $isOwner = $order->order_type === TvVerificationBadge::TYPE_OWNER;

            if ($dryRun) {
                $this->line(sprintf(
                    'Order #%d %s cust=%d type=%s eligible=%s blockers=%d',
                    $order->id,
                    $order->order_number,
                    $order->customer_id,
                    $order->order_type,
                    $eligible ? 'yes' : 'no',
                    count($blockers)
                ));
                if (! $eligible && count($blockers) > 0) {
                    foreach ($blockers as $line) {
                        $this->line('  · '.$line);
                    }
                }
                if ($adminVerify || $eligible) {
                    $this->line('  → would issue'.($adminVerify ? ' (verified)' : ($eligible ? ' (verified)' : ' (pending)')));
                    if ($isOwner) {
                        $this->printOwnerWouldIssue($order);
                    }
                } else {
                    $this->line('  → skip (use --admin-verify to backfill anyway)');
                    $skipped++;
                }
                continue;
            }

            if (! $eligible && ! $adminVerify) {
                $this->warn("Skipped order #{$order->id} — not eligible. Use --admin-verify to override.");
                $skipped++;
                continue;
            }

            try {
                if ($isOwner) {
                    $badge = TrustVerificationIssuedBadgeService::issueOwnerBadgeForOrder(
                        $order,
                        null,
                        $adminVerify || $eligible
                    );
                    $this->printOwnerIssued($badge);
                    $ownerIssued++;
                } else {
                    $badge = TrustVerificationIssuedBadgeService::issueForOrder(
                        $order,
                        null,
                        false,
                        $adminVerify || $eligible
                    );
                    $this->info("Issued {$badge->badge_number} ({$badge->badge_type}, {$badge->status}) for order #{$order->id}");
                }
                $issued++;
            } catch (\InvalidArgumentException $e) {
                $this->warn("Skipped order #{$order->id}: {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->newLine();
        $this->info($dryRun
            ? 'Dry run complete.'
            : "Done. Issued: {$issued}, owner SVO: {$ownerIssued}, skipped: {$skipped}.");

        return self::SUCCESS;
    }

    protected function printOwnerWouldIssue(TvOrder $order): void
    {
        $this->line('Owner badge would issue:');
        $this->line('customer_id: '.$order->customer_id);
        $this->line('order_id: '.$order->id);
        $this->line('badge_number: (next SVO-'.date('Y').'-######)');
    }

    protected function printOwnerIssued(TvVerificationBadge $badge): void
    {
        $this->line('Owner badge issued:');
        $this->line('customer_id: '.$badge->customer_id);
        $this->line('order_id: '.$badge->order_id);
        $this->line('badge_number: '.$badge->badge_number);
    }
}
