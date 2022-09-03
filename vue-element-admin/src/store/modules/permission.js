import { constantRoutes } from '@/router'
import Layout from '@/layout'

/**
 * Use meta.role to determine if the current user has permission
 * @param roles
 * @param route
 */
// function hasPermission(roles, route) {
//   if (route.meta && route.meta.roles) {
//     return roles.some(role => route.meta.roles.includes(role))
//   } else {
//     return true
//   }
// }

/**
 * Filter asynchronous routing tables by recursion
 * @param routes asyncRoutes
 * @param roles
 */
// export function filterAsyncRoutes(routes, roles) {
//   const res = []

//   routes.forEach(route => {
//     const tmp = { ...route }
//     if (hasPermission(roles, tmp)) {
//       if (tmp.children) {
//         tmp.children = filterAsyncRoutes(tmp.children, roles)
//       }
//       res.push(tmp)
//     }
//   })

//   return res
// }

// /**
//  * 将后台的路由表进行格式化
//  * @param {*} asyncRouterMap
//  */
// function convertRouter(asyncRouterMap) {
//   const accessedRouters = []
//   if (asyncRouterMap) {
//     asyncRouterMap.forEach(item => {
//       var parent = generateRouter(item, true)
//       var children = []
//       if (item.children) {
//         item.children.forEach(child => {
//           children.push(generateRouter(child, false))
//         })
//       }
//       parent.children = children
//       accessedRouters.push(parent)
//     })
//   }
//   accessedRouters.push({ path: '*', redirect: '/404', hidden: true })
//   return accessedRouters
// }

// function generateRouter(item, isParent) {
//   // 表单 输入 path： /sys/menu
//   // redirect： 判断如果是一级菜单 noRedirect
//   // component: 判断如果是一级菜单component: Layout
//   // console.log(item.component)

//   var router = {
//     path: item.path,
//     name: item.name,
//     meta: item.meta,
//     // component: item.component === 'Layout' ? Layout : () => import(`@/views/${item.component}.vue`),
//     // 解决Cannot read property 'range' of null 错误
//     component: item.component === 'Layout' ? Layout : resolve => require([`@/views/${item.component}.vue`], resolve),
//     // component: item.component === 'Layout' ? Layout : loadView(item.component),
//     // redirect: item.component === 'Layout' ? 'noredirect' :  '',
//     // 面包屑上 点击 redirect 的 url  首页/系统管理/菜单管理  , 可点击系统管理
//     redirect: item.redirect ? item.redirect : item.component === 'Layout' ? 'noRedirect' : '',
//     // component: isParent ? Layout : componentsMap[item.name],
//     alwaysShow: item.children.length === 1
//   }
//   // console.log('router....', router)
//   return router
// }

// 遍历后台传来的路由字符串，转换为组件对象
function filterAsyncRouter(asyncRouterMap) {
  return asyncRouterMap.filter(route => {
    if (route.component) {
      // Layout组件特殊处理
      if (route.component === 'Layout') {
        route.component = Layout
      } else {
        route.component = loadView(route.component)
      }
    }
    // 面包屑上 点击 redirect 的 url  首页/系统管理/菜单管理, 可点击系统管理
    route.redirect = route.redirect ? route.redirect : route.component === 'Layout' ? 'noRedirect' : ''
    route.alwaysShow = route.children.length === 1

    if (route.children != null && route.children && route.children.length) {
      route.children = filterAsyncRouter(route.children)
    }
    return true
  })
}

export const loadView = (view) => { // 路由懒加载
  // return () => import(`@/views/${view}`)
  // 解决Cannot read property 'range' of null 错误 https://blog.csdn.net/weixin_42406046/article/details/103718293
  return resolve => require([`@/views/${view}.vue`], resolve)
}

const state = {
  routes: [],
  addRoutes: []
}

const mutations = {
  SET_ROUTES: (state, routes) => {
    state.addRoutes = routes
    state.routes = constantRoutes.concat(routes)
  }
}

const actions = {
  // 根据后端传过来的路由树生成前端可用的菜单路由
  generateRoutes({ commit, state }, data) {
    return new Promise(resolve => {
      const asyncRouterMap = data

      const accessedRoutes = filterAsyncRouter(asyncRouterMap)
      accessedRoutes.push({ path: '*', redirect: '/404', hidden: true })

      // const accessedRoutes = convertRouter(asyncRouterMap)
      // console.log('accessedRoutes.........', accessedRoutes)

      commit('SET_ROUTES', accessedRoutes)
      resolve(accessedRoutes)
    })
  }
} // actions end

export default {
  namespaced: true,
  state,
  mutations,
  actions
}
