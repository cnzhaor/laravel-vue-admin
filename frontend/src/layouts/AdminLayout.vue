<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import SideMenu from '../components/SideMenu.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const active = computed(() => route.path)

async function logout() {
  await auth.logout()
  router.replace('/login')
}
</script>

<template>
  <el-container class="admin-shell">
    <el-aside width="220px" class="sidebar">
      <div class="brand">通用管理后台</div>
      <el-menu router :default-active="active" background-color="#17233d" text-color="#c7d2e6" active-text-color="#409eff">
        <el-menu-item index="/">工作台</el-menu-item>
        <SideMenu :items="auth.menus" />
      </el-menu>
    </el-aside>
    <el-container>
      <el-header class="header">
        <el-breadcrumb separator="/">
          <el-breadcrumb-item>首页</el-breadcrumb-item>
          <el-breadcrumb-item>{{ route.meta.title }}</el-breadcrumb-item>
        </el-breadcrumb>
        <el-dropdown>
          <span>{{ auth.user?.name }} ▾</span>
          <template #dropdown>
            <el-dropdown-menu><el-dropdown-item @click="logout">退出登录</el-dropdown-item></el-dropdown-menu>
          </template>
        </el-dropdown>
      </el-header>
      <div class="route-tab">{{ route.meta.title }}</div>
      <el-main><router-view :key="route.fullPath" /></el-main>
    </el-container>
  </el-container>
</template>

