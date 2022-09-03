<script>
export default {
  name: 'AuthRedirect',
  created() {
    // const hash = window.location.search.slice(1)
    // if (window.localStorage) {
    //   window.localStorage.setItem('x-admin-oauth-code', hash)
    //   window.close()
    // }
    this.thirdLogin()
  },
  methods: {
    thirdLogin() {
      console.log(
        'in AuthRedirect ... this.$store.state.user.code',
        this.$store.state.user.code,
        this.$store.state.user
      )
      // ***window.name*** 是 socialsignin.vue 中 openWindow(url, thirdpart, 1024, 800) 传过来三方类型 子窗口关闭时 回传给主窗口, 用于区分三方类型,
      // 在主窗口使用vue store不行，因此 href 发生了跳转 vue store 变量被清空了
      console.log(window.name)
      console.log(window.location)
      // 1.  授权成功后, github 返回给 AuthRedirect子窗口的浏览器 回调地址 并带上 ?code=8789d613d1fa9a19732a&state= 参数
      //     地址栏URL如 http://localhost:9527/auth-redirect?code=8789d613d1fa9a19732a&state=xyz
      //     其中 http://localhost:9527/auth-redirect 是定义 githubHandleClick() 里定义或服务端返回的回调地址
      //     const url = 'https://github.com/login/oauth/authorize?client_id=94aae05609c96ffb7d3b&redirect_uri=http://localhost:9527/auth-redirect'
      //     此时 window.location.href   => http://localhost:9527/auth-redirect?code=8789d613d1fa9a19732a&state=137caabc2b409f0cccd14834fc848041
      //         window.location.search  => ?code=8789d613d1fa9a19732a&state=137caabc2b409f0cccd14834fc848041

      // 2. 调用 window.opener 方法 给 父窗口 的 location.href 赋值 => http://localhost:9527/login?code=8789d613d1fa9a19732a&state=xyz
      window.opener.location.href =
        window.location.origin +
        '/login' +
        window.location.search +
        '&auth_type=' +
        window.name

      //    注意：此处加入 /login 是因为 router /login 在 permission.js的whiteLis里不会发生重定向而导致href里丢失?code等参数,从而
      //         可以在login/index.vue里直接通过location或vue route 获取code，state参数，不必在permission.js里获取并保存至store里
      //         ***非常关键***，不加的话默认/ 是等价于/dashboard不在白名单里路由根据permission.js逻辑会发生重定向至/login里此时会丢失?code等参数
      //    可在此 使用未定义的变量 来 debug  // Error in created hook: "ReferenceError: hash is not defined" found in window.opener.location.href = window.location.origin + '?' + hash
      // 3. 关闭 AuthRedirect 子窗口。同时代码逻辑至父窗口 在 permission.js => router.beforeEach 或 login/index.vue 进行 code 处理
      window.close()
    }
  },
  render: function(h) {
    return h() // avoid warning message
  }
}
</script>
