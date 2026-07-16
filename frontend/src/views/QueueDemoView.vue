<script setup lang="ts">
import { computed, onUnmounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { createQueueDemoTask, getQueueDemoTask } from '../api/queueDemo'
import type { QueueDemoStatus, QueueDemoTask } from '../types'

const form = reactive({
  message: '这是一条 Redis 队列演示任务',
  delaySeconds: 5,
})
const submitting = ref(false)
const task = ref<QueueDemoTask | null>(null)
let pollingTimer: ReturnType<typeof setTimeout> | undefined

const statusText: Record<QueueDemoStatus, string> = {
  queued: '等待 Worker',
  processing: '执行中',
  completed: '已完成',
  failed: '执行失败',
}
const statusType = computed(() => {
  if (task.value?.status === 'completed') return 'success'
  if (task.value?.status === 'failed') return 'danger'
  if (task.value?.status === 'processing') return 'warning'
  return 'info'
})
const activeStep = computed(() => {
  if (!task.value) return 0
  if (task.value.status === 'queued') return 1
  if (task.value.status === 'processing') return 2
  return 3
})

function errorMessage(error: unknown): string {
  if (typeof error === 'object' && error !== null && 'message' in error) {
    const message = (error as { message?: unknown }).message
    if (typeof message === 'string') return message
  }
  return '操作失败，请稍后重试'
}

function formatTime(value: string | null): string {
  return value ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'medium' }).format(new Date(value)) : '-'
}

function stopPolling() {
  if (pollingTimer) clearTimeout(pollingTimer)
  pollingTimer = undefined
}

async function pollTask(taskId: string) {
  try {
    const response = await getQueueDemoTask(taskId)
    task.value = response.data
    if (response.data.status === 'queued' || response.data.status === 'processing') {
      pollingTimer = setTimeout(() => pollTask(taskId), 1000)
    }
  } catch (error: unknown) {
    stopPolling()
    ElMessage.error(errorMessage(error))
  }
}

async function submitTask() {
  if (!form.message.trim()) return ElMessage.warning('请输入演示内容')

  submitting.value = true
  stopPolling()
  try {
    const response = await createQueueDemoTask(form.message.trim(), form.delaySeconds)
    task.value = response.data
    ElMessage.success(response.message)
    pollingTimer = setTimeout(() => pollTask(response.data.id), 500)
  } catch (error: unknown) {
    ElMessage.error(errorMessage(error))
  } finally {
    submitting.value = false
  }
}

onUnmounted(stopPolling)
</script>

<template>
  <div class="queue-demo">
    <el-alert
      title="此页会创建真实的 Redis 队列任务，由独立 Queue Worker 异步执行。"
      type="info"
      :closable="false"
      show-icon
    />

    <el-row :gutter="20">
      <el-col :xs="24" :lg="10">
        <el-card shadow="never">
          <template #header><strong>1. 提交任务</strong></template>
          <el-form label-position="top">
            <el-form-item label="演示内容">
              <el-input v-model="form.message" maxlength="100" show-word-limit />
            </el-form-item>
            <el-form-item label="模拟处理时间">
              <el-slider v-model="form.delaySeconds" :min="1" :max="10" show-input />
            </el-form-item>
            <el-button type="primary" :loading="submitting" @click="submitTask">
              提交到 Redis 队列
            </el-button>
          </el-form>
        </el-card>
      </el-col>

      <el-col :xs="24" :lg="14">
        <el-card shadow="never" class="status-card">
          <template #header>
            <div class="card-title">
              <strong>2. Worker 执行状态</strong>
              <el-tag v-if="task" :type="statusType">{{ statusText[task.status] }}</el-tag>
            </div>
          </template>

          <el-empty v-if="!task" description="提交任务后，这里会实时显示处理进度" />
          <template v-else>
            <el-steps :active="activeStep" finish-status="success" align-center>
              <el-step title="已入队" />
              <el-step title="Worker 领取" />
              <el-step title="处理完成" />
            </el-steps>

            <el-descriptions :column="1" border class="task-details">
              <el-descriptions-item label="任务 ID"><span class="task-id">{{ task.id }}</span></el-descriptions-item>
              <el-descriptions-item label="演示内容">{{ task.message }}</el-descriptions-item>
              <el-descriptions-item label="创建时间">{{ formatTime(task.created_at) }}</el-descriptions-item>
              <el-descriptions-item label="开始时间">{{ formatTime(task.started_at) }}</el-descriptions-item>
              <el-descriptions-item label="完成时间">{{ formatTime(task.finished_at) }}</el-descriptions-item>
              <el-descriptions-item label="Worker 结果">{{ task.result || '等待中…' }}</el-descriptions-item>
            </el-descriptions>
          </template>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<style scoped>
.queue-demo { display: flex; flex-direction: column; gap: 18px; }
.card-title { display: flex; align-items: center; justify-content: space-between; }
.status-card { min-height: 360px; }
.task-details { margin-top: 28px; }
.task-id { word-break: break-all; }
@media (max-width: 1199px) { .status-card { margin-top: 18px; } }
</style>
