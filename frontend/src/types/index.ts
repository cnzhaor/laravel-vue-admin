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

