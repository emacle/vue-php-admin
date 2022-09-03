<template>
  <div class="app-container">
    <div v-perm="['/sys/log/logs/get']" class="filter-container">
      <el-input v-model="uriValue" placeholder="uri" style="width: 200px;" />
      <el-select v-model="filters[1].value" placeholder="请求方法" clearable>
        <el-option label="GET" value="get" />
        <el-option label="POST" value="post" />
        <el-option label="PUT" value="put" />
        <el-option label="DELETE" value="delete" />
      </el-select>
      <el-date-picker
        v-model="filters[2].value"
        :picker-options="pickerOptions"
        type="datetimerange"
        format="yyyy-MM-dd HH:mm"
        range-separator="至"
        start-placeholder="开始日期"
        end-placeholder="结束日期"
        align="left"
      />
    </div>

    <data-tables-server
      :data="list"
      :search-def="searchDef"
      :total="total"
      :filters="filters"
      :table-props="tableProps"
      :loading="listLoading"
      :page-size="10"
      :pagination-props="{ background: true, pageSizes: [10,30,50] }"
      layout="table,pagination"
      @query-change="fetchData"
    >
      <el-table-column
        v-for="title in titles"
        :key="title.label"
        :prop="title.prop"
        :label="title.label"
        :sortable="title.sortable"
      />
      <el-table-column label="请求参数" min-width="100px">
        <template slot-scope="scope">
          <el-button v-waves type="primary" size="mini" @click="handleDetail(scope.row)">更多...</el-button>
        </template>
      </el-table-column>
    </data-tables-server>

    <el-dialog :visible.sync="dialogFormVisible" title="详细">
      <json-view :data="reqParams" :closed="false" :deep="1" />
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">取消</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import waves from '@/directive/waves' // Waves directive
import perm from '@/directive/perm/index.js' // 权限判断指令
import { getLogList } from '@/api/log'
import _ from 'lodash'
import timestamp from 'unix-timestamp'
import jsonView from 'vue-json-views'

export default {
  name: 'SysLog',
  // 所以在编写路由 router 和路由对应的 view component 的时候一定要确保 两者的 name 是完全一致的。
  directives: { waves, perm },
  components: { jsonView },
  data() {
    return {
      uriValue: '',
      reqParams: {},
      dialogFormVisible: false,
      searchDef: {
        show: true,
        debounce: 3000
      },
      defaultQueryInfo: '',
      filters: [
        {
          prop: 'uri',
          value: ''
        },
        {
          prop: 'method',
          value: ''
        },
        {
          prop: 'time',
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
          label: 'id',
          sortable: 'custom' // 指定sortable custom 则为可排序字段，不指定则不排序
        },
        {
          prop: 'username',
          label: '用户名'
        },
        {
          prop: 'ip',
          label: 'IP地址'
        },
        {
          prop: 'uri',
          label: 'uri',
          sortable: 'custom'
        },
        {
          prop: 'method',
          label: '请求方法',
          sortable: 'custom'
        },
        {
          prop: 'time',
          label: '创建时间',
          sortable: 'custom'
        },
        {
          prop: 'rtime',
          label: '请求耗时（秒)',
          sortable: 'custom'
        },
        {
          prop: 'response_code',
          label: '响应code',
          sortable: 'custom'
        }
      ],
      tableProps: {
        border: false,
        stripe: true,
        highlightCurrentRow: true,
        defaultSort: {
          prop: 'id',
          order: 'ascending'
        }
      },
      pickerOptions: {
        shortcuts: [
          {
            text: '最近一周',
            onClick(picker) {
              const end = new Date()
              const start = new Date()
              start.setTime(start.getTime() - 3600 * 1000 * 24 * 7)
              picker.$emit('pick', [start, end])
            }
          },
          {
            text: '最近一个月',
            onClick(picker) {
              const end = new Date()
              const start = new Date()
              start.setTime(start.getTime() - 3600 * 1000 * 24 * 30)
              picker.$emit('pick', [start, end])
            }
          },
          {
            text: '最近三个月',
            onClick(picker) {
              const end = new Date()
              const start = new Date()
              start.setTime(start.getTime() - 3600 * 1000 * 24 * 90)
              picker.$emit('pick', [start, end])
            }
          }
        ]
      }
    }
  },
  watch: {
    uriValue: _.debounce(function(newV, oldV) {
      // console.log(oldV + '=>' + newV)
      // 使用lodash debounce 延迟防抖动，几秒后将变量值 赋值给 data-tables-server 的 filters[0].value
      // data-tables-server watch filters 值发生变化后会立即向后台重新获取数据 等价于 filter值的延迟得到
      this.filters[0].value = this.uriValue
    }, 500)
  },
  created() {},
  methods: {
    // 获取数据
    fetchData(queryInfo) {
      // console.log('queryInfo', queryInfo)
      // 页面调用 fetchData 时会清空queryInfo， 重载该状态值
      // 页面 created 时及翻页时， queryInfo 不会空均会变化， 需要保留此状态，以备fetchData里 queryInfo为空时，重新加载该值
      !queryInfo
        ? (queryInfo = this.defaultQueryInfo)
        : (this.defaultQueryInfo = queryInfo)

      this.listLoading = true

      // GET /users?offset=1&limit=20&fields=id,username,email,listorder&sort=-listorder,+id&query=~username,status&username=admin&status=1
      // {"type":"init","page":1,"pageSize":5,"sort":{"prop":"listorder","order":"ascending"},"filters":[{"prop":"username","value":""},{"prop":"status","value":""}]}
      // 初始化分页/排序/查询字符串，与后端一致符合 Restful 风格
      const offset = queryInfo.page
      const limit = queryInfo.pageSize
      const sort =
        (queryInfo.sort.order === 'ascending' ? '+' : '-') + queryInfo.sort.prop

      // 根据 &query: ~uri,method,time 构造具体参数
      let queryStr = ''
      _.forEach(queryInfo.filters, function(value, key) {
        if (value.value) {
          if (value.prop !== 'time') {
            queryStr += '&' + value.prop + '=' + value.value
          } else if (value.prop === 'time') {
            // 时间区间为数组 value.value 转成 unixstamp 10000,20000 [1585670400,1586880000]
            const finalRes = _.map(value.value, function square(item) {
              return timestamp.fromDate(item)
            })
            queryStr += '&' + value.prop + '=' + _.join(finalRes)
          }
        }
      })

      // &query: ~uri,method,time 根据业务情况写死
      const queryParms =
        'offset=' +
        offset +
        '&limit=' +
        limit +
        '&sort=' +
        sort +
        '&query=~uri,method,time' +
        queryStr
      // console.log(queryParms)

      getLogList(queryParms).then(res => {
        console.log('getUserList', res)
        this.list = res.data.items
        this.total = res.data.total
        this.listLoading = false
      })
    },
    handleDetail(row) {
      this.dialogFormVisible = true
      this.reqParams = row.params
    }
  }
}
</script>
