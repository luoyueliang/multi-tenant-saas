<template>
  <div class="members-page">
    <div class="page-header">
      <h2>成员管理</h2>
      <button class="primary-btn" @click="showInvite = true">+ 邀请成员</button>
    </div>

    <div class="panel">
      <table class="data-table">
        <thead><tr><th>用户ID</th><th>姓名</th><th>邮箱</th><th>角色</th><th>状态</th><th>加入时间</th><th>操作</th></tr></thead>
        <tbody>
          <tr v-for="m in members" :key="m.user_id">
            <td>{{ m.user_id }}</td>
            <td>{{ m.name }}</td>
            <td>{{ m.email }}</td>
            <td><span :class="['badge', m.role === 'tenant_admin' ? 'badge-warning' : 'badge-info']">{{ m.role === 'tenant_admin' ? '管理员' : '成员' }}</span></td>
            <td><span :class="['badge', m.is_active ? 'badge-success' : 'badge-danger']">{{ m.is_active ? '激活' : '未激活' }}</span></td>
            <td>{{ m.joined_at }}</td>
            <td><button class="link-btn" @click="handleEdit(m)">编辑</button></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="modal-backdrop" v-if="showInvite" @click="showInvite = false">
      <div class="modal-content" @click.stop>
        <h3>邀请成员</h3>
        <form @submit.prevent="handleInvite">
          <div class="form-group">
            <label>邮箱</label>
            <input v-model="inviteForm.email" type="email" required />
          </div>
          <div class="form-group">
            <label>角色</label>
            <select v-model="inviteForm.role">
              <option value="end_user">成员</option>
              <option value="tenant_admin">管理员</option>
            </select>
          </div>
          <div class="form-actions">
            <button type="button" @click="showInvite = false">取消</button>
            <button type="submit" class="primary-btn">发送邀请</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

const showInvite = ref(false)
const members = ref<any[]>([])
const inviteForm = reactive({ email: '', role: 'end_user' })

const fetchMembers = async () => {
  try {
    const tenantId = localStorage.getItem('console_tenant_id')
    if (tenantId) {
      const res = await axios.get(`/api/v1/tenants/${tenantId}/members`)
      members.value = res.data.data || []
    }
  } catch {
    members.value = [
      { user_id: '1', name: '租户管理员', email: 'admin@tenant1.local', role: 'tenant_admin', is_active: true, joined_at: '2026-06-18' },
      { user_id: '2', name: '普通用户', email: 'user@tenant1.local', role: 'end_user', is_active: true, joined_at: '2026-06-18' },
    ]
  }
}

const handleInvite = () => { showInvite.value = false }
const handleEdit = (m: any) => { alert('编辑: ' + m.name) }

onMounted(fetchMembers)
</script>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-header h2 { margin: 0; }
.primary-btn { padding: 8px 16px; border: none; border-radius: 6px; background: var(--primary-color, #409eff); color: #fff; cursor: pointer; font-size: 13px; }
.panel { background: var(--bg-color, #fff); border-radius: 8px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border-color, #eee); font-size: 13px; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
.badge-success { background: #e8f5e9; color: #2e7d32; }
.badge-info { background: #eee; color: #666; }
.badge-warning { background: #fff3e0; color: #e65100; }
.badge-danger { background: #fce4ec; color: #c62828; }
.link-btn { background: none; border: none; color: var(--primary-color, #409eff); cursor: pointer; font-size: 13px; }
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 3000; }
.modal-content { background: var(--bg-color, #fff); border-radius: 8px; padding: 24px; width: 400px; }
.modal-content h3 { margin: 0 0 20px; }
.form-group { margin-bottom: 14px; }
.form-group label { display: block; margin-bottom: 4px; font-size: 13px; color: var(--text-color-secondary, #666); }
.form-group input, .form-group select { width: 100%; padding: 8px 12px; border: 1px solid var(--border-color, #ddd); border-radius: 6px; font-size: 13px; box-sizing: border-box; }
.form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; }
.form-actions button { padding: 8px 16px; border: 1px solid var(--border-color, #ddd); border-radius: 6px; cursor: pointer; }
</style>
