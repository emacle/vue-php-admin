import request from '@/utils/request'

// 查询框或按钮 无参数时查询所有
export function getLogList(parms) {
  return request({
    url: `/sys/log/logs?${parms}`,
    method: 'get'
  })
}

export function restoreDB() {
  return request({
    url: '/sys/log/dbrestore',
    method: 'post'
  })
}
