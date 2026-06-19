# Laravel + Vue 通用管理后台

基于 Laravel 13、Vue 3、TypeScript、Element Plus、MySQL、Redis 和 Nginx 的前后端分离管理后台。项目运行时全部由 Docker 提供，本机无需安装 PHP、Composer、Node、MySQL 或 Redis。

## 快速开始

前置条件：OrbStack 或 Docker Desktop 已启动。

```bash
cp .env.example .env
cp backend/.env.example backend/.env
./deploy/scripts/init.sh
```

访问 `http://localhost:8080`，开发环境默认账号：

- 用户名：`admin`
- 密码：`Admin@123456`

首次部署到公开环境前，必须在 `backend/.env` 修改 `ADMIN_PASSWORD`，随后重新执行初始化。建议同时修改数据库密码，并关闭 `APP_DEBUG`。

## 常用命令

```bash
docker compose up -d
docker compose down
docker compose logs -f app
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan test
docker compose run --rm frontend npm run build
```

## 模块

用户、角色、权限、菜单、部门、岗位、字典、字典项、系统参数、操作日志、登录日志。权限编码采用 `模块:资源:动作`，后端中间件负责最终鉴权，前端菜单与按钮只负责交互层隐藏。

## 架构说明

- 浏览器统一访问 Nginx，同域转发 Vue、`/api` 和 `/sanctum`。
- Sanctum 使用 Cookie 会话和 CSRF 防护。
- Redis 承担 Session、缓存与队列。
- `queue` 与 `scheduler` 是独立常驻容器。
- `/up` 为 Laravel 健康检查入口。

