<template>
  <div class="settings-page">
    <div class="page-header"><h2>租户设置</h2></div>

    <div class="panel">
      <h3>基本信息</h3>
      <form @submit.prevent="handleSave">
        <div class="form-group">
          <label>租户名称</label>
          <input v-model="form.name" />
        </div>
        <div class="form-group">
          <label>自定义域名</label>
          <input v-model="form.custom_domain" placeholder="ai.example.com" />
        </div>
        <div class="form-group">
          <label>描述</label>
          <textarea v-model="form.description" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label>联系邮箱</label>
          <input v-model="form.contact_email" type="email" />
        </div>
        <div class="form-group">
          <label>联系电话</label>
          <input v-model="form.contact_phone" />
        </div>
        <button type="submit" class="primary-btn">保存设置</button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const form = ref({
  name: '示例租户',
  custom_domain: 'ai.tenant1.local',
  description: '',
  contact_email: 'admin@tenant1.local',
  contact_phone: '',
})

const handleSave = () => { alert('保存成功') }
</script>

<style scoped>
.page-header { margin-bottom: 20px; }
.page-header h2 { margin: 0; }
.panel { background: var(--bg-color, #fff); border-radius: 8px; padding: 24px; max-width: 600px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
.panel h3 { margin: 0 0 20px; font-size: 15px; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; margin-bottom: 6px; font-size: 13px; color: var(--text-color-secondary, #666); }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px 12px; border: 1px solid var(--border-color, #ddd); border-radius: 6px; font-size: 14px; box-sizing: border-box; background: var(--bg-color, #fff); color: var(--text-color-primary, #333); }
.form-group textarea { resize: vertical; }
.primary-btn { padding: 10px 24px; border: none; border-radius: 6px; background: var(--primary-color, #409eff); color: #fff; font-size: 14px; cursor: pointer; }
</style>
