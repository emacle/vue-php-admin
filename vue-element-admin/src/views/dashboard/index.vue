<template>
  <div class="dashboard-container">
    <component :is="currentRole" />
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import adminDashboard from './admin'
import editorDashboard from './editor'

export default {
  name: 'Dashboard',
  components: { adminDashboard, editorDashboard },
  data() {
    return {
      currentRole: 'adminDashboard'
    }
  },
  computed: {
    ...mapGetters([
      'roles'
    ])
  },
  created() {
    // console.log('dashboard...', this.roles)
    // if (!this.roles.includes('admin')) {
    //   this.currentRole = 'editorDashboard'
    // }
    // 非超级管理进入 editorDashboard 面板
    for (let i = 0; i < this.roles.length; i++) {
      if (!this.roles[i].name.includes('超级管理员') && !this.roles[i].name.includes('admin')) {
        this.currentRole = 'editorDashboard'
        break
      }
    }
  }
}
</script>
