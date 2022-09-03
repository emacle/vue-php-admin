<template>
  <div class="login-container">
    <el-form
      ref="loginForm"
      :model="loginForm"
      :rules="loginRules"
      class="login-form"
      autocomplete="on"
      label-position="left"
    >
      <div class="title-container">
        <h3 class="title">系统登录</h3>
      </div>

      <el-form-item prop="username">
        <span class="svg-container">
          <svg-icon icon-class="user" />
        </span>
        <el-input
          ref="username"
          v-model="loginForm.username"
          placeholder="Username"
          name="username"
          type="text"
          tabindex="1"
          autocomplete="on"
        />
      </el-form-item>

      <el-tooltip v-model="capsTooltip" content="Caps lock is On" placement="right" manual>
        <el-form-item prop="password">
          <span class="svg-container">
            <svg-icon icon-class="password" />
          </span>
          <el-input
            :key="passwordType"
            ref="password"
            v-model="loginForm.password"
            :type="passwordType"
            placeholder="Password"
            name="password"
            tabindex="2"
            autocomplete="on"
            @keyup.native="checkCapslock"
            @blur="capsTooltip = false"
            @keyup.enter.native="handleLogin"
          />
          <span class="show-pwd" @click="showPwd">
            <svg-icon :icon-class="passwordType === 'password' ? 'eye' : 'eye-open'" />
          </span>
        </el-form-item>
      </el-tooltip>

      <el-button
        :loading="loading"
        type="primary"
        style="width:100%;margin-bottom:30px;"
        @click.native.prevent="handleLogin"
      >登录</el-button>

      <div style="position:relative">
        <div class="tips">
          <span>超级账号 : admin</span>
          <span>密码 : admin</span>
        </div>
        <div class="tips">
          <span style="margin-right:18px;">普通账号 : editor</span>
          <span>密码 : editor</span>
        </div>

        <el-button class="thirdparty-button" type="primary" @click="showDialog=true">第三方登录</el-button>
        <!-- 测试恢复数据库使用!!!慎用!!! -->
        <!-- <el-button v-if="dbrestoreFlag" :loading="rloading" type="danger" @click="dbrestore">
          恢复数据库
        </el-button>-->
      </div>
    </el-form>

    <el-dialog title="第三方登录" :visible.sync="showDialog">
      本地不能模拟，请结合自己业务进行模拟！！！
      <br>
      <br>
      <br>
      <social-sign />
    </el-dialog>
  </div>
</template>

<script>
import SocialSign from './components/SocialSignin'
import { restoreDB } from '@/api/log'

export default {
  name: 'Login',
  components: { SocialSign },
  data() {
    const validatePassword = (rule, value, callback) => {
      if (value.length < 5) {
        callback(new Error('The password can not be less than 5 digits'))
      } else {
        callback()
      }
    }
    return {
      loginForm: {
        username: 'admin',
        password: 'admin'
      },
      loginRules: {
        username: [{ required: true, trigger: 'blur' }],
        password: [
          { required: true, trigger: 'blur', validator: validatePassword }
        ]
      },
      passwordType: 'password',
      capsTooltip: false,
      loading: false,
      showDialog: false,
      redirect: undefined,
      otherQuery: {},
      dbrestoreFlag: false,
      rloading: false
    }
  },
  watch: {
    $route: {
      handler: function(route) {
        const query = route.query
        if (query) {
          this.redirect = query.redirect
          this.otherQuery = this.getOtherQuery(query)
        }
      },
      immediate: true
    }
  },
  created() {
    // window.addEventListener('storage', this.afterQRScan)
    this.LoginByThirdparty()
    if (process.env.NODE_ENV === 'production') {
      this.dbrestoreFlag = true
    }
  },
  mounted() {
    if (this.loginForm.username === '') {
      this.$refs.username.focus()
    } else if (this.loginForm.password === '') {
      this.$refs.password.focus()
    }
  },
  destroyed() {
    // window.removeEventListener('storage', this.afterQRScan)
  },
  methods: {
    LoginByThirdparty() {
      // console.log('in login/index.vue....', window.location, this.$route, this.$router)
      // 获取三方登录 code
      // 更可靠稳定的获取code方法 使用 vue router to 对象来获取
      // 如果路由存在code 与 state 参数，如http://localhost:9527/login?code=8789d613d1fa9a19732a&state=xyz
      if (
        Object.prototype.hasOwnProperty.call(this.$route.query, 'code') &&
        Object.prototype.hasOwnProperty.call(this.$route.query, 'state')
      ) {
        // this.$route.query 如果存在 code 则为三方登录则写入store 变量
        const code = this.$route.query.code
        const state = this.$route.query.state
        const auth_type = this.$route.query.auth_type
        console.log(
          'thirdLogin code, state, auth_type: ',
          code,
          state,
          auth_type
        )

        // store.state.user.code = code
        // store.state.user.code_state = state
        // console.log(store.state.user) // 该code 在store/modules/user.js 里定义有 作为第三方登录使用 参见其中 LoginByThirdparty

        this.thirdLogin = true
        const loading = this.$loading({
          lock: true,
          text: '三方认证登录中...',
          spinner: 'el-icon-loading',
          background: 'rgba(0, 0, 0, 0.7)'
        })

        const authParms = { code, state, auth_type }
        // 执行 GET githubAuth 根据 code 获取 github userinfo 结合业务逻辑生成 token / refreshtoken (jwt)
        this.$store
          .dispatch('user/LoginByThirdparty', authParms)
          .then(() => {
            this.$router.push({ path: '/' })
            loading.close()
          })
          .catch(err => {
            console.log(
              'this.$store.dispatchLoginByThirdparty catch err...',
              err.response
            )
            this.thirdLogin = false
            loading.close()
          })
          .finally(e => {
            console.log('this.$store.dispatchLoginByThirdparty finally....', e)
            this.thirdLogin = false
            loading.close()
          })
      }
    },
    checkCapslock(e) {
      const { key } = e
      this.capsTooltip = key && key.length === 1 && key >= 'A' && key <= 'Z'
    },
    showPwd() {
      if (this.passwordType === 'password') {
        this.passwordType = ''
      } else {
        this.passwordType = 'password'
      }
      this.$nextTick(() => {
        this.$refs.password.focus()
      })
    },
    handleLogin() {
      this.$refs.loginForm.validate(valid => {
        if (valid) {
          this.loading = true
          this.$store
            .dispatch('user/LoginByUsername', this.loginForm)
            .then(() => {
              this.$router.push({
                path: this.redirect || '/',
                query: this.otherQuery
              })
              this.loading = false
            })
            .catch(() => {
              this.loading = false
            })
        } else {
          console.log('error submit!!')
          return false
        }
      })
    },
    getOtherQuery(query) {
      return Object.keys(query).reduce((acc, cur) => {
        if (cur !== 'redirect') {
          acc[cur] = query[cur]
        }
        return acc
      }, {})
    },
    dbrestore() {
      this.rloading = true
      restoreDB()
        .then(res => {
          console.log('restoreDB', res)
          this.$message({
            message: res.message,
            type: 'success'
          })
          this.rloading = false
        })
        .catch(() => {
          this.rloading = false
        })
    }
    // afterQRScan() {
    //   if (e.key === 'x-admin-oauth-code') {
    //     const code = getQueryObject(e.newValue)
    //     const codeMap = {
    //       wechat: 'code',
    //       tencent: 'code'
    //     }
    //     const type = codeMap[this.auth_type]
    //     const codeName = code[type]
    //     if (codeName) {
    //       this.$store.dispatch('LoginByThirdparty', codeName).then(() => {
    //         this.$router.push({ path: this.redirect || '/' })
    //       })
    //     } else {
    //       alert('第三方登录失败')
    //     }
    //   }
    // }
  }
}
</script>

<style lang="scss">
/* 修复input 背景不协调 和光标变色 */
/* Detail see https://github.com/PanJiaChen/vue-element-admin/pull/927 */

$bg: #283443;
$light_gray: #fff;
$cursor: #fff;

@supports (-webkit-mask: none) and (not (cater-color: $cursor)) {
  .login-container .el-input input {
    color: $cursor;
  }
}

/* reset element-ui css */
.login-container {
  .el-input {
    display: inline-block;
    height: 47px;
    width: 85%;

    input {
      background: transparent;
      border: 0px;
      -webkit-appearance: none;
      border-radius: 0px;
      padding: 12px 5px 12px 15px;
      color: $light_gray;
      height: 47px;
      caret-color: $cursor;

      &:-webkit-autofill {
        box-shadow: 0 0 0px 1000px $bg inset !important;
        -webkit-text-fill-color: $cursor !important;
      }
    }
  }

  .el-form-item {
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.1);
    border-radius: 5px;
    color: #454545;
  }
}
</style>

<style lang="scss" scoped>
$bg: #2d3a4b;
$dark_gray: #889aa4;
$light_gray: #eee;

.login-container {
  min-height: 100%;
  width: 100%;
  background-color: $bg;
  overflow: hidden;

  .login-form {
    position: relative;
    width: 520px;
    max-width: 100%;
    padding: 160px 35px 0;
    margin: 0 auto;
    overflow: hidden;
  }

  .tips {
    font-size: 14px;
    color: #fff;
    margin-bottom: 10px;

    span {
      &:first-of-type {
        margin-right: 16px;
      }
    }
  }

  .svg-container {
    padding: 6px 5px 6px 15px;
    color: $dark_gray;
    vertical-align: middle;
    width: 30px;
    display: inline-block;
  }

  .title-container {
    position: relative;

    .title {
      font-size: 26px;
      color: $light_gray;
      margin: 0px auto 40px auto;
      text-align: center;
      font-weight: bold;
    }
  }

  .show-pwd {
    position: absolute;
    right: 10px;
    top: 7px;
    font-size: 16px;
    color: $dark_gray;
    cursor: pointer;
    user-select: none;
  }

  .thirdparty-button {
    position: absolute;
    right: 0;
    bottom: 6px;
  }

  @media only screen and (max-width: 470px) {
    .thirdparty-button {
      display: none;
    }
  }
}
</style>
