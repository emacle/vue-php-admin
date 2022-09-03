import store from '@/store'

function checkPermission(el, binding) {
  const { value } = binding // value 为 v-perm= "['/sys/menu/view']" 传过来的绑定值 value = ["/sys/menu/view"]
  const ctrlperm = store.getters && store.getters.ctrlperm // ctrlperm = [{ "path": "/sys/menu/view" }, { "path": "/sys/menu/add" }]

  if (value && value instanceof Array) {
    if (value.length > 0) {
      const permissionRoles = value

      const hasPermission = ctrlperm.some(item => {
        return permissionRoles.includes(item.path)
      })

      if (!hasPermission) {
        el.parentNode && el.parentNode.removeChild(el)
      }
    }
  } else {
    throw new Error(`need roles! Like v-permission="['admin','editor']"`)
  }
}

export default {
  inserted(el, binding) {
    checkPermission(el, binding)
  },
  update(el, binding) {
    checkPermission(el, binding)
  }
}
