import request from '@/utils/request'
import anotherRequest from '@/utils/anotherRequest'

export function fetchList(query) {
  return request({
    url: '/sys/user/list',
    method: 'get',
    params: query
  })
}

export function fetchListx(query) {
  return anotherRequest({
    url: '/sys/blog',
    method: 'get',
    params: query
  })
}