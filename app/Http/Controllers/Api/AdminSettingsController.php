<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MultiTenantSaas\Models\SystemSetting;

class AdminSettingsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role !== 'super_admin') {
            return response()->json(['success' => false, 'message' => '无权限访问'], 403);
        }

        $settings = SystemSetting::all()->groupBy('group');
        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function update(Request $request, string $group)
    {
        if ($request->user()->role !== 'super_admin') {
            return response()->json(['success' => false, 'message' => '无权限访问'], 403);
        }

        $allowedGroups = ['system', 'mail', 'credit', 'dify'];
        if (!in_array($group, $allowedGroups)) {
            return response()->json(['success' => false, 'message' => '未知配置组'], 400);
        }

        foreach ($request->all() as $key => $value) {
            SystemSetting::updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => $value]
            );
        }

        return response()->json(['success' => true, 'message' => '系统设置已更新']);
    }
}
