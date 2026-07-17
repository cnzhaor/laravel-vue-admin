export interface MenuItem {
  id: number
  name: string
  path?: string
  icon?: string
  component?: string
  children: MenuItem[]
}

export interface Profile {
  user: Record<string, any>
  permissions: string[]
  menus: MenuItem[]
}

export interface ApiEnvelope<T> {
  code: number
  message: string
  data: T
}

export type QueueDemoStatus = 'queued' | 'processing' | 'completed' | 'failed'

export interface QueueDemoTask {
  id: string
  user_id: number
  message: string
  delay_seconds: number
  status: QueueDemoStatus
  result: string | null
  created_at: string
  available_at: string
  started_at: string | null
  finished_at: string | null
}
