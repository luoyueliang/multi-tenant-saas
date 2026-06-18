# Multi-Tenant SaaS Framework

Laravel 多租户 SaaS 基础框架 — 开箱即用的项目骨架

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## 特性

- ✅ **全局唯一随机ID** — 16位随机数字，JS安全，无法推测业务量
- ✅ **自动租户隔离** — 全局作用域，开发者无需思考租户问题
- ✅ **三级配置缓存** — 内存 → Redis → 数据库，零延迟读取
- ✅ **权限控制** — 域名类型 × 用户角色，灵活的权限体系
- ✅ **配额管理** — 套餐配额检查，资源使用限制
- ✅ **审计日志** — 自动记录关键操作，可追溯
- ✅ **前端无关** — 只提供API，不绑定任何前端框架

## 快速开始

```bash
composer create-project luoyueliang/multi-tenant-saas my-saas-app
cd my-saas-app
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## 更新框架

```bash
composer update luoyueliang/multi-tenant-saas
```

## 项目结构

```
my-saas-app/
├── app/                        # 业务代码
│   ├── Http/Controllers/
│   ├── Http/Middleware/
│   ├── Models/
│   └── Providers/
├── src/                        # 框架核心（MultiTenantSaas 命名空间）
│   ├── Context/
│   ├── Contracts/
│   ├── Enums/
│   ├── Events/
│   ├── Exceptions/
│   ├── Helpers/
│   ├── Middleware/
│   ├── Models/
│   ├── Scopes/
│   ├── Services/
│   └── TenancyServiceProvider.php
├── config/
│   └── tenancy.php             # 框架配置
├── database/
│   └── migrations/             # 租户相关表迁移
├── bootstrap/app.php           # 中间件已预配置
├── routes/
└── composer.json
```

## 使用示例

### 继承基类模型

```php
use MultiTenantSaas\Models\Tenant;

class Customer extends Tenant
{
    protected $primaryKey = 'customer_id';
    
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
    ];
}
```

### 辅助函数

```php
// 获取当前租户ID
$tenantId = tenant_id();

// 获取租户配置
$corpId = tenant_config('wecom', 'corp_id');

// 检查配额
check_quota('customers');

// 生成唯一ID
$id = generate_id();
```

## 核心组件

| 组件 | 说明 |
|-----|------|
| `IdGenerator` | 全局唯一随机ID生成 |
| `TenantContext` | 租户上下文管理 |
| `TenantScope` | 全局作用域隔离 |
| `IdentifyTenant` | 租户识别中间件 |
| `CheckPermission` | 权限控制中间件 |
| `TenantSettingService` | 配置管理服务 |
| `QuotaService` | 资源配额服务 |
| `AuditService` | 操作审计服务 |

## 许可证

MIT License
