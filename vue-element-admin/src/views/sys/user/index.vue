<template>
  <div class="app-container">
    <div class="filter-container">
      <el-input
        v-model="userValue"
        v-perm="['/sys/user/users/get']"
        placeholder="用户名"
        style="width: 200px;"
        class="filter-item"
      />
      <el-select
        v-model="filters[1].value"
        v-perm="['/sys/user/users/get']"
        clearable
        class="filter-item"
      >
        <el-option label="启用" value="1" />
        <el-option label="禁用" value="0" />
      </el-select>
      <el-button
        v-perm="['/sys/user/users/post']"
        class="filter-item"
        style="margin-left: 10px;"
        type="primary"
        icon="el-icon-plus"
        @click="handleCreate"
      >添加</el-button>
    </div>

    <data-tables-server
      :data="list"
      :search-def="searchDef"
      :total="total"
      :filters="filters"
      :table-props="tableProps"
      :loading="listLoading"
      :page-size="5"
      :pagination-props="{ background: true, pageSizes: [5,10,20] }"
      layout="table,pagination"
      @query-change="fetchData"
    >
      <el-table-column
        v-for="title in titles"
        :key="title.label"
        :prop="title.prop"
        :label="title.label"
        sortable="custom"
      />
      <el-table-column label="状态" min-width="100px">
        <template slot-scope="scope">
          <el-tag
            :type="scope.row.status | statusFilter"
            size="small"
          >{{ scope.row.status | statusChange }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" align="center" min-width="100px">
        <template slot-scope="scope">
          <el-button
            v-perm="['/sys/user/users/put']"
            :size="btnsize"
            type="success"
            @click="handleUpdate(scope.row)"
          >编辑</el-button>
          <el-button
            v-perm="['/sys/user/users/delete']"
            :size="btnsize"
            type="danger"
            @click="handleDelete(scope.row)"
          >删除</el-button>
        </template>
      </el-table-column>
    </data-tables-server>

    <el-dialog :title="textMap[dialogStatus]" :visible.sync="dialogFormVisible" style="width: 70%;">
      <el-form
        ref="dataForm"
        :rules="rules"
        :model="temp"
        label-position
        label-width="90px"
        style="width: 80%; margin-left:50px;"
      >
        <el-form-item label="用户名" prop="username">
          <el-input v-model.trim="temp.username" :readonly="readonly" placeholder="请输入用户名" />
        </el-form-item>
        <el-form-item v-if="dialogStatus==='create'" label="密码" prop="password">
          <el-input v-model="temp.password" :type="passwordType">
            <i slot="suffix" class="el-input__icon el-icon-eye" @click="showPwd">
              <svg-icon :icon-class="passwordType === 'password' ? 'eye' : 'eye-open'" />
            </i>
          </el-input>
        </el-form-item>
        <el-form-item label="Email" prop="email">
          <el-input v-model.trim="temp.email" placeholder="请输入email" />
        </el-form-item>
        <el-form-item label="角色" prop="role">
          <!-- <el-select v-model="temp.role" class="filter-item" multiple="multiple" @remove-tag="removeTag()">
            <el-option v-for="item in roleOptions" :key="item.id" :label="item.name" :value="item.id" :disabled="item.disabled" />
          </el-select>-->
          <treeselect
            ref="treeSelect"
            v-model="temp.role"
            :multiple="true"
            :clearable="false"
            :normalizer="normalizer"
            :options="roleOptions"
            placeholder="请选择角色..."
            @input="roleChange"
          />
        </el-form-item>
        <el-form-item label="部门" prop="dept">
          <treeselect
            v-model="temp.dept"
            :multiple="true"
            :clearable="false"
            :flat="true"
            :normalizer="normalizer"
            :options="deptOptions"
            placeholder="请选择部门..."
          />
        </el-form-item>
        <el-form-item label="排序ID">
          <!-- onkeypress 防止录入e 及其他字符 -->
          <el-input-number
            v-model.trim="temp.listorder"
            :min="0"
            controls-position="right"
            onkeypress="return(/[\d]/.test(String.fromCharCode(event.keyCode)))"
          />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-switch
            v-model="temp.status"
            inactive-color="#ff4949"
            active-value="1"
            inactive-value="0"
          />
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">取消</el-button>
        <el-button
          :loading="updateLoading"
          type="primary"
          @click="dialogStatus==='create'?createData():updateData()"
        >确定</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import waves from '@/directive/waves' // Waves directive
import perm from '@/directive/perm/index.js' // 权限判断指令

import {
  createUser,
  updateUser,
  deleteUser,
  getUserList,
  getRoleOptions,
  getDeptOptions
} from '@/api/user'

// import random from 'string-random'
// import the component
import Treeselect from '@riophae/vue-treeselect'
// import the styles
import '@riophae/vue-treeselect/dist/vue-treeselect.css'
import _ from 'lodash'

export default {
  name: 'SysUser',
  // 所以在编写路由 router 和路由对应的 view component 的时候一定要确保 两者的 name 是完全一致的。
  // register the component Treeselect, TreeTable
  components: { Treeselect },
  directives: { waves, perm },
  filters: {
    statusFilter(status) {
      const statusMap = {
        1: 'success',
        0: 'danger'
      }
      return statusMap[status]
    },
    statusChange(status) {
      const statusMapx = {
        1: '启用',
        0: '禁用'
      }
      return statusMapx[status]
    }
  },
  data() {
    // TODO: 参考login页面 引入校验函数 参考党建app mine-password 页面修改密码强度
    const validatePassword = (rule, value, callback) => {
      if (value.length < 6) {
        callback(new Error('The password can not be less than 6 digits'))
      } else {
        callback()
      }
    }
    const validateRole = (rule, value, callback) => {
      console.log(rule, value)
      if (!value.length) {
        this.$refs.treeSelect.$el.getElementsByClassName(
          'vue-treeselect__control'
        )[0].style.borderColor = 'red'
        callback(new Error('请选择角色'))
      } else {
        this.$refs.treeSelect.$el.getElementsByClassName(
          'vue-treeselect__control'
        )[0].style.borderColor = ''
        callback()
      }
    }

    return {
      userValue: '',
      passwordType: 'password',
      searchDef: {
        show: true,
        debounceTime: 3000
      },
      defaultQueryInfo: '',
      filters: [
        {
          prop: 'username',
          value: ''
        },
        {
          prop: 'status',
          value: ''
        }
      ],
      list: [],
      total: 0,
      listLoading: true,
      updateLoading: false,
      titles: [
        {
          prop: 'id',
          label: 'ID'
        },
        {
          prop: 'username',
          label: '用户名'
        },
        {
          prop: 'email',
          label: 'Email'
        },
        {
          prop: 'listorder',
          label: '排序'
        }
      ],
      tableProps: {
        border: false,
        stripe: true,
        highlightCurrentRow: true,
        defaultSort: {
          prop: 'listorder',
          order: 'ascending'
        }
      },
      // 自定义role treeselect key id,label
      normalizer(node) {
        return {
          id: node.id,
          label: node.name,
          children: node.children
        }
      },
      roleOptions: [],

      // 部门 treeselect
      deptOptions: [],

      btnsize: 'mini',
      dialogFormVisible: false,
      dialogStatus: '',
      readonly: false,
      textMap: {
        update: '编辑',
        create: '新增'
      },
      temp: {
        id: undefined,
        username: '',
        password: '',
        email: '',
        role: [],
        dept: [],
        status: '1',
        listorder: 1000
      },
      rules: {
        username: [
          { required: true, message: '请输入用户名', trigger: 'blur' }
        ],
        password: [
          { required: true, trigger: 'blur', validator: validatePassword }
        ],
        role: [{ required: true, validator: validateRole, trigger: 'blur' }]
        // dept: [{ required: true, message: '请选择部门', trigger: 'blur' }]
      }
    }
  },
  watch: {
    userValue: _.debounce(function(newV, oldV) {
      // console.log(oldV + '=>' + newV)
      // 使用lodash debounce 延迟防抖动，几秒后将变量值 赋值给 data-tables-server 的 filters[0].value
      // data-tables-server watch filters 值发生变化后会立即向后台重新获取数据 等价于 filter值的延迟得到
      this.filters[0].value = this.userValue
    }, 500)
  },
  created() {
    // this.fetchData()
    this.initOptions()
  },
  methods: {
    roleChange() {
      this.$refs['dataForm'].validateField('role')
    },
    removeTag(args) {
      console.log('removeTag...')
      console.log(args)
      return
    },
    // 获取数据
    fetchData(queryInfo) {
      // console.log('queryInfo', queryInfo)
      // 页面调用 fetchData 时会清空queryInfo， 重载该状态值
      // 页面 created 时及翻页时， queryInfo 不会空均会变化， 需要保留此状态，以备fetchData里 queryInfo为空时，重新加载该值
      !queryInfo
        ? (queryInfo = this.defaultQueryInfo)
        : (this.defaultQueryInfo = queryInfo)

      // if (!queryInfo) {
      //   queryInfo = this.defaultQueryInfo
      // }
      // this.defaultQueryInfo = queryInfo

      this.listLoading = true

      // GET /users?offset=1&limit=20&fields=id,username,email,listorder&sort=-listorder,+id&query=~username,status&username=admin&status=1
      // {"type":"init","page":1,"pageSize":5,"sort":{"prop":"listorder","order":"ascending"},"filters":[{"prop":"username","value":""},{"prop":"status","value":""}]}
      // 初始化分页/排序/查询字符串，与后端一致符合 Restful 风格
      const offset = queryInfo.page
      const limit = queryInfo.pageSize
      const sort =
        (queryInfo.sort.order === 'ascending' ? '+' : '-') + queryInfo.sort.prop

      let queryStr = ''
      _.forEach(queryInfo.filters, function(value, key) {
        if (value.value) {
          queryStr += '&' + value.prop + '=' + value.value
        }
      })

      // &query=~username,status 根据业务情况写死
      const queryParms =
        'offset=' +
        offset +
        '&limit=' +
        limit +
        '&sort=' +
        sort +
        '&query=~username,status' +
        queryStr
      // console.log(queryParms)

      getUserList(queryParms).then(res => {
        console.log('getUserList', res)
        this.list = res.data.items
        this.total = res.data.total
        this.listLoading = false
      })
    },

    // 初始化角色树，部门树选项
    initOptions() {
      getRoleOptions()
        .then(res => {
          console.log('getRoleOptions', res)
          this.roleOptions = res.data
        })
        .catch(() => {})

      getDeptOptions()
        .then(res => {
          console.log('getDeptOptions', res)
          this.deptOptions = res.data
        })
        .catch(() => {})
    },

    resetTemp() {
      this.temp = {
        id: undefined,
        username: '',
        password: '',
        email: '',
        role: [],
        dept: [],
        status: '1',
        listorder: 1000
      }
      this.readonly = false
    },
    handleCreate() {
      console.log('handleCreate...click')
      this.resetTemp()
      this.dialogStatus = 'create'
      this.dialogFormVisible = true
      this.$nextTick(() => {
        // 进入dialog时清空所有校验状态css
        this.$refs['dataForm'].clearValidate()
        this.$refs.treeSelect.$el.getElementsByClassName(
          'vue-treeselect__control'
        )[0].style.borderColor = ''
      })
    },
    createData() {
      this.$refs['dataForm'].validate(valid => {
        if (valid) {
          console.log('createData valid done...', this.temp)

          // 调用api创建数据入库
          this.updateLoading = true
          createUser(this.temp)
            .then(res => {
              // 成功后 关闭窗口
              this.updateLoading = false
              console.log('createUser...', res)
              this.fetchData()
              this.dialogFormVisible = false
              this.$notify({
                message: res.message,
                type: res.type
              })
            })
            .catch(err => {
              console.log(err)
              this.updateLoading = true
            })
        }
      })
    },
    handleUpdate(row) {
      this.temp = Object.assign({}, row) // copy obj
      console.log(this.temp)
      this.readonly = true // 用户名不能修改, 只能删除

      this.dialogStatus = 'update'
      this.dialogFormVisible = true
      this.$nextTick(() => {
        this.$refs['dataForm'].clearValidate()
      })
    },
    updateData() {
      this.$refs['dataForm'].validate(valid => {
        if (valid) {
          // 调用api编辑数据入库
          this.updateLoading = true
          updateUser(this.temp)
            .then(res => {
              this.updateLoading = false
              if (res.type === 'success') {
                // 后台重新更新数据
                this.fetchData()
                // this.$refs.TreeTable.updateTreeNode(this.temp) // 只能更新自身以下的节点
                this.dialogFormVisible = false
              }
              this.$notify({
                //  title: '错误',
                message: res.message,
                type: res.type
              })
            })
            .catch(err => {
              console.log(err)
              this.updateLoading = true
            })
        }
      })
    },
    handleDelete(row) {
      const h = this.$createElement
      this.$msgbox({
        title: '提示',
        message: h('p', null, [
          h('span', null, '确认删除选中记录吗？[用户:  '),
          h('i', { style: 'color: teal' }, row.username),
          h('span', null, ' ]')
        ]),
        showCancelButton: true,
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        beforeClose: (action, instance, done) => {
          if (action === 'confirm') {
            instance.confirmButtonLoading = true

            // 调用api删除数据
            deleteUser(row.id)
              .then(res => {
                // 如果删除成功，后台重新更新数据,否则不更新数据
                done()
                instance.confirmButtonLoading = false
                if (res.type === 'success') {
                  this.fetchData()
                }
                this.$notify({
                  //  title: '错误',
                  message: '用户: ' + row.username + ' ' + res.message,
                  type: res.type
                })
              })
              .catch(err => {
                console.log(err)
                instance.confirmButtonLoading = false
              })
          } else {
            done()
            console.log('click cancel.....')
            instance.confirmButtonLoading = false
          }
        }
        // }).then(action => {
      })
        .then(() => {
          // this.$message({
          //   type: 'info',
          //   message: 'action: ' + action // confirm
          // })
        })
        .catch(() => {
          // console.log(err)  // cancel
        })
    },
    handleFilter() {
      this.listQuery.page = 1
      // this.getList()
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
