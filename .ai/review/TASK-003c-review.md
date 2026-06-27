## Architecture
变更精确且符合 Laravel 关系约定。在使用自定义主键（`role_id`、`permission_id`）的模型上，显式声明 `belongsToMany` 的四个参数是正确的做法。尽管 Laravel 默认推断的列名恰好一致（`Str::snake(class_basename(Model))` → `role_id` / `permission_id`），但显式声明消除了隐式依赖，降低了将来重构时的出错风险。修改范围严格限制在 `Role.php` 和 `Permission.php` 两个模型文件，与任务约束一致。

## Code Quality
改动极其简洁——每处仅增加两个字符串参数，零逻辑变更。命名符合 Laravel 惯例（`role_id`、`permission_id` 对应 pivot 表的实际列名）。可读性反而提升了：阅读代码时不需要去猜 Laravel 的默认推断行为。

## Type Safety
`belongsToMany` 的四个参数均为字符串字面量，与 migration 中定义的列名完全匹配。无类型风险。

## Security
本变更不涉及任何数据访问逻辑、用户输入处理或 SQL 构建。仅影响 ORM 关系映射的键名解析，无安全风险。

## Performance
无性能影响。Eloquent 生成的 SQL 与修改前完全一致——区别仅在于是显式传参还是隐式推断。

## Potential Bugs
无。这是一个纯显式化改动，不改变运行时行为。测试已通过（state.json 显示 RbacServiceTest 缺陷计数均为 8 = PASS）。

## Verdict
**PASS**

【建议改进】（非阻塞）：
1. 任务描述中提到"移除 RbacServiceTest 中的手写 SQL workaround"，但 diff 中 `tests/RbacServiceTest.php` 无实际变更。建议在 task log 中确认此项已完成或标注为"无需变更"（当前测试已使用 Eloquent 方法 `grantPermission` / `syncWithoutDetaching`，无需 workaround）。
2. `Permission.php:23` 和 `Role.php:33` 的 `roles()` / `permissions()` 方法缺少 PHPDoc 注释，建议补上 `@return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Permission>` 等泛型标注以增强 IDE 提示。
