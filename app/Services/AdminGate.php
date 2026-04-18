<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AdminGate
{
    public const ADMIN_ROLES = ['manager', 'administrator'];
    public const SYSTEM_CONTEXT_LEVEL = 10;

    public function isAdmin(int $userId): bool
    {
        return $this->isSiteAdmin($userId) || $this->hasAdminRole($userId);
    }

    public function isSiteAdmin(int $userId): bool
    {
        $value = (string) DB::table('config')->where('name', 'siteadmins')->value('value');
        $ids = array_filter(array_map('intval', explode(',', $value)));
        return in_array($userId, $ids, true);
    }

    public function hasAdminRole(int $userId): bool
    {
        return DB::table('role_assignments as ra')
            ->join('role as r', 'r.id', '=', 'ra.roleid')
            ->join('context as ctx', 'ctx.id', '=', 'ra.contextid')
            ->whereIn('r.shortname', self::ADMIN_ROLES)
            ->where('ctx.contextlevel', self::SYSTEM_CONTEXT_LEVEL)
            ->where('ra.userid', $userId)
            ->exists();
    }
}
