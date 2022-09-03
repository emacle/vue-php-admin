import perm from './perm'

// 根据@/directive/permission 权限判断修改
const install = function(Vue) {
  Vue.directive('perm', perm)
}

if (window.Vue) {
  window['perm'] = perm
  Vue.use(install); // eslint-disable-line
}

perm.install = install
export default perm
