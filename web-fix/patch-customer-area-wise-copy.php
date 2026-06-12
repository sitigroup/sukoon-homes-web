<?php
$blade = '/www/wwwroot/admin-homes/resources/views/customer/edit.blade.php';
$content = file_get_contents($blade);

$old = "{{ __('Other agents still submit suggestions for approval.') }}";
$new = "{{ __('Global rule: any agent with this box enabled can add Area/Sub Area on the agent portal. Agents without it (including this one when unchecked) only submit suggestions for admin approval.') }}";

$old2 = "{{ __('Other agents still submit suggestions for approval. Saved with the button below.') }}";
if (str_contains($content, $old2)) {
    $content = str_replace($old2, $new, $content);
}

if (str_contains($content, $old)) {
    $content = str_replace($old, $new, $content);
    file_put_contents($blade, $content);
    echo "OK: updated help text\n";
} elseif (str_contains($content, $new)) {
    echo "OK: already updated\n";
} else {
    echo "WARN: help text needle not found\n";
}
