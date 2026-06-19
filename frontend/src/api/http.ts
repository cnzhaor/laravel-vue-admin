import axios from 'axios'

export const http = axios.create({
  baseURL: '/api/v1',
  withCredentials: true,
  headers: { Accept: 'application/json' },
})

http.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401 && location.pathname !== '/login') location.href = '/login'
    return Promise.reject(error.response?.data ?? error)
  },
)

export async function csrf() {
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
}

