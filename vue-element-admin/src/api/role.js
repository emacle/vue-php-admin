import request from '@/utils/request'

// 操作：列出菜单
export function getRoleList(form) {
  return request({
    url: '/sys/role/roles',
    method: 'get',
    data: form
  })
}

// 添加按钮
export function createRole(form) {
  return request({
    url: '/sys/role/roles',
    method: 'post',
    data: form
  })
}

// 编辑按钮
export function updateRole(form) {
  return request({
    url: '/sys/role/roles',
    method: 'put',
    data: form
  })
}

// 删除按钮
export function deleteRole(id) {
  return request({
    url: `/sys/role/roles/${id}`,
    method: 'delete'
  })
}

// // 查询按钮
// export function viewRole(rmvFile, filename) {
//   return request({
//     url: '/sys/role/view',
//     method: 'get'
//   })
// }

// 获取所有菜单类权限 不需权限验证
export function getAllMenus() {
  return request({
    url: '/sys/role/allmenus',
    method: 'get'
  })
}
// 获取所有角色类权限 不需权限验证
export function getAllRoles() {
  return request({
    url: '/sys/role/allroles',
    method: 'get'
  })
}
// 获取所有部门类权限 不需权限验证
export function getAllDepts() {
  return request({
    url: '/sys/role/alldepts',
    method: 'get'
  })
}

//  获取角色对应菜单 不需权限验证
export function getRoleMenu(form) {
  return request({
    url: '/sys/role/rolemenu',
    method: 'post',
    data: form
  })
}

//  获取角色拥有角色权限 不需权限验证
export function getRoleRole(form) {
  return request({
    url: '/sys/role/rolerole',
    method: 'post',
    data: form
  })
}

//  获取角色拥有部门数据类权限 不需权限验证
export function getRoleDept(form) {
  return request({
    url: '/sys/role/roledept',
    method: 'post',
    data: form
  })
}

export function saveRolePerms(roleId, rolePerms, roleScope) {
  return request({
    url: '/sys/role/saveroleperm',
    method: 'post',
    data: { roleId, rolePerms, roleScope }
  })
}

// 下面三个api vue-element-admin 框架自带角色权限页面,可考虑删除，角色权限测试页面里引用 @src/permission/role.vue
export function getRoutes() {
  return request({
    url: '/vue-element-admin/routes',
    method: 'get'
  })
}

export function getRoles() {
  return request({
    url: '/vue-element-admin/roles',
    method: 'get'
  })
}

export function addRole(data) {
  return request({
    url: '/vue-element-admin/role',
    method: 'post',
    data
  })
}
