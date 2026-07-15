# Laravel + Vue 通用管理后台

基于 Laravel 13、Vue 3、TypeScript 和 Element Plus 的前后端分离管理后台。项目通过 Docker Compose 运行，本机无需单独安装 PHP、Composer、Node.js、MySQL 或 Redis。

## 功能模块

- 用户、角色和权限管理
- 菜单、部门和岗位管理
- 字典、字典项和系统参数管理
- 登录日志和操作日志
- 基于角色的菜单及接口权限控制
- Cookie Session + Laravel Sanctum 登录认证
- 队列与定时任务容器
- Adminer 数据库可视化管理

## 技术栈

| 分类 | 技术 |
| --- | --- |
| 后端 | PHP 8.3、Laravel 13、Laravel Sanctum |
| 前端 | Vue 3、TypeScript、Vite、Pinia、Vue Router |
| UI | Element Plus |
| 数据 | MySQL 8.4、Redis 7.4 |
| 服务 | Nginx、PHP-FPM、Docker Compose |

## 快速开始

### 环境要求

- macOS、Linux 或 Windows
- Docker Desktop 或 OrbStack
- Git

### 初始化项目

```bash
git clone https://github.com/cnzhaor/laravel-vue-admin.git
cd laravel-vue-admin

cp .env.example .env
cp backend/.env.example backend/.env

chmod +x deploy/scripts/init.sh
./deploy/scripts/init.sh
```

初始化脚本会自动完成：

1. 构建 PHP 应用镜像；
2. 安装 Composer 依赖；
3. 生成 Laravel `APP_KEY`；
4. 启动 MySQL 和 Redis；
5. 重建数据库并写入初始数据；
6. 启动前端、后端、队列、定时任务和 Nginx。

> `init.sh` 使用 `migrate:fresh --seed`，会清空现有数据库。已有数据的环境请勿重复执行。

### 访问地址

| 服务 | 地址 |
| --- | --- |
| 管理后台 | <http://localhost:8080> |
| 健康检查 | <http://localhost:8080/up> |
| Adminer | <http://localhost:8081> |

后台默认账号：

```text
用户名：admin
密码：Admin@123456
```

首次部署到公开环境前，请在 `backend/.env` 修改 `ADMIN_PASSWORD`，同时修改数据库密码并设置：

```dotenv
APP_ENV=production
APP_DEBUG=false
```

## Adminer 数据库管理

Adminer 仅监听本机 `127.0.0.1`，默认不会暴露给局域网或公网。

登录信息：

| 字段 | 内容 |
| --- | --- |
| 系统 | MySQL |
| 服务器 | `mysql` |
| 用户名 | `admin` |
| 密码 | `admin_secret` |
| 数据库 | `admin` |

如果 Adminer 没有启动：

```bash
docker compose up -d adminer
```

首次启动 Adminer 需要从官方网站下载单文件程序，因此需要可用的网络连接。

## 常用命令

```bash
# 查看容器状态
docker compose ps

# 启动或停止全部服务
docker compose up -d
docker compose down

# 查看日志
docker compose logs -f app
docker compose logs -f frontend
docker compose logs -f queue

# Laravel 数据库操作
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed

# 清理 Laravel 缓存
docker compose exec app php artisan optimize:clear

# 运行后端测试
docker compose exec app php artisan test

# 检查前端生产构建
docker compose exec frontend npm run build
```

## 配置说明

根目录 `.env` 控制 Docker Compose：

```dotenv
APP_PORT=8080
DB_DATABASE=admin
DB_USERNAME=admin
DB_PASSWORD=admin_secret
DB_ROOT_PASSWORD=root_secret
```

可通过 `ADMINER_PORT` 修改 Adminer 端口：

```dotenv
ADMINER_PORT=8081
```

`backend/.env` 控制 Laravel。修改数据库名、用户名或密码时，需要确保根目录 `.env` 与 `backend/.env` 保持一致。

## 项目结构

```text
.
├── backend/                 # Laravel API
│   ├── app/
│   ├── database/
│   ├── routes/
│   └── tests/
├── frontend/                # Vue 3 管理后台
│   └── src/
│       ├── api/
│       ├── components/
│       ├── layouts/
│       ├── router/
│       ├── stores/
│       └── views/
├── deploy/
│   ├── nginx/               # Nginx 配置
│   ├── php/                 # PHP-FPM 镜像
│   └── scripts/             # 初始化脚本
└── compose.yaml
```

## 架构说明

- 浏览器统一访问 Nginx；
- Nginx 将页面请求转发至 Vite，将 `/api`、`/sanctum` 和 `/up` 转发至 Laravel；
- Sanctum 使用 Cookie Session 和 CSRF 防护；
- Redis 承担 Session、缓存和队列；
- `queue` 与 `scheduler` 作为独立常驻容器运行；
- 数据库存储 UTC 时间，前端按照浏览器本地时区显示。

## 抖音返利 Demo

登录后台后访问「抖音返利 Demo」，可演示抖口令/短链解析转链、渠道标识透传和抖客结算账单查询。默认启用 Mock 模式，不需要开放平台账号：

```dotenv
DOUYIN_REBATE_MOCK=true
```

切换真实接口前，需要在抖店开放平台创建应用，取得「联盟抖客」授权，并申请「抖客分销转链」和账单查询相关权限。然后在 `backend/.env` 配置：

```dotenv
DOUYIN_REBATE_MOCK=false
DOUYIN_APP_KEY=你的AppKey
DOUYIN_APP_SECRET=你的AppSecret
DOUYIN_ACCESS_TOKEN=抖客授权AccessToken
DOUYIN_PID=dy_xxx_xxx_xxx
```

修改后执行 `docker compose exec app php artisan optimize:clear`。AppSecret 和 AccessToken 只保存在后端，前端不会接触密钥。真实模式使用 `buyin.doukeCommandParseAndShare` 完成解析转链，使用 `buyin.douKeSettleBillList` 查询单日结算账单。

## 常见问题

### 页面打开后白屏

Vite 依赖缓存可能已经失效，重启前端容器：

```bash
docker compose restart frontend
```

然后强制刷新浏览器页面。

### 8080 端口已被占用

修改根目录 `.env`：

```dotenv
APP_PORT=8088
```

重新启动：

```bash
docker compose up -d
```

### 查看数据库连接状态

```bash
docker compose exec mysql mysqladmin ping -h localhost
```

## 安全提示

- 不要将真实密码、密钥或生产环境 `.env` 提交到 Git；
- 生产环境必须关闭 `APP_DEBUG`；
- 修改默认管理员密码和数据库密码；
- Adminer 当前仅供本机开发使用，不建议直接暴露到公网；
- 定期备份 MySQL 数据卷。
