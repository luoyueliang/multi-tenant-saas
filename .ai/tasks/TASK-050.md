# TASK-050: 路由注册与服务容器绑定

**目标：** 在 `routes/api.php` 的 `/api/v1` 组下注册全部 Agent/Chat/Stats/Tool 路由（含 SSE 路由，import 用 `App\Http\Controllers\Api\*`）；在 `TenancyServiceProvider::register` 校验/补齐 4 个 Agent 服务契约单例绑定。
**范围：**
- 只允许修改:
  - `routes/api.php`（仅追加 Agent 路由组）
  - `src/TenancyServiceProvider.php`（仅追加/校验 4 个绑定，确保未遗漏）
- 禁止: 改控制器；改现有路由
**依赖：** 需要 TASK-047、TASK-048、TASK-049 先完成
**预估时间：** 1.5 小时
