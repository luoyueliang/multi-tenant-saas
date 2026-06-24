# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/zh-CN/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/lang/zh-CN/).

## [Unreleased]

## [0.1.0] - 2026-06-24

### Added
- 多租户 SaaS 框架基座
- 租户隔离（TenantScope + BelongsToTenant）
- 权限控制（四重访问架构）
- 配额管理
- 审计日志模型 + 服务集成
- 8 种 UI 框架支持
- Domain 模块（域名管理 + Nginx 配置生成）
- SSL 模块（证书管理）
- 32 个 API 路由
- 46 个测试用例
- API Resource 层（数据脱敏）
- 安全 HTTP 头中间件
- 编码规范文档
- CHANGELOG 和 CONTRIBUTING

### Security
- Sanctum 认证
- 租户数据隔离
- OAuth Token 加密存储
- 批量赋值防护
- 速率限制（认证端点）
- 密码策略增强（min(8)+mixedCase+numbers）
- 支付日志脱敏
- CORS 环境变量配置
