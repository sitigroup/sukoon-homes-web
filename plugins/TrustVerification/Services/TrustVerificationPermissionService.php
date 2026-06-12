<?php

namespace App\Plugins\TrustVerification\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class TrustVerificationPermissionService
{
    public const MODULE = 'trust_verification';

    public const DENIED_MESSAGE = 'You do not have permission to access this section.';

    /** Maps to Staff → Permissions UI under module {@see MODULE}. */
    public const ROLE_READ = 'read';

    public const ROLE_UPDATE = 'update';

    public const ROLE_DOCUMENTS = 'documents';

    public const ROLE_REPORTS = 'reports';

    public const ROLE_PAYMENTS = 'payments';

    public const ROLE_SETTINGS = 'settings';

    public const ROLE_AUDIT = 'audit';

    /** @return list<string> */
    public static function roles(): array
    {
        return [
            self::ROLE_READ,
            self::ROLE_UPDATE,
            self::ROLE_DOCUMENTS,
            self::ROLE_REPORTS,
            self::ROLE_PAYMENTS,
            self::ROLE_SETTINGS,
            self::ROLE_AUDIT,
        ];
    }

    /**
     * Merge trust_verification into config('rolepermission') for the staff permission UI.
     * Idempotent — safe to call from migration, seeder, and service provider boot.
     */
    public static function registerModule(): bool
    {
        $config = config('rolepermission', []);

        if (isset($config[self::MODULE])) {
            return false;
        }

        config([
            'rolepermission' => array_merge($config, [
                self::MODULE => self::roles(),
            ]),
        ]);

        return true;
    }

    public static function isSuperAdmin(): bool
    {
        $user = Auth::user();

        return $user && (int) $user->type === 0;
    }

    public static function moduleConfiguredForCurrentUser(): bool
    {
        if (self::isSuperAdmin()) {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $permissions = json_decode($user->permissions ?? '', true);

        return is_array($permissions)
            && array_key_exists(self::MODULE, $permissions)
            && is_array($permissions[self::MODULE]);
    }

    public static function can(string $ability): bool
    {
        if (! Auth::check()) {
            return false;
        }

        if (self::isSuperAdmin()) {
            return true;
        }

        if (! in_array($ability, self::roles(), true)) {
            return false;
        }

        if (self::moduleConfiguredForCurrentUser()) {
            return (bool) has_permissions($ability, self::MODULE);
        }

        return self::legacyCan($ability);
    }

    private static function legacyCan(string $ability): bool
    {
        return match ($ability) {
            self::ROLE_READ, self::ROLE_AUDIT, self::ROLE_DOCUMENTS => has_permissions('read', 'customer'),
            self::ROLE_UPDATE, self::ROLE_REPORTS, self::ROLE_PAYMENTS, self::ROLE_SETTINGS => has_permissions('update', 'customer'),
            default => false,
        };
    }

    /** @return array<string, bool> */
    public static function capabilities(): array
    {
        $capabilities = [];
        foreach (self::roles() as $role) {
            $capabilities[$role] = self::can($role);
        }

        return $capabilities;
    }

    public static function deniedMessage(): string
    {
        return self::DENIED_MESSAGE;
    }

    public static function denyUnlessCan(string $ability): ?RedirectResponse
    {
        if (self::can($ability)) {
            return null;
        }

        return redirect()->back()->with('error', self::deniedMessage());
    }
}
