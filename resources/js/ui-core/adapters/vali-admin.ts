/**
 * Vali Admin 适配器
 * 
 * Vali Admin 是一个基于 Bootstrap 5 的免费管理后台模板
 * 网站: https://pratikborsadiya.in/vali-admin
 */

import type { UIFrameworkAdapter, UIFrameworkMetadata } from '../registry'

export const valiAdminMetadata: UIFrameworkMetadata = {
  name: 'vali-admin',
  label: 'Vali Admin',
  description: '基于 Bootstrap 5 的免费管理后台模板，简洁美观',
  version: '^3.0.0',
  website: 'https://pratikborsadiya.in/vali-admin',
  icon: 'vali-admin:vali-admin',
  features: [
    '基于 Bootstrap 5',
    '响应式设计',
    '多种布局选项',
    '自定义主题',
    '免费开源',
  ],
  installCommand: 'npm install bootstrap @popperjs/core',
}

export const valiAdminAdapter: UIFrameworkAdapter = {
  name: 'vali-admin',
  metadata: valiAdminMetadata,
  
  async install(app) {
    // 导入 Bootstrap JS
    await import('bootstrap/dist/js/bootstrap.bundle.min.js')
    
    // 导入 Bootstrap CSS
    await import('bootstrap/dist/css/bootstrap.min.css')
    
    // 导入 Vali Admin 样式
    // 注意：Vali Admin 的 CSS 需要手动引入或通过自定义构建
    // 这里提供基础变量配置
  },
  
  getComponentMap() {
    return {
      // Vali Admin 基于 Bootstrap，使用相同的组件映射
      Button: 'button',
      Input: 'input',
      Select: 'select',
      Table: 'table',
      Form: 'form',
      Card: 'div.card',
      Dialog: 'div.modal',
      Drawer: 'div.offcanvas',
      Menu: 'ul.nav',
      MenuItem: 'li.nav-item',
      Layout: 'div',
      Header: 'nav.navbar',
      Aside: 'div.col-auto',
      Main: 'div.col',
      Pagination: 'nav[aria-label="pagination"]',
      Tag: 'span.badge',
      Dropdown: 'div.dropdown',
      Breadcrumb: 'nav[aria-label="breadcrumb"]',
      Sidebar: 'aside.sidebar',
      Content: 'main.content',
    }
  },
  
  getThemeVariables(mode) {
    // Vali Admin 主题变量
    const baseVariables = {
      '--sidebar-width': '250px',
      '--sidebar-collapsed-width': '70px',
      '--header-height': '60px',
      '--content-padding': '20px',
    }
    
    if (mode === 'dark') {
      return {
        ...baseVariables,
        '--sidebar-bg': '#2c3e50',
        '--sidebar-text': '#ecf0f1',
        '--sidebar-hover': '#34495e',
        '--header-bg': '#ffffff',
        '--content-bg': '#f5f5f5',
        '--card-bg': '#ffffff',
        '--text-color': '#333333',
        '--text-muted': '#6c757d',
        '--border-color': '#e0e0e0',
      }
    }
    
    return {
      ...baseVariables,
      '--sidebar-bg': '#2c3e50',
      '--sidebar-text': '#ecf0f1',
      '--sidebar-hover': '#34495e',
      '--header-bg': '#ffffff',
      '--content-bg': '#f5f5f5',
      '--card-bg': '#ffffff',
      '--text-color': '#333333',
      '--text-muted': '#6c757d',
      '--border-color': '#e0e0e0',
    }
  },
}
