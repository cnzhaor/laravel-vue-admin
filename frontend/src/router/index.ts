import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AdminLayout from '../layouts/AdminLayout.vue'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import CrudView from '../views/CrudView.vue'
import DouyinRebateView from '../views/DouyinRebateView.vue'

const routes = [
  { path: '/login', component: LoginView, meta: { public: true, title: '登录' } },
  {
    path: '/',
    component: AdminLayout,
    children: [
      { path: '', component: DashboardView, meta: { title: '工作台' } },
      { path: 'marketing/douyin-rebate', component: DouyinRebateView, meta: { title: '抖音返利 Demo' } },
      { path: 'system/users', component: CrudView, props: { resource: 'users' }, meta: { title: '用户管理' } },
      { path: 'system/roles', component: CrudView, props: { resource: 'roles' }, meta: { title: '角色管理' } },
      { path: 'system/permissions', component: CrudView, props: { resource: 'permissions' }, meta: { title: '权限管理' } },
      { path: 'system/menus', component: CrudView, props: { resource: 'menus' }, meta: { title: '菜单管理' } },
      { path: 'system/departments', component: CrudView, props: { resource: 'departments' }, meta: { title: '部门管理' } },
      { path: 'system/positions', component: CrudView, props: { resource: 'positions' }, meta: { title: '岗位管理' } },
      { path: 'system/dictionaries', component: CrudView, props: { resource: 'dictionaries' }, meta: { title: '字典管理' } },
      { path: 'system/dictionary-items', component: CrudView, props: { resource: 'dictionary-items' }, meta: { title: '字典项管理' } },
      { path: 'system/parameters', component: CrudView, props: { resource: 'parameters' }, meta: { title: '参数管理' } },
      { path: 'monitor/operation-logs', component: CrudView, props: { resource: 'operation-logs' }, meta: { title: '操作日志' } },
      { path: 'monitor/login-logs', component: CrudView, props: { resource: 'login-logs' }, meta: { title: '登录日志' } },
    ],
  },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach(async (to) => {
  document.title = `${String(to.meta.title ?? '后台')} - 通用管理后台`
  if (to.meta.public) return true
  const auth = useAuthStore()
  if (!auth.loaded) {
    try { await auth.load() } catch { return '/login' }
  }
  return auth.profile ? true : '/login'
})

export default router
