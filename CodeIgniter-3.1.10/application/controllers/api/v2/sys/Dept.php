<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
// Using Medoo namespace
use Medoo\Medoo;

class Dept extends RestController
{
    private $Medoodb;

    function __construct()
    {
        parent::__construct();
        $this->load->model('Base_model');
        // $this->config->load('config', true);
        // Initialize
        $this->Medoodb = new Medoo(config_item('medoodb'));
    }

    // 增
    function depts_post()
    {

        $parms = $this->post();  // 获取表单参数，类型为数组

        if ($this->Base_model->_key_exists('sys_dept', ['name' => $parms['name']])) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 机构名称重复'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $dept_id = $this->Base_model->_insert_key('sys_dept', $parms);
        if (!$dept_id) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 机构新增失败'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 生成该部门对应的权限: sys_perm, 权限类型为: dept, 生成唯一的 perm_id
        $perm_id = $this->Base_model->_insert_key('sys_perm', ['perm_type' => 'dept', "r_id" => $dept_id]);
        if (!$perm_id) {
            var_dump($this->uri->uri_string . ' 生成该部门对应的权限: sys_perm, 失败...');
            var_dump(['perm_type' => 'role', "r_id" => $dept_id]);
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
            "message" => $parms['name'] . ' - 机构新增成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 删
    function depts_delete($id)
    {
        // $parms = $this->delete();  // delete() 不能使用此方法获取表单参数，根据规范只能使用 url /sys/dept/depts/2 传参方式
        // var_dump($id);

        // 参数检验/数据预处理
        // 含有子节点不允许删除
        $data = $this->Medoodb->get(
            'sys_dept',
            '*',
            ['pid' => $id]
        ); // 返回一条记录 或 null ， 比 select()效率要高？

        if (!empty($data)) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '存在子节点不能删除'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 删除外键关联表 sys_role_perm , sys_user_dept, sys_perm, sys_dept
        // 1. 根据sys_dept id及'dept' 查找 perm_id
        // 2. 删除sys_role_perm中perm_id记录
        // 3. 删除sys_perm中 perm_type='role' and r_id = role_id 记录,即第1步中获取的 perm_id， 一一对应
        // 4. 删除sys_user_dept中 dept_id = $id) 的记录
        $where = 'perm_type="dept" and r_id=' . $id;
        $arr = $this->Base_model->_get_key('sys_perm', '*', $where);
        if (empty($arr)) {
            var_dump($this->uri->uri_string . ' 未查找到 sys_perm 表中记录');
            var_dump($where);
            return;
        }

        $perm_id = $arr[0]['id']; // 正常只有一条记录
        $this->Base_model->_delete_key('sys_role_perm', ['perm_id' => $perm_id]); // 必须删除权限id 因为超级管理员角色自动拥有该权限否则会造成删除关联错误
        $this->Base_model->_delete_key('sys_perm', ['id' => $perm_id]);

        $this->Base_model->_delete_key('sys_user_dept', ['dept_id' => $id]);

        // 删除基础表 sys_dept
        if (!$this->Base_model->_delete_key('sys_dept', ['id' => $id])) {
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

    // 改
    function depts_put()
    {
        $parms = $this->put();  // 获取表单参数，类型为数组
        // var_dump($parms['path']);

        // 参数检验/数据预处理
        $id = $parms['id'];
        unset($parms['id']); // 剃除索引id
        unset($parms['children']); // 剃除传递上来的子节点信息

        if ($id == $parms['pid']) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 上级机构不能为自己'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $where = ["id" => $id];

        if (!$this->Base_model->_update_key('sys_dept', $parms, $where)) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['name'] . ' - 机构更新错误'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['name'] . ' - 机构更新成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 查
    function depts_get()
    {
        $DeptArr = $this->Medoodb->select(
            'sys_dept',
            [
                'id',
                'pid',
                'name',
                'aliasname',
                'listorder',
                'status'
            ],
            [
                "ORDER" => ["listorder" => "DESC"]
            ]
        );

        $DeptTree = $this->permission->genDeptTree($DeptArr, 'id', 'pid', 0);

        $message = [
            "code" => 20000,
            "data" => $DeptTree,
        ];
        $this->response($message, RestController::HTTP_OK);
    }
}
