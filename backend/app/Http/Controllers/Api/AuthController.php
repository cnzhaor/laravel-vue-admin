<?php

namespace App\Http\Controllers\Api;

use App\Models\LoginLog;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['username' => ['required', 'string'], 'password' => ['required', 'string']]);
        $user = User::query()->where('username', $data['username'])->first();
        $success = $user && $user->enabled && Hash::check($data['password'], $user->password);

        LoginLog::query()->create([
            'user_id' => $user?->id,
            'username' => $data['username'],
            'success' => (bool) $success,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'message' => $success ? '登录成功' : '用户名、密码错误或账号已禁用',
        ]);

        if (!$success) {
            return response()->json(['code' => 401, 'message' => '用户名、密码错误或账号已禁用', 'data' => null], 422);
        }

        auth()->login($user, true);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        return $this->success($this->profile($user), '登录成功');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($this->profile($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        auth()->guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return $this->success(null, '已退出登录');
    }

    private function profile(User $user): array
    {
        $permissions = $user->permissionCodes();
        $menus = Menu::query()->where('enabled', true)->where('visible', true)->orderBy('sort')->get()
            ->filter(fn (Menu $menu) => !$menu->permission_code || $user->hasPermission($menu->permission_code))
            ->values();

        return [
            'user' => $user->load(['roles:id,name,code', 'department:id,name', 'position:id,name']),
            'permissions' => $permissions,
            'menus' => $this->tree($menus->toArray()),
        ];
    }

    private function tree(array $items, ?int $parentId = null): array
    {
        return array_values(array_map(function ($item) use ($items) {
            $item['children'] = $this->tree($items, $item['id']);
            return $item;
        }, array_filter($items, fn ($item) => ($item['parent_id'] ?? null) === $parentId)));
    }
}

