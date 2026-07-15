<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { http } from '../api/http'

type ApiEnvelope<T> = { code: number; message: string; data: T }
type Status = { mode: 'mock' | 'live'; configured: boolean; pid: string | null; convert_method: string; bill_method: string }
type ShareInfo = { share_command?: string; share_link?: string; zlink?: string; deeplink?: string }
type Conversion = {
  command_info?: {
    command_type?: string
    pid?: string
    product_info?: {
      product_id?: string
      title?: string
      price?: number
      cos_ratio?: number
      estimated_commission?: number
      external_info?: string
      share_info?: ShareInfo
    }
  }
}
type Bill = { order_id: string; product_name: string; pay_amount: number; commission_amount: number; status: string; settle_time: string }

const status = ref<Status>()
const converting = ref(false)
const querying = ref(false)
const conversion = ref<Conversion>()
const bills = ref<Bill[]>([])
const total = ref(0)
const form = reactive({
  command: '7:/ 长按复制此条消息，打开抖音搜索，查看商品详情 ##DemoCommand##',
  external_info: `user_${Date.now().toString().slice(-8)}`,
})
const billQuery = reactive({ date: new Date().toISOString().slice(0, 10), page: 1, page_size: 20 })

const product = computed(() => conversion.value?.command_info?.product_info)
const share = computed(() => product.value?.share_info)
const money = (cents?: number) => `¥${((Number(cents) || 0) / 100).toFixed(2)}`

async function loadStatus() {
  const response = await http.get<never, ApiEnvelope<Status>>('/douyin-rebate/status')
  status.value = response.data
}

async function convert() {
  if (!form.command.trim()) return ElMessage.warning('请输入抖口令或抖音短链')
  converting.value = true
  try {
    const response = await http.post<never, ApiEnvelope<Conversion>>('/douyin-rebate/convert', form)
    conversion.value = response.data
    ElMessage.success(response.message)
  } catch (error: any) {
    ElMessage.error(error?.message || '转链失败')
  } finally {
    converting.value = false
  }
}

async function queryBills() {
  querying.value = true
  try {
    const response = await http.get<never, ApiEnvelope<{ list?: Bill[]; orders?: Bill[]; total?: number }>>('/douyin-rebate/bills', { params: billQuery })
    bills.value = response.data.list ?? response.data.orders ?? []
    total.value = response.data.total ?? bills.value.length
  } catch (error: any) {
    ElMessage.error(error?.message || '账单查询失败')
  } finally {
    querying.value = false
  }
}

async function copy(value?: string) {
  if (!value) return
  await navigator.clipboard.writeText(value)
  ElMessage.success('已复制')
}

onMounted(async () => {
  try {
    await Promise.all([loadStatus(), queryBills()])
  } catch (error: any) {
    ElMessage.error(error?.message || '初始化失败')
  }
})
</script>

<template>
  <div class="rebate-demo">
    <el-alert
      v-if="status"
      :type="status.mode === 'mock' ? 'warning' : 'success'"
      :closable="false"
      show-icon
      :title="status.mode === 'mock' ? '当前为 Mock 模式，不会请求抖音服务器' : '当前为 Live 模式，将调用抖音电商开放平台'"
    >
      <template #default>
        PID：{{ status.pid || '未配置' }}；接口：{{ status.convert_method }}
      </template>
    </el-alert>

    <el-row :gutter="20" class="demo-grid">
      <el-col :span="12">
        <el-card shadow="never">
          <template #header><div class="card-title"><span>1. 解析并转链</span><el-tag>抖客分销</el-tag></div></template>
          <el-form label-position="top">
            <el-form-item label="抖口令 / 抖音短链">
              <el-input v-model="form.command" type="textarea" :rows="5" maxlength="2000" show-word-limit />
            </el-form-item>
            <el-form-item label="渠道标识 external_info">
              <el-input v-model="form.external_info" maxlength="40" placeholder="例如 user_1001" />
              <div class="field-tip">只允许数字、字母和下划线，可用于关联站内用户或推广渠道。</div>
            </el-form-item>
            <el-button type="primary" :loading="converting" @click="convert">生成返利推广素材</el-button>
          </el-form>
        </el-card>
      </el-col>

      <el-col :span="12">
        <el-card shadow="never" class="result-card">
          <template #header><div class="card-title"><span>2. 转链结果</span><el-tag v-if="product" type="success">可推广</el-tag></div></template>
          <el-empty v-if="!product" description="提交左侧内容后显示推广素材" />
          <template v-else>
            <el-descriptions :column="2" border>
              <el-descriptions-item label="商品">{{ product.title || '抖音商品' }}</el-descriptions-item>
              <el-descriptions-item label="商品 ID">{{ product.product_id }}</el-descriptions-item>
              <el-descriptions-item label="价格">{{ money(product.price) }}</el-descriptions-item>
              <el-descriptions-item label="预估佣金">{{ money(product.estimated_commission) }}（{{ product.cos_ratio || '-' }}%）</el-descriptions-item>
              <el-descriptions-item label="渠道标识">{{ product.external_info || form.external_info }}</el-descriptions-item>
              <el-descriptions-item label="PID">{{ conversion?.command_info?.pid }}</el-descriptions-item>
            </el-descriptions>
            <div class="material" v-if="share?.share_command">
              <div class="material-label">推广口令</div>
              <div class="material-value">{{ share.share_command }}</div>
              <el-button link type="primary" @click="copy(share.share_command)">复制</el-button>
            </div>
            <div class="material" v-if="share?.zlink || share?.share_link">
              <div class="material-label">推广链接</div>
              <div class="material-value link">{{ share.zlink || share.share_link }}</div>
              <el-button link type="primary" @click="copy(share.zlink || share.share_link)">复制</el-button>
            </div>
          </template>
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="never">
      <template #header>
        <div class="card-title">
          <span>3. 结算账单</span>
          <div class="bill-actions">
            <el-date-picker v-model="billQuery.date" type="date" value-format="YYYY-MM-DD" :clearable="false" />
            <el-button :loading="querying" @click="queryBills">查询</el-button>
          </div>
        </div>
      </template>
      <el-table :data="bills" v-loading="querying">
        <el-table-column prop="order_id" label="订单号" min-width="190" />
        <el-table-column prop="product_name" label="商品" min-width="180" />
        <el-table-column label="支付金额" width="120"><template #default="scope">{{ money(scope.row.pay_amount) }}</template></el-table-column>
        <el-table-column label="返佣金额" width="120"><template #default="scope"><strong class="commission">{{ money(scope.row.commission_amount) }}</strong></template></el-table-column>
        <el-table-column prop="status" label="状态" width="110"><template #default="scope"><el-tag :type="scope.row.status === '已结算' ? 'success' : 'warning'">{{ scope.row.status }}</el-tag></template></el-table-column>
        <el-table-column prop="settle_time" label="结算时间" width="180" />
      </el-table>
      <div class="table-foot">共 {{ total }} 条；Live 模式下官方账单接口仅支持按单日查询。</div>
    </el-card>
  </div>
</template>

<style scoped>
.rebate-demo { display: flex; flex-direction: column; gap: 18px; }
.demo-grid { margin-top: 0; }
.card-title { display: flex; align-items: center; justify-content: space-between; font-weight: 650; }
.field-tip, .table-foot { color: #909399; font-size: 12px; margin-top: 7px; }
.result-card { height: 100%; }
.material { display: grid; grid-template-columns: 82px 1fr 46px; gap: 10px; align-items: center; padding: 13px 0; border-bottom: 1px solid #ebeef5; font-size: 13px; }
.material-label { color: #909399; }
.material-value { line-height: 1.55; word-break: break-all; }
.material-value.link { color: #409eff; }
.bill-actions { display: flex; gap: 10px; }
.commission { color: #f56c6c; }
.table-foot { text-align: right; margin-top: 12px; }
</style>
