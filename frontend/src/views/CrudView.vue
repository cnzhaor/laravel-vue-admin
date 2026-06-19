<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { http } from '../api/http'

type Field = { key: string; label: string; type?: 'text'|'password'|'number'|'switch'|'select'|'textarea'; required?: boolean; multiple?: boolean; optionsEndpoint?: string; options?: {label:string,value:any}[] }
const props = defineProps<{ resource: string }>()

const configs: Record<string, { fields: Field[]; readonly?: boolean }> = {
  users: { fields: [
    { key:'name', label:'姓名', required:true }, { key:'username', label:'用户名', required:true },
    { key:'email', label:'邮箱', required:true }, { key:'phone', label:'手机号' },
    { key:'password', label:'密码', type:'password' },
    { key:'department_id', label:'部门', type:'select', optionsEndpoint:'departments' },
    { key:'position_id', label:'岗位', type:'select', optionsEndpoint:'positions' },
    { key:'role_ids', label:'角色', type:'select', multiple:true, optionsEndpoint:'roles' },
    { key:'enabled', label:'启用', type:'switch' },
  ]},
  roles: { fields: [{key:'name',label:'角色名称',required:true},{key:'code',label:'角色编码',required:true},{key:'permission_ids',label:'权限',type:'select',multiple:true,optionsEndpoint:'permissions'},{key:'enabled',label:'启用',type:'switch'},{key:'remark',label:'备注',type:'textarea'}] },
  permissions: { fields: [{key:'name',label:'权限名称',required:true},{key:'code',label:'权限编码',required:true},{key:'type',label:'类型',type:'select',options:[{label:'菜单',value:'menu'},{label:'接口',value:'api'},{label:'按钮',value:'button'}]},{key:'remark',label:'备注',type:'textarea'}] },
  menus: { fields: [{key:'name',label:'菜单名称',required:true},{key:'path',label:'路由'},{key:'component',label:'组件标识'},{key:'permission_code',label:'权限编码'},{key:'type',label:'类型',type:'select',options:[{label:'目录',value:'directory'},{label:'菜单',value:'menu'},{label:'按钮',value:'button'}]},{key:'sort',label:'排序',type:'number'},{key:'visible',label:'显示',type:'switch'},{key:'enabled',label:'启用',type:'switch'}] },
  departments: { fields: [{key:'name',label:'部门名称',required:true},{key:'code',label:'部门编码',required:true},{key:'sort',label:'排序',type:'number'},{key:'enabled',label:'启用',type:'switch'}] },
  positions: { fields: [{key:'name',label:'岗位名称',required:true},{key:'code',label:'岗位编码',required:true},{key:'sort',label:'排序',type:'number'},{key:'enabled',label:'启用',type:'switch'},{key:'remark',label:'备注',type:'textarea'}] },
  dictionaries: { fields: [{key:'name',label:'字典名称',required:true},{key:'code',label:'字典编码',required:true},{key:'enabled',label:'启用',type:'switch'},{key:'remark',label:'备注',type:'textarea'}] },
  'dictionary-items': { fields: [{key:'dictionary_id',label:'所属字典',type:'select',required:true,optionsEndpoint:'dictionaries'},{key:'label',label:'显示名称',required:true},{key:'value',label:'字典值',required:true},{key:'sort',label:'排序',type:'number'},{key:'enabled',label:'启用',type:'switch'},{key:'tag_type',label:'标签类型'}] },
  parameters: { fields: [{key:'name',label:'参数名称',required:true},{key:'key',label:'参数键',required:true},{key:'value',label:'参数值',type:'textarea'},{key:'is_public',label:'公开',type:'switch'},{key:'remark',label:'备注',type:'textarea'}] },
  'operation-logs': { readonly:true, fields:[{key:'method',label:'方法'},{key:'path',label:'路径'},{key:'ip',label:'IP'},{key:'status',label:'状态'},{key:'duration_ms',label:'耗时(ms)'},{key:'created_at',label:'时间'}] },
  'login-logs': { readonly:true, fields:[{key:'username',label:'用户名'},{key:'success',label:'成功'},{key:'ip',label:'IP'},{key:'message',label:'结果'},{key:'created_at',label:'时间'}] },
}

const config = computed(() => configs[props.resource])
const rows = ref<any[]>([])
const loading = ref(false)
const dialog = ref(false)
const editingId = ref<number>()
const query = reactive({ keyword:'', page:1, per_page:15 })
const total = ref(0)
const form = reactive<Record<string, any>>({})
const remoteOptions = reactive<Record<string, {label:string,value:any}[]>>({})

function resetForm(row?: any) {
  Object.keys(form).forEach(k => delete form[k])
  config.value.fields.forEach(field => {
    let value = row?.[field.key]
    if (field.key === 'role_ids') value = row?.roles?.map((item:any) => item.id)
    if (field.key === 'permission_ids') value = row?.permissions?.map((item:any) => item.id)
    form[field.key] = value ?? (field.multiple ? [] : field.type === 'switch' ? true : field.type === 'number' ? 0 : '')
  })
  editingId.value = row?.id
}

async function load() {
  loading.value = true
  try {
    const response: any = await http.get(`/${props.resource}`, { params: query })
    rows.value = response.data.data
    total.value = response.data.total
  } finally { loading.value = false }
}

function open(row?: any) { resetForm(row); dialog.value = true }

async function save() {
  for (const field of config.value.fields) {
    if (field.required && !form[field.key]) return ElMessage.warning(`请填写${field.label}`)
  }
  if (editingId.value) await http.put(`/${props.resource}/${editingId.value}`, form)
  else await http.post(`/${props.resource}`, form)
  ElMessage.success('保存成功'); dialog.value = false; load()
}

async function remove(row: any) {
  await ElMessageBox.confirm(`确定删除“${row.name || row.username || row.code || row.id}”吗？`, '删除确认', { type:'warning' })
  await http.delete(`/${props.resource}/${row.id}`)
  ElMessage.success('删除成功'); load()
}

async function loadOptions() {
  for (const field of config.value.fields.filter(item => item.optionsEndpoint)) {
    const response: any = await http.get(`/${field.optionsEndpoint}`, { params:{ per_page:100 } })
    remoteOptions[field.key] = response.data.data.map((item:any) => ({ label:item.name || item.label, value:item.id }))
  }
}

onMounted(async () => { await Promise.all([load(), loadOptions()]) })
</script>

<template>
  <el-card>
    <div class="toolbar">
      <el-input v-model="query.keyword" clearable placeholder="输入关键字搜索" style="width:260px" @keyup.enter="load" />
      <el-button type="primary" @click="load">查询</el-button>
      <el-button v-if="!config.readonly" type="success" @click="open()">新增</el-button>
    </div>
    <el-table v-loading="loading" :data="rows" stripe>
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column v-for="field in config.fields" :key="field.key" :prop="field.key" :label="field.label" min-width="120">
        <template #default="{ row }">
          <el-tag v-if="field.type === 'switch' || typeof row[field.key] === 'boolean'" :type="row[field.key] ? 'success' : 'info'">{{ row[field.key] ? '是' : '否' }}</el-tag>
          <span v-else>{{ row[field.key] ?? '-' }}</span>
        </template>
      </el-table-column>
      <el-table-column v-if="!config.readonly" label="操作" width="150" fixed="right">
        <template #default="{ row }"><el-button link type="primary" @click="open(row)">编辑</el-button><el-button link type="danger" @click="remove(row)">删除</el-button></template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="query.page" v-model:page-size="query.per_page" :total="total" layout="total, prev, pager, next" @current-change="load" />
  </el-card>

  <el-dialog v-model="dialog" :title="editingId ? '编辑' : '新增'" width="560px">
    <el-form :model="form" label-width="100px">
      <el-form-item v-for="field in config.fields" :key="field.key" :label="field.label" :required="field.required">
        <el-switch v-if="field.type === 'switch'" v-model="form[field.key]" />
        <el-input-number v-else-if="field.type === 'number'" v-model="form[field.key]" :min="0" />
        <el-select v-else-if="field.type === 'select'" v-model="form[field.key]" :multiple="field.multiple" clearable style="width:100%">
          <el-option v-for="option in (field.options || remoteOptions[field.key] || [])" :key="option.value" :label="option.label" :value="option.value" />
        </el-select>
        <el-input v-else v-model="form[field.key]" :type="field.type === 'password' ? 'password' : field.type === 'textarea' ? 'textarea' : 'text'" />
      </el-form-item>
    </el-form>
    <template #footer><el-button @click="dialog=false">取消</el-button><el-button type="primary" @click="save">保存</el-button></template>
  </el-dialog>
</template>
