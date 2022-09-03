import ls from 'local-storage'

const TokenKey = 'Admin-Token'
const RefreshTokenKey = 'Admin-Refresh-Token'

export function getToken() {
  return ls.get(TokenKey)
}

export function setToken(token) {
  return ls.set(TokenKey, token)
}

export function removeToken() {
  return ls.remove(TokenKey)
}

export function getRefreshToken() {
  return ls.get(RefreshTokenKey)
}

export function setRefreshToken(token) {
  return ls.set(RefreshTokenKey, token)
}

export function removeRefreshToken() {
  return ls.remove(RefreshTokenKey)
}
