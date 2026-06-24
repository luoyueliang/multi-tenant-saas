<?php

use Illuminate\Support\Facades\Broadcast;
use MultiTenantSaas\Models\User;

/**
 * 用户私有频道 - 用于实时通知推送
 * 客户端订阅: Echo.private(`App.Models.User.${userId}`)
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * 租户频道 - 用于租户级别的事件推送
 * 客户端订阅: Echo.private(`tenant.{tenantId}`)
 */
Broadcast::channel('tenant.{tenantId}', function (User $user, $tenantId) {
    return $user->tenants()
        ->where('tenants.tenant_id', $tenantId)
        ->wherePivot('is_active', true)
        ->exists();
});
