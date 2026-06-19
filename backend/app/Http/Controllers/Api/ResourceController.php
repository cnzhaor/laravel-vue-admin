<?php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use App\Models\Dictionary;
use App\Models\DictionaryItem;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Position;
use App\Models\SystemParameter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResourceController extends ApiController
{
    private const CONFIG = [
        'departments' => [Department::class, ['name' => 'required|string|max:100', 'code' => 'required|string|max:100', 'parent_id' => 'nullable|integer', 'sort' => 'integer|min:0', 'enabled' => 'boolean']],
        'positions' => [Position::class, ['name' => 'required|string|max:100', 'code' => 'required|string|max:100', 'sort' => 'integer|min:0', 'enabled' => 'boolean', 'remark' => 'nullable|string']],
        'permissions' => [Permission::class, ['name' => 'required|string|max:100', 'code' => 'required|string|max:150', 'type' => 'required|in:menu,api,button', 'remark' => 'nullable|string']],
        'menus' => [Menu::class, ['name' => 'required|string|max:100', 'parent_id' => 'nullable|integer', 'path' => 'nullable|string|max:150', 'component' => 'nullable|string|max:150', 'icon' => 'nullable|string|max:100', 'permission_code' => 'nullable|string|max:150', 'type' => 'required|in:directory,menu,button', 'sort' => 'integer|min:0', 'visible' => 'boolean', 'enabled' => 'boolean']],
        'dictionaries' => [Dictionary::class, ['name' => 'required|string|max:100', 'code' => 'required|string|max:100', 'enabled' => 'boolean', 'remark' => 'nullable|string']],
        'dictionary-items' => [DictionaryItem::class, ['dictionary_id' => 'required|exists:dictionaries,id', 'label' => 'required|string|max:100', 'value' => 'required|string|max:100', 'sort' => 'integer|min:0', 'enabled' => 'boolean', 'tag_type' => 'nullable|string|max:30']],
        'parameters' => [SystemParameter::class, ['name' => 'required|string|max:100', 'key' => 'required|string|max:150', 'value' => 'nullable|string', 'is_public' => 'boolean', 'remark' => 'nullable|string']],
    ];

    public function index(Request $request): JsonResponse
    {
        [$model] = $this->config($request);
        $query = $model::query()->latest('id');
        if ($keyword = $request->string('keyword')->trim()->value()) {
            $query->where(function ($q) use ($keyword) {
                foreach (['name', 'code', 'label', 'key'] as $column) {
                    if (\Schema::hasColumn($q->getModel()->getTable(), $column)) $q->orWhere($column, 'like', "%{$keyword}%");
                }
            });
        }
        if ($request->filled('dictionary_id') && $model === DictionaryItem::class) {
            $query->where('dictionary_id', $request->integer('dictionary_id'));
        }
        return $this->success($query->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        [$model, $rules] = $this->config($request);
        $rules = $this->uniqueRules($model, $rules);
        return $this->success($model::query()->create($request->validate($rules)), '创建成功', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        [$model] = $this->config($request);
        return $this->success($model::query()->findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        [$model, $rules] = $this->config($request);
        $record = $model::query()->findOrFail($id);
        $record->update($request->validate($this->uniqueRules($model, $rules, $id)));
        return $this->success($record->refresh(), '更新成功');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        [$model] = $this->config($request);
        $record = $model::query()->findOrFail($id);
        $record->delete();
        return $this->success(null, '删除成功');
    }

    private function config(Request $request): array
    {
        $resource = $request->route()->getName();
        $resource = explode('.', (string) $resource)[0];
        abort_unless(isset(self::CONFIG[$resource]), 404);
        return self::CONFIG[$resource];
    }

    private function uniqueRules(string $model, array $rules, ?int $id = null): array
    {
        /** @var Model $instance */
        $instance = new $model();
        foreach (['code', 'key'] as $field) {
            if (isset($rules[$field])) {
                $rules[$field] = [$rules[$field], Rule::unique($instance->getTable(), $field)->ignore($id)];
            }
        }
        return $rules;
    }
}

