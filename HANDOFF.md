# 项目交接记录

更新时间：2026-06-19（Asia/Shanghai）

## 仓库信息

- GitHub：<https://github.com/cnzhaor/laravel-vue-admin>
- 当前分支：`main`
- 远程分支：`origin/main`
- 最新已推送提交：`a409f7b feat: add database admin tool and localize log times`
- 当前未提交改动：`README.md`、`HANDOFF.md`
- 本地未跟踪缓存：`.pnpm-store/`，不要提交

## 当前运行状态

Laravel 13 + Vue 3 通用管理后台已经在 Docker Compose 中正常运行。

| 服务 | 状态/地址 |
| --- | --- |
| 管理后台 | <http://localhost:8080> |
| Laravel 健康检查 | <http://localhost:8080/up> |
| Adminer | <http://localhost:8081>，仅监听 `127.0.0.1` |
| MySQL | 容器内 `mysql:3306`，健康 |
| Redis | 容器内 `redis:6379`，健康 |
| Queue / Scheduler | 常驻运行 |

后台默认开发账号：

```text
用户名：admin
密码：Admin@123456
```

Adminer 登录信息：

```text
系统：MySQL
服务器：mysql
用户名：admin
密码：admin_secret
数据库：admin
```

## 已实现

- Sanctum Cookie、CSRF、登录限流、退出和当前用户接口
- 自建 RBAC，覆盖菜单、接口和按钮权限
- 用户、角色、权限、菜单、部门、岗位管理
- 字典、字典项和系统参数管理
- 操作日志和登录日志
- 中文登录页、后台布局、动态菜单、路由守卫和面包屑
- Docker 初始化脚本、健康检查、队列、定时任务和 GitHub Actions
- Adminer 数据库可视化工具，仅允许本机访问
- 日志时间按浏览器本地时区格式化显示

## 本轮处理记录

### 前端白屏

根因是 Vite 优化依赖缓存过期，浏览器加载 Vue 模块时返回：

```text
504 Outdated Optimize Dep
```

重启前端容器后恢复：

```bash
docker compose restart frontend
```

### 登录日志时间

数据库和 Laravel 使用 UTC 存储时间，前端此前直接显示 UTC 值，导致北京时间少 8 小时。

已在 `frontend/src/views/CrudView.vue` 中增加日期时间格式化，登录日志和操作日志现在按照浏览器本地时区显示。数据库存储方式保持不变。

### 数据库可视化

已在 `compose.yaml` 增加 `adminer` 服务：

- 复用 `laravelvue-app` PHP 镜像；
- 首次启动时从 Adminer 官网下载单文件程序；
- 使用命名卷 `adminer_data` 持久化；
- 仅绑定 `127.0.0.1:8081`，不对局域网或公网开放。

首次启动需要可访问 <https://www.adminer.org>。

## 验证结果

- 管理后台及 `/up` 可访问
- Adminer 登录成功，可查看 `admin` 数据库中的 22 张表
- 登录日志已验证显示北京时间，例如 UTC `06:56:23` 显示为 `14:56:23`
- 前端 `vue-tsc` 和生产构建通过
- Laravel 测试此前结果：6 个测试通过，8 个断言
- PHPUnit 使用内存 SQLite，不会清空开发 MySQL

前端构建存在第三方依赖的 Rolldown annotation 和大体积 chunk 警告，但不影响构建成功。

## 当前待办

1. 检查并提交已扩充的 `README.md`；
2. 推送 README/HANDOFF 提交到 `origin/main`；
3. 将 `.pnpm-store/` 加入 `.gitignore` 或删除本地缓存目录；
4. 生产部署前修改管理员、MySQL 和 Redis 相关凭据；
5. 生产环境设置 `APP_ENV=production`、`APP_DEBUG=false`；
6. Adminer 不应直接暴露到公网。

## 常用操作

```bash
# 查看服务
docker compose ps

# 启动全部服务
docker compose up -d

# 重启前端
docker compose restart frontend

# 启动 Adminer
docker compose up -d adminer

# 后端测试
docker compose exec app php artisan test

# 前端构建
docker compose exec frontend npm run build
```

完整安装、配置和排障说明见 `README.md`。
