import request from '@/utils/request'

export function fetchList(query) {
  return request({
    url: '/sys/user/list',
    method: 'get',
    params: query
  })
}
