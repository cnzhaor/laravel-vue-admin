<script setup lang="ts">
import { useAuthStore } from '../stores/auth'
const auth = useAuthStore()
</script>

<template>
  <el-row :gutter="16">
    <el-col :span="8"><el-card><el-statistic title="当前用户" :value="auth.user?.name" /></el-card></el-col>
    <el-col :span="8"><el-card><el-statistic title="授权权限" :value="auth.permissions.length" /></el-card></el-col>
    <el-col :span="8"><el-card><el-statistic title="系统状态" value="运行正常" /></el-card></el-col>
  </el-row>
  <el-card class="welcome"><h2>欢迎回来，{{ auth.user?.name }}</h2><p>从左侧菜单开始管理系统基础数据。</p></el-card>
</template>

