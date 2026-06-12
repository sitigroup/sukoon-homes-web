<?php

namespace App\Plugins\TrustVerification\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\TrustVerification\Models\TvAuditLog;
use App\Plugins\TrustVerification\Models\TvContentBlock;
use App\Plugins\TrustVerification\Services\TrustVerificationAuditLogService;
use App\Plugins\TrustVerification\Services\TrustVerificationContentService;
use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use App\Plugins\TrustVerification\Support\ChecksTrustVerificationPermissions;
use App\Plugins\TrustVerification\Support\TrustVerificationContentPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TrustVerificationContentAdminController extends Controller
{
    use ChecksTrustVerificationPermissions;

    /** @var list<string> */
    private const TABS = ['hub', 'wizard', 'faq', 'legal', 'email', 'report', 'testimonials'];

    /** @var array<string, array{label: string, icon: string}> */
    private const TAB_META = [
        'hub' => ['label' => 'Hub', 'icon' => 'bi-house-door'],
        'wizard' => ['label' => 'Wizard', 'icon' => 'bi-magic'],
        'faq' => ['label' => 'FAQ', 'icon' => 'bi-question-circle'],
        'legal' => ['label' => 'Legal', 'icon' => 'bi-journal-text'],
        'email' => ['label' => 'Email', 'icon' => 'bi-envelope'],
        'report' => ['label' => 'Reports', 'icon' => 'bi-file-earmark-bar-graph'],
        'testimonials' => ['label' => 'Testimonials', 'icon' => 'bi-chat-quote'],
    ];

    /** @return list<string> */
    private static function contentAuditActions(): array
    {
        return [
            TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_UPDATED,
            TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_PUBLISHED,
            TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_UNPUBLISHED,
            TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_RESET,
        ];
    }

    public function index(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $tab = (string) $request->query('tab', 'hub');
        if (! in_array($tab, self::TABS, true)) {
            $tab = 'hub';
        }

        $query = TvContentBlock::query()->where('group_key', $tab);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('content_key', 'like', '%'.$search.'%')
                    ->orWhere('content', 'like', '%'.$search.'%');
            });
        }

        $status = (string) $request->query('status', 'all');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'draft') {
            $query->where('is_active', false);
        }

        if ($request->boolean('recent')) {
            $query->where('updated_at', '>=', now()->subDays(7));
        }

        $blocks = $query->orderBy('sort_order')->orderBy('id')->get();

        $lastUpdated = TvContentBlock::query()->max('updated_at');
        $stats = [
            'total' => TvContentBlock::query()->count(),
            'published' => TvContentBlock::query()->where('is_active', true)->count(),
            'draft' => TvContentBlock::query()->where('is_active', false)->count(),
            'last_updated' => $lastUpdated ? Carbon::parse($lastUpdated) : null,
        ];

        $contentActions = self::contentAuditActions();
        $recentEdits = TvAuditLog::query()
            ->whereIn('action', $contentActions)
            ->latest('id')
            ->limit(8)
            ->get();

        $mostEdited = TvAuditLog::query()
            ->whereIn('action', $contentActions)
            ->latest('id')
            ->limit(200)
            ->get()
            ->groupBy(fn (TvAuditLog $log) => (string) ($log->metadata['content_key'] ?? 'unknown'))
            ->map->count()
            ->sortDesc()
            ->take(5);

        $blocksWithPreview = $blocks->map(fn (TvContentBlock $block) => [
            'block' => $block,
            'headline' => TrustVerificationContentPreview::headline($block),
            'snippet' => TrustVerificationContentPreview::snippet($block),
        ]);

        return view('trust-verification::admin.content.index', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'tabMeta' => self::TAB_META,
            'blocks' => $blocks,
            'blocksWithPreview' => $blocksWithPreview,
            'stats' => $stats,
            'filters' => [
                'q' => $search ?? '',
                'status' => $status,
                'recent' => $request->boolean('recent'),
            ],
            'recentEdits' => $recentEdits,
            'mostEdited' => $mostEdited,
            'sortable' => in_array($tab, ['faq', 'testimonials'], true),
        ]);
    }

    public function edit(Request $request, TvContentBlock $block)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        TrustVerificationContentService::logContentAudit(
            $request,
            TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_VIEWED,
            $block
        );

        $versionHistory = TvAuditLog::query()
            ->whereIn('action', self::contentAuditActions())
            ->where('metadata->content_key', $block->content_key)
            ->latest('id')
            ->limit(15)
            ->get();

        return view('trust-verification::admin.content.edit', [
            'block' => $block,
            'preview' => TrustVerificationContentService::exportBlockValue($block),
            'previewSnippet' => TrustVerificationContentPreview::snippet($block, 500),
            'previewHeadline' => TrustVerificationContentPreview::headline($block),
            'versionHistory' => $versionHistory,
        ]);
    }

    public function update(Request $request, TvContentBlock $block)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $input = $request->all();

        if ($block->type === TvContentBlock::TYPE_FAQ) {
            $input['content_json'] = [
                'question' => $request->input('question'),
                'answer' => $request->input('answer'),
            ];
        } elseif ($block->type === TvContentBlock::TYPE_TESTIMONIAL) {
            $input['content_json'] = [
                'customer_name' => $request->input('customer_name'),
                'rating' => $request->input('rating'),
                'review' => $request->input('review'),
                'city' => $request->input('city'),
            ];
        } elseif ($block->type === TvContentBlock::TYPE_EMAIL) {
            $input['content_json'] = [
                'subject' => $request->input('email_subject'),
                'body_html' => $request->input('body_html'),
                'body_text' => $request->input('body_text'),
            ];
        } elseif ($block->type === TvContentBlock::TYPE_LEGAL) {
            $json = $request->input('content_json');
            if (is_string($json)) {
                $input['content_json'] = json_decode($json, true);
            }
        }

        TrustVerificationContentService::updateBlock($block, $input, auth()->id());

        TrustVerificationContentService::logContentAudit(
            $request,
            TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_UPDATED,
            $block->fresh(),
            'Content updated'
        );

        return redirect()
            ->route('trust-verification.content.index', ['tab' => $block->group_key])
            ->with('success', 'Content saved (version '.$block->fresh()->version.').');
    }

    public function togglePublish(Request $request, TvContentBlock $block)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $active = ! $block->is_active;
        TrustVerificationContentService::setPublished($block, $active, auth()->id());

        TrustVerificationContentService::logContentAudit(
            $request,
            $active
                ? TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_PUBLISHED
                : TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_UNPUBLISHED,
            $block->fresh()
        );

        return redirect()->back()->with('success', $active ? 'Published.' : 'Unpublished.');
    }

    public function reset(Request $request, TvContentBlock $block)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        TrustVerificationContentService::resetBlock($block, auth()->id());

        TrustVerificationContentService::logContentAudit(
            $request,
            TrustVerificationAuditLogService::ACTION_CONTENT_BLOCK_RESET,
            $block->fresh(),
            'Reset to default'
        );

        return redirect()
            ->route('trust-verification.content.edit', $block)
            ->with('success', 'Reset to default content.');
    }

    public function reorder(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $request->validate([
            'group_key' => 'required|in:faq,testimonials',
            'order' => 'required|array',
            'order.*' => 'integer|exists:tv_content_blocks,id',
        ]);

        foreach ($request->input('order', []) as $sort => $id) {
            TvContentBlock::where('id', $id)
                ->where('group_key', $request->input('group_key'))
                ->update(['sort_order' => $sort + 1]);
        }

        TrustVerificationContentService::bustPublicCache();

        return redirect()->back()->with('success', 'Order updated.');
    }

    public function seed(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $count = TrustVerificationContentService::seedDefaults((bool) $request->boolean('force'));

        return redirect()->back()->with('success', "Seeded {$count} content block(s).");
    }

    public function storeFaq(Request $request)
    {
        if ($denied = $this->tvDenyUnless(TrustVerificationPermissionService::ROLE_SETTINGS)) {
            return $denied;
        }

        $key = 'faq.'.Str::uuid();
        $block = TvContentBlock::create([
            'content_key' => $key,
            'group_key' => 'faq',
            'title' => 'New FAQ',
            'type' => TvContentBlock::TYPE_FAQ,
            'content_json' => ['question' => 'New question', 'answer' => 'Answer text'],
            'sort_order' => (int) TvContentBlock::where('group_key', 'faq')->max('sort_order') + 1,
        ]);

        TrustVerificationContentService::bustPublicCache();

        return redirect()->route('trust-verification.content.edit', $block);
    }
}
