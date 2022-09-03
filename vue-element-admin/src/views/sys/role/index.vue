<template>
  <div class="app-container">
    <div class="filter-container">
      <el-input
        v-model="filters[0].value"
        placeholder="角色名"
        style="width: 200px"
        class="filter-item"
      />
      <el-select
        v-model="filters[1].value"
        class="filter-item"
        multiple="multiple"
      >
        <el-option label="启用" value="1" />
        <el-option label="禁用" value="0" />
      </el-select>
      <el-button
        v-waves
        v-perm="['/sys/role/roles/post']"
        class="filter-item"
        style="margin-left: 10px"
        type="primary"
        icon="el-icon-plus"
        @click="handleCreate"
      >添加</el-button>
    </div>

    <data-tables
      :data="list"
      :filters="filters"
      :loading="listLoading"
      :table-props="tableProps"
      :pagination-props="{ pageSizes: [5, 10, 15, 20] }"
      layout="table,pagination"
      @current-change="handleRoleSelectChange"
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
          <el-tag :type="scope.row.status | statusFilter" size="small">{{
            scope.row.status | statusChange
          }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" align="center" min-width="100px">
        <template slot-scope="scope">
          <el-button
            v-perm="['/sys/role/roles/put']"
            :size="btnsize"
            type="success"
            @click="handleUpdate(scope.row)"
          >编辑</el-button>
          <el-button
            v-perm="['/sys/role/roles/delete']"
            :size="btnsize"
            type="danger"
            @click="handleDelete(scope.row)"
          >删除</el-button>
        </template>
      </el-table-column>
    </data-tables>

    <el-dialog :title="textMap[dialogStatus]" :visible.sync="dialogFormVisible">
      <el-form
        ref="dataForm"
        :rules="rules"
        :model="temp"
        label-position
        label-width="90px"
        style="width: 400px; margin-left: 50px"
      >
        <el-form-item label="角色名称" prop="name">
          <el-input v-model.trim="temp.name" placeholder="请输入角色名" />
        </el-form-item>
        <el-form-item label="角色说明" prop="remark">
          <el-input v-model.trim="temp.remark" placeholder="请输入角色备注" />
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
          @click="dialogStatus === 'create' ? createData() : updateData()"
        >确定</el-button>
      </div>
    </el-dialog>

    <!--角色菜单，表格树内容栏-->
    <div class="menu-container">
      <div class="menu-header">
        <span>
          <h2>
            角色授权
            <span
              v-if="selectRole.id != null"
              class="menu-role"
            >: {{ selectRole.name }}</span>
          </h2>
        </span>
      </div>
      <el-tabs
        v-model="activeName"
        tab-position="top"
        type="card"
        @tab-click="handleTabClick"
      >
        <el-tab-pane label="菜单类" name="menu">
          <el-tree
            ref="menuTree"
            v-loading="menuLoading"
            :data="menuData"
            :props="defaultProps"
            :render-content="renderContent"
            :check-strictly="true"
            show-checkbox
            node-key="id"
            size="mini"
            style="width: 100%; pading-top: 20px"
            element-loading-text="拼命加载中"
            @check-change="handleMenuCheckChange"
          />
        </el-tab-pane>
        <el-tab-pane label="角色类" name="role">
          <data-tables
            ref="roleTable"
            :data="roleData"
            :table-props="tableProps"
            :loading="roleLoading"
            :pagination-props="{ pageSizes: [10, 20] }"
          >
            <el-table-column type="selection" width="55" />
            <el-table-column
              v-for="title in rtitles"
              :key="title.label"
              :prop="title.prop"
              :label="title.label"
              sortable="custom"
            />
            <el-table-column label="状态" min-width="100px">
              <template slot-scope="scope">
                <el-tag :type="scope.row.status | statusFilter" size="small">{{
                  scope.row.status | statusChange
                }}</el-tag>
              </template>
            </el-table-column>
          </data-tables>
        </el-tab-pane>
        <el-tab-pane label="数据权限" name="dept">
          <el-row :gutter="0">
            <el-form size="medium" label-width="100px">
              <el-col :span="16">
                <el-form-item label="权限范围" prop="dataPermScope">
                  <el-select v-model="dataPermScope" class="filter-item">
                    <el-option
                      v-for="item in dataPermOption"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col v-show="dataPermScope == 4" :span="12">
                <el-form-item label="部门数据" prop="dept">
                  <el-tree
                    ref="deptTree"
                    v-loading="deptLoading"
                    :data="deptData"
                    :props="defaultDeptProps"
                    :check-strictly="!checkJianlian"
                    show-checkbox
                    node-key="id"
                    size="mini"
                    style="width: 100%; pading-top: 20px"
                    element-loading-text="拼命加载中"
                  />
                  <!-- @check-change="handleDeptCheckChange" -->
                </el-form-item>
              </el-col>
              <el-col v-show="dataPermScope == 4" :span="16">
                <el-form-item label="勾选级联树" prop="jilian">
                  <p-check
                    v-model="checkJianlian"
                    class="p-default p-curve"
                    color="primary"
                    :disabled="selectRole.id == null"
                    toggle
                  >
                    <i slot="extra" />
                    级联
                    <i slot="off-extra" />
                    <label slot="off-label">不级联</label>
                  </p-check>
                  <!-- <el-checkbox v-model="checkJianlian" :disabled="selectRole.id == null" /> -->
                </el-form-item>
              </el-col>
            </el-form>
          </el-row>
        </el-tab-pane>

        <el-tab-pane label="文件类" name="file">todo:文件类授权</el-tab-pane>

        <div
          style="
            float: left;
            padding-left: 24px;
            padding-top: 12px;
            padding-bottom: 4px;
          "
        >
          <!-- <el-checkbox
            v-if="activeName==='menu'"
            v-model="checkAll"
            :disabled="selectRole.id == null"
            @change="handleCheckAll"
          >
            <b>全选</b>
          </el-checkbox>-->
          <p-check
            v-if="activeName === 'menu'"
            v-model="checkAll"
            class="p-icon p-round p-jelly"
            color="primary"
            :disabled="selectRole.id == null"
            @change="handleCheckAll"
          >
            <i slot="extra" class="icon mdi mdi-check" />
            全选
          </p-check>
        </div>
        <div
          style="
            float: right;
            padding-right: 15px;
            padding-top: 4px;
            padding-bottom: 4px;
          "
        >
          <el-button
            v-perm="['/sys/role/roles/put']"
            v-waves
            :disabled="selectRole.id == null"
            :size="btnsize"
            type="primary"
            @click="resetSelection"
          >重置</el-button>
          <el-button
            v-perm="['/sys/role/saveroleperm/post']"
            v-waves
            :loading="authLoading"
            :disabled="selectRole.id == null"
            :size="btnsize"
            type="primary"
            @click="submitAuthForm"
          >提交</el-button>
        </div>
      </el-tabs>
    </div>
  </div>
</template>

<script>
import waves from '@/directive/waves' // Waves directive
import perm from '@/directive/perm/index.js' // 权限判断指令
// import the Treeselect component and styles
// import Treeselect from '@riophae/vue-treeselect'
// import '@riophae/vue-treeselect/dist/vue-treeselect.css'

import 'pretty-checkbox/src/pretty-checkbox.scss'
import '@mdi/font/scss/materialdesignicons.scss'
import PrettyCheck from 'pretty-checkbox-vue/check'

import {
  createRole,
  updateRole,
  deleteRole,
  getRoleList,
  getAllMenus,
  getAllRoles,
  getAllDepts,
  getRoleMenu,
  getRoleRole,
  getRoleDept,
  saveRolePerms
} from '@/api/role'

// import random from 'string-random'

export default {
  name: 'SysRole',
  // 所以在编写路由 router 和路由对应的 view component 的时候一定要确保 两者的 name 是完全一致的。
  // register the component Treeselect, TreeTable
  components: { 'p-check': PrettyCheck },
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
    return {
      checkJianlian: false,
      dataPermShow: false,
      dataPermScope: '',
      dataPermOption: [
        {
          value: '0',
          label: '全部数据权限'
        },
        {
          value: '1',
          label: '部门数据权限'
        },
        {
          value: '2',
          label: '部门及以下数据权限'
        },
        {
          value: '3',
          label: '仅本人数据权限'
        },
        {
          value: '4',
          label: '自定数据权限'
        }
      ],
      filters: [
        {
          prop: 'name',
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
      listQuery: {
        page: 1,
        limit: 10
      },
      titles: [
        {
          prop: 'id',
          label: 'ID'
        },
        {
          prop: 'name',
          label: '角色名称'
        },
        {
          prop: 'remark',
          label: '说明'
        },
        // {
        //   prop: 'status',
        //   label: '状态'
        // },
        {
          prop: 'listorder',
          label: '排序'
        }
      ],
      rtitles: [
        // {
        //   prop: 'perm_id',
        //   label: '权限ID'
        // },
        {
          prop: 'id',
          label: 'ID'
        },
        {
          prop: 'name',
          label: '角色名称'
        },
        {
          prop: 'remark',
          label: '说明'
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
      selectRole: {},
      menuData: [],
      menuLoading: false,
      roleData: [],
      roleLoading: false,
      deptData: [],
      deptLoading: false,
      authLoading: false,
      updateLoading: false,
      checkAll: false,
      currentRoleMenus: [], // 服务端获取当前角色的菜单类权限
      currentRoleRoles: [], // 服务端获取当前角色的角色类权限
      currentRoleDepts: [], // 服务端获取当前角色的角色类权限
      defaultProps: {
        children: 'children',
        label: 'title'
      },
      defaultDeptProps: {
        children: 'children',
        label: 'name'
      },
      tabMapOptions: [
        { label: '菜单类', key: 'menu' },
        { label: '角色类', key: 'role' },
        { label: '数据权限', key: 'dept' },
        { label: '文件类', key: 'file' }
      ],
      activeName: 'menu',

      // 'href': windows.location.href,
      // path: this.$route.path,
      // params: this.$route.params,
      btnsize: 'mini',
      dialogFormVisible: false,
      dialogStatus: '',
      textMap: {
        update: '编辑',
        create: '新增'
      },
      temp: {
        id: undefined,
        name: '',
        remark: '',
        status: '1',
        listorder: 99
      },
      rules: {
        name: [{ required: true, message: '请输入角色名', trigger: 'blur' }]
      }
    }
  },

  created() {
    this.fetchData()
  },
  methods: {
    // 获取数据
    fetchData() {
      this.listLoading = true
      this.menuLoading = true
      this.roleLoading = true
      this.deptLoading = true
      getRoleList()
        .then(res => {
          // console.log('getRoleList', res)
          this.list = res.data.items
          this.total = res.data.total // 可不用 total 由于此处角色数量不会超过500条，使用data-tables 数据为全部数据，不需要使用 total属性
          // data-tables 组件的 data 属性代表着所有的数据，pagination 上显示的所有的 total 值就等于 data.length，
          // 所以使用 data-tables 组件的时候并不需要传入 total 属性; 而 data-tables-server 的 data 属性只是当前页的数据，
          // total 属性代表着全部的数据量， 他们都需要使用的时候来传入。
          this.listLoading = false
        })
        .catch(() => {
          // console.log(err)
        })

      // 菜单类权限列表
      getAllMenus()
        .then(res => {
          // console.log('getAllMenus', res)
          this.menuData = res.data
          this.menuLoading = false
        })
        .catch(() => {
          // console.log(err)
        })
      // 角色类权限列表
      getAllRoles()
        .then(res => {
          this.roleData = res.data.items
          this.roleLoading = false
        })
        .catch(() => {
          // console.log(err)
        })
      // 部门类权限列表
      getAllDepts()
        .then(res => {
          this.deptData = res.data
          this.deptLoading = false
        })
        .catch(() => {
          // console.log(err)
        })
    },
    handleTabClick(tab, event) {
      // console.log(tab, event);
    },
    // 角色选择改变监听
    handleRoleSelectChange(val) {
      if (val === null || val.id === null) {
        this.selectRole = {}
        this.dataPermScope = ''
        this.checkJianlian = false // 保存权限提交后重置此值
        return
      }

      this.selectRole = val
      this.dataPermScope = this.selectRole.scope

      this.menuLoading = true
      this.roleLoading = true
      this.deptLoading = true

      getRoleMenu({ roleId: this.selectRole.id })
        .then(res => {
          // console.log('getRoleRole res', res)
          this.currentRoleMenus = res.data
          this.$refs.menuTree.setCheckedNodes(res.data)
          this.menuLoading = false
        })
        .catch(() => { })

      getRoleRole({ roleId: this.selectRole.id })
        .then(res => {
          console.log('getRoleRole res', res)
          this.roleLoading = false
          this.currentRoleRoles = res.data
          // console.log('currentRoleRoles', this.currentRoleRoles)
          this.$refs.roleTable.$refs.elTable.clearSelection()
          for (let i = 0; i < this.currentRoleRoles.length; i++) {
            for (let index = 0; index < this.roleData.length; index++) {
              if (
                this.currentRoleRoles[i].perm_id ===
                this.roleData[index].perm_id
              ) {
                // 服务端返回需选中项的id
                this.$refs.roleTable.$refs.elTable.toggleRowSelection(
                  this.roleData[index],
                  true
                ) // row.ndex 选中
              }
            }
          }
        })
        .catch(() => { })

      getRoleDept({ roleId: this.selectRole.id })
        .then(res => {
          // console.log('getRoleRole res', res)
          this.deptLoading = false
          this.checkJianlian = false // 置初始值时，不能关联子树，因为拥有的部门数据类权限是不规则的树结构
          this.currentRoleDepts = res.data
          this.$refs.deptTree.setCheckedNodes(res.data)
        })
        .catch(() => { })
    },
    // 树节点选择监听
    handleMenuCheckChange(data, check, subCheck) {
      if (check) {
        // 节点选中时同步选中父节点
        const parentId = data.pid
        this.$refs.menuTree.setChecked(parentId, true, false)
      } else {
        // 节点取消选中时同步取消选中子节点
        if (data.children != null) {
          data.children.forEach(element => {
            this.$refs.menuTree.setChecked(element.id, false, false)
          })
        }
      }
    },
    // 部门树节点选择监听
    handleDeptCheckChange(data, check, subCheck) {
      if (check) {
        // 节点选中时同步选中父节点
        const parentId = data.pid
        this.$refs.deptTree.setChecked(parentId, true, false)
      } else {
        // this.$refs.deptTree.setChecked(parentId, true, false)
        // 节点取消选中时同步取消选中子节点
        // if (data.children != null) {
        //   data.children.forEach(element => {
        //     this.$refs.deptTree.setChecked(element.id, false, false)
        //   })
        // }
      }
    },
    // 重置选择
    async resetSelection() {
      // 因为部门数据权限 父子关联时，不能直接重置，必须异步执行等待
      await this.resetcheckJianlian()

      this.checkAll = false
      // 重置当前菜单类权限
      this.$refs.menuTree.setCheckedNodes(this.currentRoleMenus)
      // 重置当前角色类权限 先清空赋值
      this.$refs.roleTable.$refs.elTable.clearSelection()
      for (let i = 0; i < this.currentRoleRoles.length; i++) {
        for (let index = 0; index < this.roleData.length; index++) {
          if (
            this.currentRoleRoles[i].perm_id === this.roleData[index].perm_id
          ) {
            this.$refs.roleTable.$refs.elTable.toggleRowSelection(
              this.roleData[index],
              true
            )
          }
        }
      }
      // 重置当前部门数据类权限
      this.dataPermScope = this.selectRole.scope
      // DONE: 从全部到自定义重置时会出错，没有找到$refs.deptTree, 使用v-show 代替v-if替换组件显示解决
      if (this.dataPermScope === '4') {
        this.$refs.deptTree.setCheckedNodes(this.currentRoleDepts)
      }
    },
    resetcheckJianlian() {
      this.checkJianlian = false
    },
    // 全选操作
    handleCheckAll() {
      if (this.checkAll) {
        const allMenus = []
        this.checkAllMenu(this.menuData, allMenus)
        this.$refs.menuTree.setCheckedNodes(allMenus)
      } else {
        this.$refs.menuTree.setCheckedNodes([])
      }
    },
    // 递归全选
    checkAllMenu(menuData, allMenus) {
      menuData.forEach(menu => {
        allMenus.push(menu)
        if (menu.children) {
          this.checkAllMenu(menu.children, allMenus)
        }
      })
    },

    // 角色菜单授权提交
    submitAuthForm() {
      const roleId = this.selectRole.id
      if (roleId === 1) {
        this.$message({
          message: '超级管理员角色拥有所有权限，不允许修改！',
          type: 'error'
        })
        return
      }

      this.authLoading = true
      const rolePerms = []
      // 获取选中的菜单类权限
      const checkedNodes = this.$refs.menuTree.getCheckedNodes(false, true)
      for (let i = 0, len = checkedNodes.length; i < len; i++) {
        const rolePerm = { role_id: roleId, perm_id: checkedNodes[i].perm_id }
        rolePerms.push(rolePerm)
      }

      // 获取选中的角色类权限
      // 在使用Element前端库时候,需要获得表格选中行的时候 查看源码,发现有个store属性,保存了选中的行数据.
      const roleSelections = this.$refs.roleTable.$refs.elTable.store.states
        .selection
      for (let i = 0, len = roleSelections.length; i < len; i++) {
        const rolePerm = { role_id: roleId, perm_id: roleSelections[i].perm_id }
        rolePerms.push(rolePerm)
      }

      const roleScope = this.dataPermScope
      // 获取选中的部门数据权限
      if (roleScope === '4') {
        const checkedDeptNodes = this.$refs.deptTree.getCheckedNodes(
          false,
          true
        )

        for (let i = 0, len = checkedDeptNodes.length; i < len; i++) {
          const deptPerm = {
            role_id: roleId,
            perm_id: checkedDeptNodes[i].perm_id
          }
          rolePerms.push(deptPerm)
        }
      }

      saveRolePerms(roleId, rolePerms, roleScope)
        .then(res => {
          // console.log('saveRolePerms...', res)
          this.$notify({
            //  title: '错误',
            message: res.message,
            type: res.type
          })
          this.authLoading = false
          this.fetchData()
        })
        .catch(err => {
          console.log(err)
          this.authLoading = false
        })
    },
    renderContent(h, { node, data, store }) {
      return (
        <div class='column-container'>
          <span style='text-algin:center;margin-right:200px;'>
            <svg-icon icon-class={data.icon} /> {data.title}
          </span>
          <span style='text-algin:center;margin-right:200px;'>
            <el-tag
              type={
                data.type === 0 ? '' : data.type === 1 ? 'success' : 'warning'
              }
              size='small'
            >
              {data.type === 0 ? '目录' : data.type === 1 ? '菜单' : '操作'}
            </el-tag>
          </span>
          <span style='text-algin:center;margin-right:80px;'>
            {data.path ? data.path : '\t'}
          </span>
        </div>
      )
      // <span style='text-algin:center;margin-right:200px;'>id:{data.id} - pid:{data.pid} - perm_id: {data.perm_id}</span>
    },
    resetTemp() {
      this.temp = {
        id: undefined,
        name: '',
        remark: '',
        status: '1',
        listorder: 99
      }
    },
    handleCreate() {
      console.log('handleCreate...click')
      this.resetTemp()
      this.dialogStatus = 'create'
      this.dialogFormVisible = true
      this.$nextTick(() => {
        this.$refs['dataForm'].clearValidate()
      })
    },
    createData() {
      this.$refs['dataForm'].validate(valid => {
        if (valid) {
          console.log('createData valid done...', this.temp)

          // 调用api创建数据入库
          this.updateLoading = true
          createRole(this.temp).then(res => {
            // 成功后 关闭窗口
            this.updateLoading = false
            console.log('createRole...', res)
            this.fetchData()
            this.dialogFormVisible = false
            this.$notify({
              message: res.message,
              type: res.type
            })
          })
        }
      })
    },
    handleUpdate(row) {
      this.temp = Object.assign({}, row) // copy obj
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
          updateRole(this.temp).then(res => {
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
        }
      })
    },
    handleDelete(row) {
      const h = this.$createElement
      this.$msgbox({
        title: '提示',
        message: h('p', null, [
          h('span', null, '确认删除选中记录吗？[角色:  '),
          h('i', { style: 'color: teal' }, row.name),
          h('span', null, ' ]')
        ]),
        showCancelButton: true,
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        beforeClose: (action, instance, done) => {
          if (action === 'confirm') {
            instance.confirmButtonLoading = true

            // 调用api删除数据
            deleteRole(row.id)
              .then(res => {
                // 如果删除成功，后台重新更新数据,否则不更新数据
                done()
                instance.confirmButtonLoading = false
                if (res.type === 'success') {
                  this.fetchData()
                }
                this.$notify({
                  //  title: '错误',
                  message: '角色: ' + row.name + ' ' + res.message,
                  type: res.type
                })
              })
              .catch(err => {
                console.log(err)
                instance.confirmButtonLoading = false
              })
          } else {
            done()
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
    }
  }
}
</script>
 <style scoped>
.menu-container {
  margin-top: 100px;
}
.menu-header {
  padding-left: 8px;
  padding-bottom: 5px;
  text-align: left;
  font-size: 16px;
  color: rgb(20, 89, 121);
}
.menu-role {
  color: rgb(211, 66, 22);
}
/deep/ .el-checkbox__inner {
  display: inline-block;
  position: relative;
  border: 1px solid #dcdfe6;
  border-radius: 2px;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  width: 14px;
  height: 14px;
  z-index: 1;
  -webkit-transition: none !important;
  transition: none !important;
}
/deep/ .el-checkbox__input.is-indeterminate .el-checkbox__inner::before {
  content: "";
  position: absolute;
  display: block;
  background-color: #1890ff;
  height: 2px;
  -webkit-transform: scale(0.5);
  transform: scale(0.5);
  left: 0;
  right: 0;
  top: 5px;
}
/deep/ .el-checkbox__input.is-indeterminate .el-checkbox__inner::after {
  display: block !important;
  transform: rotate(45deg) scaleY(1);
}
</style>
