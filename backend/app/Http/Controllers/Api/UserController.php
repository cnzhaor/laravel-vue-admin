<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with(['roles:id,name,code', 'department:id,name', 'position:id,name'])->latest('id');
        if ($keyword = $request->string('keyword')->trim()->value()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$keyword}%")->orWhere('username', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"));
        }
        return $this->success($query->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        $roleIds = $data['role_ids'] ?? [];
        unset($data['role_ids']);
        $user = User::query()->create($data);
        $user->roles()->sync($roleIds);
        return $this->success($user->load('roles'), '创建成功', 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate($this->rules($user->id));
        abort_if($user->is_super_admin && array_key_exists('enabled', $data) && !$data['enabled'], 422, '不能禁用超级管理员');
        $roleIds = $data['role_ids'] ?? null;
        unset($data['role_ids']);
        if (empty($data['password'])) unset($data['password']);
        $user->update($data);
        if ($roleIds !== null) $user->roles()->sync($roleIds);
        return $this->success($user->load('roles'), '更新成功');
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if($user->is_super_admin, 422, '不能删除超级管理员');
        $user->delete();
        return $this->success(null, '删除成功');
    }

    private function rules(?int $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$id ? 'nullable' : 'required', 'string', 'min:8'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'enabled' => ['boolean'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }
}

