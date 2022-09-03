import request from '@/utils/request'

export function loginByUsername(username, password) {
  const data = {
    username,
    password
  }
  return request({
    url: '/sys/user/login',
    method: 'post',
    data
  })
}

export function logout() {
  return request({
    url: '/sys/user/logout',
    method: 'post'
  })
}

export function getUserInfo(token) {
  return request({
    url: '/sys/user/info',
    method: 'get'
    // params: { token }
  })
}

// github 认证
export function githubAuth(code, state) {
  return request({
    url: '/sys/user/githubauth',
    method: 'get',
    params: { code, state }
  })
}

// gitee 认证
export function giteeAuth(code, state) {
  return request({
    url: '/sys/user/giteeauth',
    method: 'get',
    params: { code, state }
  })
}

export function checkRefreshToken() {
  return request({
    url: '/sys/user/refreshtoken',
    method: 'post'
  })
}
