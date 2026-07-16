import { http } from './http'
import type { ApiEnvelope, QueueDemoTask } from '../types'

export async function createQueueDemoTask(message: string, delaySeconds: number) {
  return http.post<never, ApiEnvelope<QueueDemoTask>>('/queue-demo/jobs', {
    message,
    delay_seconds: delaySeconds,
  })
}

export async function getQueueDemoTask(taskId: string) {
  return http.get<never, ApiEnvelope<QueueDemoTask>>(`/queue-demo/jobs/${taskId}`)
}
