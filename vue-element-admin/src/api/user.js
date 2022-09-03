import request from '@/utils/request'

// 查询框或按钮 无参数时查询所有
export function getUserList(parms) {
  return request({
    url: `/sys/user/users?${parms}`,
    method: 'get'
  })
}

// 添加按钮
export function createUser(form) {
  return request({
    url: '/sys/user/users',
    method: 'post',
    data: form
  })
}

// 编辑按钮
export function updateUser(form) {
  return request({
    url: '/sys/user/users',
    method: 'put',
    data: form
  })
}

// 删除按钮
export function deleteUser(id) {
  return request({
    url: `/sys/user/users/${id}`,
    method: 'delete'
  })
}

// 新增、编辑角色权限选项，无需权限分配
export function getRoleOptions(form) {
  return request({
    url: '/sys/user/getroleoptions',
    method: 'get'
  })
}

// 新增、编辑部门权限选项，无需权限分配
export function getDeptOptions(form) {
  return request({
    url: '/sys/user/getdeptoptions',
    method: 'get'
  })
}

// 更新密码
export function updatePassword(form) {
  return request({
    url: '/sys/user/password',
    method: 'put',
    data: form
  })
}
