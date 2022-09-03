<template>
  <div class="social-signup-container">
    <div class="sign-btn" @click="wechatHandleClick('wechat')">
      <span class="wx-svg-container"><svg-icon icon-class="wechat" class="icon" /></span>
      WeChat
    </div>
    <div class="sign-btn" @click="tencentHandleClick('tencent')">
      <span class="qq-svg-container"><svg-icon icon-class="qq" class="icon" /></span>
      QQ
    </div>
    <div class="sign-btn" @click="githubHandleClick('github')">
      <span class="github-svg-container">
        <svg-icon icon-class="github" class="icon" />
      </span> github
    </div>
    <div class="sign-btn" @click="giteeHandleClick('gitee')">
      <span class="gitee-svg-container">
        <svg-icon icon-class="gitee-white" class="icon" />
      </span> 码云
    </div>
  </div>
</template>

<script>
import openWindow from '@/utils/open-window'
import { githubAuth, giteeAuth } from '@/api/login'

export default {
  name: 'SocialSignin',
  methods: {
    wechatHandleClick(thirdpart) {
      alert('ok')
      // this.$store.commit('SET_AUTH_TYPE', thirdpart)
      // const appid = 'xxxxx'
      // const redirect_uri = encodeURIComponent('xxx/redirect?redirect=' + window.location.origin + '/auth-redirect')
      // const url = 'https://open.weixin.qq.com/connect/qrconnect?appid=' + appid + '&redirect_uri=' + redirect_uri + '&response_type=code&scope=snsapi_login#wechat_redirect'
      // openWindow(url, thirdpart, 540, 540)
    },
    tencentHandleClick(thirdpart) {
      alert('ok')
      // this.$store.commit('SET_AUTH_TYPE', thirdpart)
      // const client_id = 'xxxxx'
      // const redirect_uri = encodeURIComponent('xxx/redirect?redirect=' + window.location.origin + '/auth-redirect')
      // const url = 'https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id=' + client_id + '&redirect_uri=' + redirect_uri
      // openWindow(url, thirdpart, 540, 540)
    },
    githubHandleClick(thirdpart) {
      // 1. 指定授权 client_id 及 redirect_uri 的 URL
      //    如果不指定 redirect_uri, 则默认使用 gihtub => Settings => Developer settings =>  OAuth Apps 里 Authorization callback URL 配置的地址
      //    为了无歧义尽量在程序代码里指定redirect_uri
      // const url = 'https://github.com/login/oauth/authorize?client_id=94aae05609c96ffb7d3b&redirect_uri=http://localhost:9527/auth-redirect'
      githubAuth().then(response => { // githubAuth() 参数 code 为空，由后端返回 authorize_url
        // 2. 弹出子窗口进行授权, 子窗口完成授权后, 子窗口地址栏URL 会是 redirect_uri 并带上 ?code= 参数与&state= 参数
        //  子窗口单击确定按钮授权时，会校验此时的redirect 与 创建三方应用时填写的 callback 不一致会出错
        const url = response.data.auth_url
        // console.log('auth url....', url)
        // =>  https://github.com/login/oauth/authorize?state=137caabc2b409f0cccd14834fc848041&response_type=code&approval_prompt=auto&redirect_uri=http://localhost:9527/auth-redirect&client_id=94aae05609c96ffb7d3b
        openWindow(url, thirdpart, 540, 540)
      }).catch(error => {
        console.log(error)
      })
    },
    giteeHandleClick(thirdpart) {
      // 1. 指定授权 client_id 及 redirect_uri 的 URL
      //    如果不指定 redirect_uri, 则默认使用 gihtub => Settings => Developer settings =>  OAuth Apps 里 Authorization callback URL 配置的地址
      //    为了无歧义尽量在程序代码里指定redirect_uri
      // const url = 'https://github.com/login/oauth/authorize?client_id=94aae05609c96ffb7d3b&redirect_uri=http://localhost:9527/auth-redirect'
      giteeAuth().then(response => { // githubAuth() 参数 code 为空，由后端返回 authorize_url
        // 2. 弹出子窗口进行授权, 子窗口完成授权后, 子窗口地址栏URL 会是 redirect_uri 并带上 ?code= 参数与&state= 参数
        const url = response.data.auth_url
        // console.log('auth url....', url)
        // =>  https://github.com/login/oauth/authorize?state=137caabc2b409f0cccd14834fc848041&response_type=code&approval_prompt=auto&redirect_uri=http://localhost:9527/auth-redirect&client_id=94aae05609c96ffb7d3b
        openWindow(url, thirdpart, 1024, 800)
      }).catch(error => {
        console.log(error)
      })
    }
  }
}
</script>

<style lang="scss" scoped>
  .social-signup-container {
    margin: 20px 0;
    .sign-btn {
      display: inline-block;
      cursor: pointer;
    }
    .icon {
      color: #fff;
      font-size: 24px;
      margin-top: 8px;
    }
    .wx-svg-container,
    .qq-svg-container {
      display: inline-block;
      width: 40px;
      height: 40px;
      line-height: 40px;
      text-align: center;
      padding-top: 1px;
      border-radius: 4px;
      margin-bottom: 20px;
      margin-right: 5px;
    }
    .wx-svg-container {
      background-color: #24da70;
    }
    .qq-svg-container {
      background-color: #6BA2D6;
      margin-left: 50px;
    }
    .github-svg-container {
      display: inline-block;
      width: 40px;
      height: 40px;
      line-height: 40px;
      text-align: center;
      padding-top: 1px;
      border-radius: 4px;
      margin-bottom: 20px;
      margin-right: 5px;
      background-color: #69747e;
      margin-left: 50px;
    }
    .gitee-svg-container {
      display: inline-block;
      width: 40px;
      height: 40px;
      line-height: 40px;
      text-align: center;
      padding-top: 1px;
      border-radius: 4px;
      margin-bottom: 20px;
      margin-right: 5px;
      background-color: #69747e;
      margin-left: 50px;
    }
  }
</style>
