<template>
  <div class="credits-page">
    <div class="page-header"><h2>积分管理</h2></div>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-label">总积分</div>
        <div class="stat-value">{{ balance.total }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">已使用</div>
        <div class="stat-value">{{ balance.used }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">可用积分</div>
        <div class="stat-value">{{ balance.available }}</div>
      </div>
    </div>

    <div class="panel">
      <h3>交易记录</h3>
      <table class="data-table">
        <thead><tr><th>时间</th><th>类型</th><th>金额</th><th>余额</th><th>描述</th></tr></thead>
        <tbody>
          <tr v-for="t in transactions" :key="t.id">
            <td>{{ t.created_at }}</td>
            <td><span :class="['badge', typeClass(t.type)]">{{ typeLabel(t.type) }}</span></td>
            <td :class="{ 'text-green': t.amount > 0, 'text-red': t.amount < 0 }">{{ t.amount > 0 ? '+' : '' }}{{ t.amount }}</td>
            <td>{{ t.balance_after }}</td>
            <td>{{ t.description }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

const balance = ref({ total: 10000, used: 1000, available: 9000 })
const transactions = ref([
  { id: 1, created_at: '2026-06-18', type: 'recharge', amount: 10000, balance_after: 10000, description: '初始充值' },
  { id: 2, created_at: '2026-06-18', type: 'consume', amount: -500, balance_after: 9500, description: 'AI 对话消耗' },
  { id: 3, created_at: '2026-06-18', type: 'consume', amount: -500, balance_after: 9000, description: 'AI 对话消耗' },
])

const typeClass = (t: string) => ({ recharge: 'badge-success', consume: 'badge-danger', gift: 'badge-info', refund: 'badge-warning' }[t] || 'badge-info')
const typeLabel = (t: string) => ({ recharge: '充值', consume: '消费', gift: '赠送', refund: '退款' }[t] || t)
</script>

<style scoped>
.page-header { margin-bottom: 20px; }
.page-header h2 { margin: 0; }
.stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
.stat-card { background: var(--bg-color, #fff); border-radius: 8px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
.stat-label { font-size: 13px; color: var(--text-color-secondary, #999); margin-bottom: 8px; }
.stat-value { font-size: 28px; font-weight: bold; color: var(--primary-color, #409eff); }
.panel { background: var(--bg-color, #fff); border-radius: 8px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
.panel h3 { margin: 0 0 16px; font-size: 15px; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border-color, #eee); font-size: 13px; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
.badge-success { background: #e8f5e9; color: #2e7d32; }
.badge-danger { background: #fce4ec; color: #c62828; }
.badge-info { background: #eee; color: #666; }
.badge-warning { background: #fff3e0; color: #e65100; }
.text-green { color: #2e7d32; }
.text-red { color: #c62828; }
</style>
