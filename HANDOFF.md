# 项目交接记录

更新时间：2026-07-17（Asia/Shanghai）

## 仓库信息

- GitHub：<https://github.com/cnzhaor/laravel-vue-admin>
- 当前分支：`main`
- 远程分支：`origin/main`
- 当前最新提交：`25f17a3 feat: 添加 Redis 队列演示功能，包括任务提交、状态查询和相关 API`
- 当前工作区包含开发审查规则、Redis 延迟队列修复和技术文档的未提交改动

## 当前运行状态

Laravel 13 + Vue 3 通用管理后台已经在 Docker Compose 中正常运行。

| 服务 | 状态/地址 |
| --- | --- |
| 管理后台 | <http://localhost:8080> |
| Redis 队列演示 | <http://localhost:8080/monitor/queue-demo> |
| 抖音返利 Demo | <http://localhost:8080/marketing/douyin-rebate> |
| Laravel 健康检查 | <http://localhost:8080/up> |
| Adminer | <http://localhost:8081>，仅监听 `127.0.0.1` |
| MySQL | 容器内 `mysql:3306`，健康 |
| Redis | 容器内 `redis:6379`，健康 |
| Queue / Scheduler | 常驻运行；Queue 异常退出自动重启 |

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
- Redis 队列演示：登录用户可提交真实延迟任务并查看 Worker 状态
- Adminer 数据库可视化工具，仅允许本机访问
- 日志时间按浏览器本地时区格式化显示
- 抖音电商抖客返利 Demo：口令/短链解析转链、渠道归因、推广素材展示和结算账单查询
- 抖音 API HMAC-SHA256 签名、业务参数递归排序、密钥后端隔离及 Mock/Live 双模式

## 本轮处理记录

### Redis Worker 完善

- Queue 容器增加 `restart: unless-stopped` 和 init 进程，异常退出后可自动恢复；
- Worker 使用 80 秒超时、90 秒 `retry_after`、5 秒退避和 100 秒停止宽限，避免超时任务重复执行并支持优雅停止；
- Worker 每运行 3600 秒主动回收，由 Docker 自动拉起；
- Redis 空队列使用 5 秒阻塞等待，降低无任务时的轮询开销；
- Redis 队列默认在数据库事务提交后派发；
- Scheduler 每分钟检查队列积压，达到 100 个任务时写入结构化警告日志；
- 新增队列安全默认值与积压日志测试。

### Redis 队列演示任务

- `ProcessQueueDemo` 改用 Laravel `dispatch()->delay()` 实现 1–10 秒真实延迟，删除 Job 内的 `sleep()`，等待期间不占用 Worker；
- 任务状态通过 Redis 缓存 1 小时，新增 `available_at` 预计可执行时间，并包含等待、处理、完成和失败状态；
- 新增已登录用户的任务提交与状态查询 API，包含参数校验、限流和任务归属检查；
- 「Redis 队列演示」页面已同步延迟等待语义，展示创建时间、预计可执行时间、开始时间和结果；
- 测试新增 delay 派发属性、1–10 秒边界校验和延迟等待期状态断言；
- 独立 Review 后增加 Redis Cache 锁保护的单调状态转换、延迟到期后的“等待 Worker”文案、失败信息脱敏及重复投递/终态测试；
- 新增 `docs/redis-queue.md`，记录架构、配置、Redis 数据结构、状态机、API、重试幂等、监控、部署、测试和故障排查。

### 抖音返利 API Demo

新增登录后可访问的「抖音返利 Demo」页面：

- 后端接口：
  - `GET /api/v1/douyin-rebate/status`
  - `POST /api/v1/douyin-rebate/convert`
  - `GET /api/v1/douyin-rebate/bills`
- 转链接口使用 `buyin.doukeCommandParseAndShare`；
- 结算账单使用 `buyin.douKeSettleBillList`，按单日查询；
- `external_info` 只允许数字、字母和下划线，最大 40 个字符，可关联站内用户或推广渠道；
- 转链和账单接口均增加每分钟 30 次的登录用户限流；
- AppSecret、AccessToken 和 PID 只从 Laravel 环境配置读取，不暴露给前端；
- 默认 `DOUYIN_REBATE_MOCK=true`，没有抖客资质和密钥也能完整演示。

主要文件：

```text
backend/app/Services/DouyinRebateService.php
backend/app/Http/Controllers/Api/DouyinRebateController.php
backend/tests/Unit/DouyinRebateServiceTest.php
frontend/src/views/DouyinRebateView.vue
```

切换真实接口需要在 `backend/.env` 配置：

```dotenv
DOUYIN_REBATE_MOCK=false
DOUYIN_APP_KEY=
DOUYIN_APP_SECRET=
DOUYIN_ACCESS_TOKEN=
DOUYIN_PID=
```

其中 AccessToken 必须来自「联盟抖客」主体授权，应用还需取得抖客分销转链及账单查询相关权限。修改配置后执行：

```bash
docker compose exec app php artisan optimize:clear
```

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
- Laravel 完整测试：15 个测试通过，52 个断言
- `docker compose config --quiet` 通过
- `php artisan schedule:list` 已确认 Redis 默认队列每分钟监控
- `php artisan queue:restart` 端到端验证通过，Worker 优雅退出后由 Docker 自动拉起，重启计数为 1
- 队列演示 2 条 API 路由已通过 `php artisan route:list --path=queue-demo` 检查
- 前端 TypeScript 和生产构建通过
- 浏览器已提交 10 秒延迟任务并观察到「延迟等待 → 已完成」；Redis `MONITOR` 确认 Job 先写入 `queues:default:delayed`，到期后才进入 `reserved`，Worker 在同一秒完成处理，浏览器控制台无告警或错误
- 独立 Review 发现并已修正 4 项问题：终态回退、延迟到期后文案、异常详情泄露、失败与重复投递测试缺口
- 独立复核确认上述 4 项均已关闭，当前 diff 未发现新的 P1/P2 问题；真实并发锁竞争测试可作为后续 P3 增强
- 真实 Redis Cache 已验证重复 `handle()` 和后续 `failed()` 不会覆盖 `completed` 终态，状态与结果保持不变
- 修正 Scheduler 调用 `queue:monitor` 时把位置参数误写为 `queues=redis:default` 的问题，避免每分钟监控任务失败
- 抖音服务单元测试：2 个测试通过，4 个断言，覆盖递归参数排序和 Mock 渠道归因/推广素材
- 抖音返利 3 条路由已通过 `php artisan route:list --path=douyin-rebate` 检查
- PHPUnit 使用内存 SQLite，不会清空开发 MySQL

前端构建存在第三方依赖的 Rolldown annotation 和大体积 chunk 警告，但不影响构建成功。

## 当前待办

1. 按需提交当前未提交改动；
2. 真实联调前申请抖店开放平台抖客资质、授权和相关 API 权限；
3. 取得真实 AppKey、AppSecret、AccessToken、PID 后关闭 Mock 模式，使用真实口令验证转链和账单字段映射；
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
