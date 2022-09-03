import { loginByUsername, logout, getUserInfo, githubAuth, giteeAuth, checkRefreshToken } from '@/api/login'
import { getToken, setToken, removeToken, getRefreshToken, setRefreshToken, removeRefreshToken } from '@/utils/auth'

import router, { resetRouter } from '@/router'

const state = {
  user: '',
  status: '',
  code: '', // 存储三方登录认证 code 参数 不是必须
  token: getToken(),
  refresh_token: getRefreshToken(),
  name: '',
  avatar: '',
  introduction: '',
  roles: [],
  ctrlperm: []
}

const mutations = {
  SET_CODE: (state, code) => {
    state.code = code
  },
  SET_TOKEN: (state, token) => {
    state.token = token
  },
  SET_REFRESH_TOKEN: (state, token) => {
    state.refresh_token = token
  },
  SET_INTRODUCTION: (state, introduction) => {
    state.introduction = introduction
  },
  SET_NAME: (state, name) => {
    state.name = name
  },
  SET_AVATAR: (state, avatar) => {
    state.avatar = avatar
  },
  SET_ROLES: (state, roles) => {
    state.roles = roles
  },
  SET_STATUS: (state, status) => {
    state.status = status
  },
  SET_PHONE: (state, phone) => {
    state.phone = phone
  },
  SET_IDENTIFY: (state, identify) => {
    state.identify = identify
  },
  SET_CTRLPERM: (state, ctrlperm) => {
    state.ctrlperm = ctrlperm
  }
}

const actions = {
  // 用户名登录
  LoginByUsername({ commit }, userInfo) {
    const username = userInfo.username.trim()
    return new Promise((resolve, reject) => {
      loginByUsername(username, userInfo.password).then(response => {
        const data = response.data
        commit('SET_TOKEN', data.token)
        commit('SET_REFRESH_TOKEN', data.refresh_token)
        setToken(data.token)
        setRefreshToken(data.refresh_token)
        resolve()
      }).catch(error => {
        reject(error)
      })
    })
  },

  // 第三方验证登录
  LoginByThirdparty({ commit, state }, authParms) {
    return new Promise((resolve, reject) => {
      // 按类型拆分 防止不同的三方oauth 方式不一致
      if (authParms.auth_type === 'github') { // github
        githubAuth(authParms.code, authParms.state).then(response => {
          console.log('githubAuth response...', response)
          const data = response.data
          commit('SET_TOKEN', data.token)
          commit('SET_REFRESH_TOKEN', data.refresh_token)
          setToken(data.token)
          setRefreshToken(data.refresh_token)
          resolve()
        }).catch(error => {
          reject(error)
        })
      } else if (authParms.auth_type === 'gitee') { // 码云
        giteeAuth(authParms.code, authParms.state).then(response => {
          console.log('giteeAuth response...', response)
          const data = response.data
          commit('SET_TOKEN', data.token)
          commit('SET_REFRESH_TOKEN', data.refresh_token)
          setToken(data.token)
          setRefreshToken(data.refresh_token)
          resolve()
        }).catch(error => {
          reject(error)
        })
      } else {
        // 其他
        console.log('other login...')
      }
    })
  },

  // 获取用户信息
  GetUserInfo({ commit, state }) {
    return new Promise((resolve, reject) => {
      getUserInfo(state.token).then(response => {
        const { data } = response
        // console.log('GetUserInfo...', data)
        // 由于mockjs 不支持自定义状态码只能这样hack
        if (!data) {
          reject('Verification failed, please login again.')
        }

        const { roles, username, avatar, introduction, phone, identify, ctrlperm } = data

        // console.log('GetUserInfo', roles, name, avatar)

        // roles must be a non-empty array
        if (!roles || roles.length <= 0) {
          reject('getInfo: roles must be a non-null array!')
        }

        commit('SET_ROLES', roles)
        commit('SET_NAME', username)
        commit('SET_AVATAR', avatar)
        commit('SET_INTRODUCTION', introduction)
        commit('SET_PHONE', phone)
        commit('SET_IDENTIFY', identify)
        commit('SET_CTRLPERM', ctrlperm)

        resolve(data)
      }).catch(error => {
        reject(error)
      })
    })
  },

  // user logout
  LogOut({ commit, state, dispatch }) {
    return new Promise((resolve, reject) => {
      logout(state.token).then(() => {
        commit('SET_TOKEN', '')
        commit('SET_REFRESH_TOKEN', '')
        commit('SET_ROLES', [])
        removeToken()
        removeRefreshToken()
        resetRouter()

        // reset visited views and cached views
        // to fixed https://github.com/PanJiaChen/vue-element-admin/issues/2485
        dispatch('tagsView/delAllViews', null, { root: true })

        resolve()
      }).catch(error => {
        reject(error)
      })
    })
  },

  // 前端 登出
  FedLogOut({ commit, dispatch }) {
    return new Promise(resolve => {
      commit('SET_TOKEN', '')
      commit('SET_REFRESH_TOKEN', '')
      removeToken()
      removeRefreshToken()
      resetRouter()

      // reset visited views and cached views
      // to fixed https://github.com/PanJiaChen/vue-element-admin/issues/2485
      dispatch('tagsView/delAllViews', null, { root: true })

      resolve()
    })
  },

  // accessToken超时
  handleCheckRefreshToken({ state, commit }) {
    return new Promise((resolve, reject) => {
      // console.log('state.token', state.token)
      // console.log('state.refresh_token', state.refresh_token)
      checkRefreshToken().then(res => {
        const data = res.data
        commit('SET_TOKEN', data.token)
        commit('SET_REFRESH_TOKEN', data.refresh_token)
        setToken(data.token)
        setRefreshToken(data.refresh_token)

        resolve()
      }).catch((error) => {
        console.log('error.......', error)
        reject(error)
      })
    })
  },

  // dynamically modify permissions
  async changeRoles({ commit, dispatch }, role) {
    const token = role + '-token'

    commit('SET_TOKEN', token)
    setToken(token)

    const { roles } = await dispatch('getInfo')

    resetRouter()

    // generate accessible routes map based on roles
    const accessRoutes = await dispatch('permission/generateRoutes', roles, { root: true })
    // dynamically add accessible routes
    router.addRoutes(accessRoutes)

    // reset visited views and cached views
    dispatch('tagsView/delAllViews', null, { root: true })
  }
}

export default {
  namespaced: true,
  state,
  mutations,
  actions
}
