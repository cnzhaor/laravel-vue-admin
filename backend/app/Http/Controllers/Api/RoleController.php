<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->success(Role::query()->with('permissions:id,name,code,type')->latest('id')->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->save($request, new Role(), 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        return $this->save($request, $role);
    }

    public function destroy(Role $role): JsonResponse
    {
        abort_if($role->is_system, 422, '不能删除系统内置角色');
        $role->delete();
        return $this->success(null, '删除成功');
    }

    private function save(Request $request, Role $role, int $status = 200): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100', Rule::unique('roles')->ignore($role->id)],
            'enabled' => ['boolean'],
            'remark' => ['nullable', 'string'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);
        abort_if($role->exists && $role->is_system && isset($data['enabled']) && !$data['enabled'], 422, '不能禁用系统内置角色');
        $ids = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);
        $role->fill($data)->save();
        $role->permissions()->sync($ids);
        return $this->success($role->load('permissions'), $status === 201 ? '创建成功' : '更新成功', $status);
    }
}

