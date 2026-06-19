# 项目交接记录

更新时间：2026-06-19（Asia/Shanghai）

## 当前状态

Laravel 13 + Vue 3 通用管理后台首期已经完成并运行在 Docker 中：

- 入口：`http://localhost:8080`
- 默认开发账号：`admin`
- 默认开发密码：`Admin@123456`
- 服务：PHP-FPM、Vue/Vite、Nginx、MySQL、Redis、Queue、Scheduler

## 已实现

- Sanctum Cookie、CSRF、登录限流、退出和当前用户接口
- 自建 RBAC，覆盖菜单、接口和按钮权限
- 用户、角色、权限、菜单、部门、岗位、字典、字典项、参数
- 操作日志和登录日志
- 中文登录页、后台布局、动态菜单、路由守卫、面包屑和通用管理页面
- Docker 初始化脚本、健康检查、后端测试、前端构建和 GitHub Actions

## 验证结果

- `/up` 和前端入口返回 200
- 真实 CSRF Cookie 登录、会话保持和受保护接口通过
- Laravel 测试：6 个通过，8 个断言
- 前端 TypeScript 检查和生产构建通过
- PHPUnit 强制使用内存 SQLite，不会清空开发 MySQL

## 常用操作

详见 `README.md`。首次公开部署前必须修改管理员密码、数据库密码并关闭 `APP_DEBUG`。
