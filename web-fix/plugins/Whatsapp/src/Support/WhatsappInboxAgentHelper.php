<?php

namespace App\Plugins\Whatsapp\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WhatsappInboxAgentHelper
{
    /**
     * @return Collection<int, object{id:int, name:string}>
     */
    public static function assignableAgents(): Collection
    {
        return DB::table('users')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @param  array<int, int|null>  $ids
     * @return array<int, string>
     */
    public static function namesByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_unique(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        return DB::table('users')
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->all();
    }

    public static function nameForId(?int $id, array $namesById = []): ?string
    {
        if (! $id) {
            return null;
        }

        if (isset($namesById[$id])) {
            return (string) $namesById[$id];
        }

        $name = DB::table('users')->where('id', $id)->value('name');

        return $name ? (string) $name : null;
    }
}
