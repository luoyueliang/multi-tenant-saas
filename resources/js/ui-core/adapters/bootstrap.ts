/**
 * Bootstrap 适配器
 */

import type { UIFrameworkAdapter, UIFrameworkMetadata } from '../registry'

export const bootstrapMetadata: UIFrameworkMetadata = {
  name: 'bootstrap',
  label: 'Bootstrap',
  description: '全球最流行的前端框架，简洁、直观、响应式',
  version: '^5.3.0',
  website: 'https://getbootstrap.com',
  icon: 'bootstrap:bootstrap',
  features: [
    '响应式设计',
    '丰富的组件',
    '强大的栅格系统',
    '自定义主题',
    '广泛的社区支持',
  ],
  installCommand: 'npm install bootstrap @popperjs/core',
}

export const bootstrapAdapter: UIFrameworkAdapter = {
  name: 'bootstrap',
  metadata: bootstrapMetadata,
  
  async install(app) {
    // 导入 Bootstrap JS
    await import('bootstrap/dist/js/bootstrap.bundle.min.js')
    
    // 导入 Bootstrap CSS
    await import('bootstrap/dist/css/bootstrap.min.css')
  },
  
  getComponentMap() {
    return {
      // Bootstrap 使用原生 HTML + CSS 类，映射到自定义组件
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
      Tooltip: 'div.tooltip',
      Popover: 'div.popover',
      Alert: 'div.alert',
      Toast: 'div.toast',
    }
  },
  
  getThemeVariables(mode) {
    if (mode === 'dark') {
      return {
        '--bs-body-bg': '#212529',
        '--bs-body-color': '#dee2e6',
        '--bs-border-color': '#495057',
        '--bs-card-bg': '#2b3035',
        '--bs-card-border-color': '#495057',
        '--bs-navbar-bg': '#343a40',
        '--bs-table-bg': '#2b3035',
        '--bs-table-border-color': '#495057',
      }
    }
    return {
      '--bs-body-bg': '#ffffff',
      '--bs-body-color': '#212529',
      '--bs-border-color': '#dee2e6',
    }
  },
}
