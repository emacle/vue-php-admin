<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Role extends RestController
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('Base_model');
        $this->load->model('Role_model');
    }

    // 增
    function roles_post()
    {
        $parms = $this->post();  // 获取表单参数，类型为数组

        if ($this->Base_model->_key_exists('sys_role', ['name' => $parms['name']])) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 角色名重复'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 加入新增时间
        $parms['create_time'] = time();

        $role_id = $this->Base_model->_insert_key('sys_role', $parms);
        if (!$role_id) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 角色新增失败'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 生成该角色对应的权限: sys_perm, 权限类型为: role, 生成唯一的 perm_id
        $perm_id = $this->Base_model->_insert_key('sys_perm', ['perm_type' => 'role', "r_id" => $role_id]);
        if (!$perm_id) {
            var_dump($this->uri->uri_string . ' 生成该角色对应的权限: sys_perm, 失败...');
            var_dump(['perm_type' => 'role', "r_id" => $role_id]);
            return;
        }

        // 超级管理员角色自动拥有该权限 perm_id
        $role_perm_id = $this->Base_model->_insert_key('sys_role_perm', ["role_id" => 1, "perm_id" => $perm_id]);
        if (!$role_perm_id) {
            var_dump($this->uri->uri_string . ' 超级管理员角色自动拥有该权限 perm_id, 失败...');
            var_dump(["role_id" => 1, "perm_id" => $perm_id]);
            return;
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['name'] . ' - 角色新增成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 改
    function roles_put()
    {
        $parms = $this->put();  // 获取表单参数，类型为数组

        // 参数检验/数据预处理
        // 超级管理员角色不允许修改
        if ($parms['id'] == 1) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 角色不允许修改'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $id = $parms['id'];
        unset($parms['id']); // 剃除索引id

        // 加入更新时间
        $parms['update_time'] = time();
        $where = ["id" => $id];

        if (!$this->Base_model->_update_key('sys_role', $parms, $where)) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 角色更新错误'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['name'] . ' - 角色更新成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 删
    function roles_delete($id)
    {

        // 参数检验/数据预处理
        // 超级管理员角色不允许删除
        if ($id == 1) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" =>'不允许删除'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 删除外键关联表 sys_role_perm , sys_perm, sys_role
        // 1. 根据sys_role id及'menu' 查找 perm_id
        // 2. 删除sys_role_perm 中perm_id记录
        // 3. 删除sys_perm中 perm_type='role' and r_id = role_id 记录,即第1步中获取的 perm_id， 一一对应
        // 4. 删除sys_role 中 id = role_id 的记录
        $where = 'perm_type="role" and r_id=' . $id;
        $arr = $this->Base_model->_get_key('sys_perm', '*', $where);
        if (empty($arr)) {
            var_dump($this->uri->uri_string . ' 未查找到 sys_perm 表中记录');
            var_dump($where);
            return;
        }

        $perm_id = $arr[0]['id']; // 正常只有一条记录
        $this->Base_model->_delete_key('sys_role_perm', ['perm_id' => $perm_id]); // 必须删除权限id 因为超级管理员角色自动拥有该权限否则会造成删除关联错误
        $this->Base_model->_delete_key('sys_role_perm', ['role_id' => $id]); // 再删除该角色对应的权限id（原有的菜单）
        $this->Base_model->_delete_key('sys_perm', ['id' => $perm_id]);

        $this->Base_model->_delete_key('sys_user_role', ['role_id' => $id]);

        // 删除基础表 sys_role
        if (!$this->Base_model->_delete_key('sys_role', ['id' => $id])) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '删除失败'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => '删除成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 查
    function roles_get()
    {
        $RoleArr = $this->Role_model->getRoleList();
        $message = [
            "code" => 20000,
            "data" => $RoleArr,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 获取所有菜单 不需权限验证
    function allmenus_get()
    {
        $MenuTreeArr = $this->Role_model->getAllMenus();
        if (empty($MenuTreeArr)) {
            $message = [
                "code" => 20000,
                "data" => $MenuTreeArr,
                "message" => "数据库表中没有菜单"
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $MenuTree = $this->permission->genVueMenuTree($MenuTreeArr, 'id', 'pid', 0);
        $message = [
            "code" => 20000,
            "data" => $MenuTree,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 获取所有角色带perm_id 不需权限验证
    function allroles_get()
    {
        $AllRolesArr = $this->Role_model->getAllRoles();
        if (empty($AllRolesArr)) {
            $message = [
                "code" => 20000,
                "data" => $AllRolesArr,
                "message" => "数据库表中没有角色"
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "data" => $AllRolesArr,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 获取所有部门带perm_id 不需权限验证
    function alldepts_get()
    {
        $AllDeptsArr = $this->Role_model->getAllDepts();

        if (empty($AllDeptsArr)) {
            $message = [
                "code" => 20000,
                "data" => $AllDeptsArr,
                "message" => "数据库表中没有部门"
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $DeptTree = $this->permission->genDeptTree($AllDeptsArr, 'id', 'pid', 0);
        $message = [
            "code" => 20000,
            "data" => $DeptTree,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    //  获取角色拥有的菜单权限 不需权限验证
    function rolemenu_post()
    {
        $parms = $this->post();  // 获取表单参数，类型为数组
        $RoleId = $parms['roleId'];

        $MenuTreeArr = $this->Role_model->getRoleMenu($RoleId);
        $message = [
            "code" => 20000,
            "data" => $MenuTreeArr,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 获取角色拥有的角色权限 不需权限验证
    function rolerole_post()
    {
        $parms = $this->post();  // 获取表单参数，类型为数组
        $RoleId = $parms['roleId'];

        $RoleRoleArr = $this->Role_model->getRoleRole($RoleId);
        $message = [
            "code" => 20000,
            "data" => $RoleRoleArr,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 获取角色拥有的部门数据权限 不需权限验证
    function roledept_post()
    {
        $parms = $this->post();  // 获取表单参数，类型为数组
        $RoleId = $parms['roleId'];

        $RoleDeptArr = $this->Role_model->getRoleDept($RoleId);
        $message = [
            "code" => 20000,
            "data" => $RoleDeptArr,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 保存角色对应权限
    function saveroleperm_post()
    {
        $parms = $this->post();  // 获取表单参数，类型为数组
        //        var_dump($parms['roleId']);
        //        var_dump($parms['rolePerms']);
        //        var_dump($parms['roleScope']);
        // 参数检验/数据预处理
        // 超级管理员角色不允许删除
        if ($parms['roleId'] == 1) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '超级管理员角色拥有所有权限，不允许修改！'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 部门数据授权范围写入sys_role表
        $ret = $this->Base_model->_update_key('sys_role', ['scope' => $parms['roleScope']], ['id' => $parms['roleId']]);
        if (!$ret) {
            var_dump('部门数据授权范围写入sys_role表失败!');
        }
        // 写入将角色->权限对应关系写入 sys_role_perm 表

        $RolePermArr = $this->Role_model->getRolePerm($parms['roleId']);

        $AddArr = $this->permission->array_diff_assoc2($parms['rolePerms'], $RolePermArr);
        // var_dump('------------只存在于前台传参 做添加操作-------------');
        // var_dump($AddArr);
        $failed = false;
        $failedArr = [];
        foreach ($AddArr as $k => $v) {
            $ret = $this->Base_model->_insert_key('sys_role_perm', $v);
            if (!$ret) {
                $failed = true;
                array_push($failedArr, $v);
            }
        }
        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '授权失败 ' . json_encode($failedArr)
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $DelArr = $this->permission->array_diff_assoc2($RolePermArr, $parms['rolePerms']);
        // var_dump('------------只存在于后台数据库 删除操作-------------');
        // var_dump($DelArr);
        $failed = false;
        $failedArr = [];
        foreach ($DelArr as $k => $v) {
            $ret = $this->Base_model->_delete_key('sys_role_perm', $v);
            if (!$ret) {
                $failed = true;
                array_push($failedArr, $v);
            }
        }
        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '授权失败 ' . json_encode($failedArr)
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "data" => $parms,
            "message" => '授权操作成功',
        ];
        $this->response($message, RestController::HTTP_OK);
    }
}
