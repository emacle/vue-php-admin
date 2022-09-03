import request from '@/utils/request'

// 操作：列出机构
export function getDeptList(form) {
  return request({
    url: '/sys/dept/depts',
    method: 'get',
    data: form
  })
}

// 添加按钮
export function createDept(form) {
  return request({
    url: '/sys/dept/depts',
    method: 'post',
    data: form
  })
}

// 编辑按钮
export function updateDept(form) {
  return request({
    url: '/sys/dept/depts',
    method: 'put',
    data: form
  })
}

// 删除按钮
export function deleteDept(id) {
  return request({
    url: `/sys/dept/depts/${id}`,
    method: 'delete'
  })
}
