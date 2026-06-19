<script setup lang="ts">
import type { MenuItem } from '../types'
defineProps<{ items: MenuItem[] }>()
</script>

<template>
  <template v-for="item in items" :key="item.id">
    <el-sub-menu v-if="item.children?.length" :index="item.path || String(item.id)">
      <template #title><span>{{ item.name }}</span></template>
      <SideMenu :items="item.children" />
    </el-sub-menu>
    <el-menu-item v-else :index="item.path">{{ item.name }}</el-menu-item>
  </template>
</template>

