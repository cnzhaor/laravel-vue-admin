<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Dictionary;
use App\Models\DictionaryItem;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\SystemParameter;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && env('ADMIN_PASSWORD') === 'Admin@123456') {
            throw new \RuntimeException('生产环境禁止使用默认管理员密码');
        }

        $department = Department::query()->updateOrCreate(['code' => 'headquarters'], ['name' => '总公司', 'sort' => 1, 'enabled' => true]);
        $position = Position::query()->updateOrCreate(['code' => 'administrator'], ['name' => '系统管理员', 'sort' => 1, 'enabled' => true]);

        $resources = [
            'user' => '用户管理', 'role' => '角色管理', 'permissions' => '权限管理',
            'menus' => '菜单管理', 'departments' => '部门管理', 'positions' => '岗位管理',
            'dictionaries' => '字典管理', 'dictionary-items' => '字典项管理',
            'parameters' => '参数管理', 'operation-logs' => '操作日志', 'login-logs' => '登录日志',
        ];
        $permissions = collect($resources)->map(fn ($name, $resource) => Permission::query()->updateOrCreate(
            ['code' => "system:{$resource}:manage"],
            ['name' => $name, 'type' => 'api']
        ));

        $role = Role::query()->updateOrCreate(
            ['code' => 'super_admin'],
            ['name' => '超级管理员', 'enabled' => true, 'is_system' => true, 'remark' => '系统内置角色']
        );
        $role->permissions()->sync($permissions->pluck('id'));

        $user = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => env('ADMIN_NAME', '超级管理员'),
                'email' => env('ADMIN_EMAIL', 'admin@example.com'),
                'password' => env('ADMIN_PASSWORD', 'Admin@123456'),
                'department_id' => $department->id,
                'position_id' => $position->id,
                'enabled' => true,
                'is_super_admin' => true,
            ]
        );
        $user->roles()->sync([$role->id]);

        $system = Menu::query()->updateOrCreate(['path' => '/system'], [
            'name' => '系统管理', 'type' => 'directory', 'icon' => 'Setting', 'sort' => 10, 'enabled' => true, 'visible' => true,
        ]);
        $monitor = Menu::query()->updateOrCreate(['path' => '/monitor'], [
            'name' => '系统监控', 'type' => 'directory', 'icon' => 'Monitor', 'sort' => 20, 'enabled' => true, 'visible' => true,
        ]);

        $menus = [
            [$system->id, '用户管理', '/system/users', 'users', 'system:user:manage'],
            [$system->id, '角色管理', '/system/roles', 'roles', 'system:role:manage'],
            [$system->id, '权限管理', '/system/permissions', 'permissions', 'system:permissions:manage'],
            [$system->id, '菜单管理', '/system/menus', 'menus', 'system:menus:manage'],
            [$system->id, '部门管理', '/system/departments', 'departments', 'system:departments:manage'],
            [$system->id, '岗位管理', '/system/positions', 'positions', 'system:positions:manage'],
            [$system->id, '字典管理', '/system/dictionaries', 'dictionaries', 'system:dictionaries:manage'],
            [$system->id, '字典项管理', '/system/dictionary-items', 'dictionary-items', 'system:dictionary-items:manage'],
            [$system->id, '参数管理', '/system/parameters', 'parameters', 'system:parameters:manage'],
            [$monitor->id, '操作日志', '/monitor/operation-logs', 'operation-logs', 'system:operation-logs:manage'],
            [$monitor->id, '登录日志', '/monitor/login-logs', 'login-logs', 'system:login-logs:manage'],
        ];
        foreach ($menus as $index => [$parentId, $name, $path, $component, $permission]) {
            Menu::query()->updateOrCreate(['path' => $path], [
                'parent_id' => $parentId, 'name' => $name, 'component' => $component,
                'permission_code' => $permission, 'type' => 'menu', 'sort' => $index + 1,
                'enabled' => true, 'visible' => true,
            ]);
        }

        $status = Dictionary::query()->updateOrCreate(['code' => 'common_status'], ['name' => '通用状态', 'enabled' => true]);
        DictionaryItem::query()->updateOrCreate(['dictionary_id' => $status->id, 'value' => '1'], ['label' => '启用', 'sort' => 1, 'enabled' => true, 'tag_type' => 'success']);
        DictionaryItem::query()->updateOrCreate(['dictionary_id' => $status->id, 'value' => '0'], ['label' => '停用', 'sort' => 2, 'enabled' => true, 'tag_type' => 'danger']);
        SystemParameter::query()->updateOrCreate(['key' => 'system.title'], ['name' => '系统标题', 'value' => '通用管理后台', 'is_public' => true]);
    }
}
