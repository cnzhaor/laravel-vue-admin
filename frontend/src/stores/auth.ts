import { defineStore } from 'pinia'
import { csrf, http } from '../api/http'
import type { Profile } from '../types'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    profile: null as Profile | null,
    loaded: false,
  }),
  getters: {
    user: (state) => state.profile?.user,
    menus: (state) => state.profile?.menus ?? [],
    permissions: (state) => state.profile?.permissions ?? [],
  },
  actions: {
    async login(username: string, password: string) {
      await csrf()
      const response: any = await http.post('/login', { username, password })
      this.profile = response.data
      this.loaded = true
    },
    async load() {
      try {
        const response: any = await http.get('/me')
        this.profile = response.data
      } finally {
        this.loaded = true
      }
    },
    async logout() {
      await http.post('/logout')
      this.$reset()
    },
    can(code?: string) {
      return !code || this.permissions.includes('*') || this.permissions.includes(code)
    },
  },
})

