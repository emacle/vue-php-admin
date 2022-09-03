<template>
  <el-form
    ref="dataForm"
    :rules="rules"
    :model="temp"
    label-position
    label-width="90px"
    style="width:40%; margin-left:5px;"
  >
    <el-form-item label="原密码" prop="password_orig">
      <el-input v-model.trim="temp.password_orig" :type="passwordType">
        <i slot="suffix" class="el-input__icon el-icon-eye" @click="showPwd">
          <svg-icon :icon-class="passwordType === 'password' ? 'eye' : 'eye-open'" />
        </i>
      </el-input>
    </el-form-item>
    <el-form-item label="新密码" prop="password">
      <el-input v-model.trim="temp.password" :type="passwordType">
        <i slot="suffix" class="el-input__icon el-icon-eye" @click="showPwd">
          <svg-icon :icon-class="passwordType === 'password' ? 'eye' : 'eye-open'" />
        </i>
      </el-input>
    </el-form-item>
    <el-form-item label="确认密码" prop="password_confirmation">
      <el-input v-model.trim="temp.password_confirmation" :type="passwordType">
        <i slot="suffix" class="el-input__icon el-icon-eye" @click="showPwd">
          <svg-icon :icon-class="passwordType === 'password' ? 'eye' : 'eye-open'" />
        </i>
      </el-input>
    </el-form-item>
    <el-form-item>
      <el-button :loading="updateLoading" type="primary" @click="submit">修改密码</el-button>
    </el-form-item>
  </el-form>
</template>

<script>
import { updatePassword } from '@/api/user'
// updatePassword

export default {
  props: {
    user: {
      type: Object,
      default: () => {
        return {
          name: '',
          email: ''
        }
      }
    }
  },
  data() {
    const validatePassword = (rule, value, callback) => {
      if (!value) {
        return callback(new Error('请输入密码'))
      } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[^]{8,}$/.test(value)) {
        callback(new Error('密码至少包含大写字母，小写字母和数字，且不少于8位'))
      } else {
        callback()
      }
    }
    const validateRePassword = (rule, value, callback) => {
      if (!value) {
        return callback(new Error('请再次输入密码'))
      } else if (value !== this.temp.password) {
        callback(new Error('两次密码输入不一致'))
      } else {
        callback()
      }
    }

    return {
      temp: {
        username: '',
        password_orig: '',
        password: '',
        password_confirmation: ''
      },
      passwordType: 'password',
      rules: {
        password_orig: [
          { required: true, message: '请输入原密码', trigger: 'blur' }
        ],
        password: [
          { required: true, trigger: 'blur', validator: validatePassword }
        ],
        password_confirmation: [
          { required: true, trigger: 'blur', validator: validateRePassword }
        ]
      },
      updateLoading: false
    }
  },
  methods: {
    submit() {
      // updatePassword
      this.$refs['dataForm'].validate(valid => {
        if (valid) {
          this.temp.username = this.user.name
          // 调用api编辑数据入库
          this.updateLoading = true
          updatePassword(this.temp)
            .then(res => {
              this.updateLoading = false
              // TODO: 过滤后端返回res.message的regexp规则修改成易于理解的方式，password must validate against
              this.$message({
                message: res.message,
                type: res.type,
                duration: 5 * 1000
              })
              if (res.type === 'success') {
                this.temp.password = ''
                this.temp.password_orig = ''
                this.temp.password_confirmation = ''
              }
            })
            .catch(err => {
              console.log(err)
              this.updateLoading = false
            })
        }
      })
    },
    showPwd() {
      if (this.passwordType === 'password') {
        this.passwordType = ''
      } else {
        this.passwordType = 'password'
      }
    }
  }
}
</script>
