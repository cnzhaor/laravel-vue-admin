<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()
const loading = ref(false)
const form = reactive({ username: 'admin', password: 'Admin@123456' })

async function submit() {
  loading.value = true
  try {
    await auth.login(form.username, form.password)
    router.replace('/')
  } catch (error: any) {
    ElMessage.error(error.message || '登录失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <el-card class="login-card">
      <h1>通用管理后台</h1>
      <p>Laravel 13 + Vue 3</p>
      <el-form :model="form" @keyup.enter="submit">
        <el-form-item><el-input v-model="form.username" placeholder="用户名" /></el-form-item>
        <el-form-item><el-input v-model="form.password" type="password" show-password placeholder="密码" /></el-form-item>
        <el-button type="primary" :loading="loading" class="full" @click="submit">登录</el-button>
      </el-form>
    </el-card>
  </div>
</template>

