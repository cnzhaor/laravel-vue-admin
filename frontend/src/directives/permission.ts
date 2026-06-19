import type { Directive } from 'vue'
import { useAuthStore } from '../stores/auth'

const permission: Directive<HTMLElement, string> = {
  mounted(el, binding) {
    if (!useAuthStore().can(binding.value)) el.remove()
  },
}

export default permission

