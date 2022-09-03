<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;

class Menu extends RestController
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('Base_model');
    }

    // 增
    function menus_post()
    {
        $parms = $this->post();  // 获取表单参数，类型为数组

        // 参数检验/数据预处理
        // 菜单类型为目录
        if (!$parms['type']) {
            $parms['component'] = 'Layout';
        }

        $menu_id = $this->Base_model->_insert_key('sys_menu', $parms);
        if (!$menu_id) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['title'] . ' - 菜单添加失败'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 生成该菜单对应的权限: sys_perm, 权限类型为: menu, 生成唯一的 perm_id
        $perm_id = $this->Base_model->_insert_key('sys_perm', ['perm_type' => 'menu', "r_id" => $menu_id]);
        if (!$perm_id) {
            var_dump($this->uri->uri_string . ' 生成该菜单对应的权限: sys_perm, 失败...');
            var_dump(['perm_type' => 'menu', "r_id" => $menu_id]);
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
            "message" => $parms['title'] . ' - 菜单添加成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 改
    function menus_put()
    {
        $parms = $this->put();  // 获取表单参数，类型为数组
        // var_dump($parms['path']);

        // 参数检验/数据预处理
        if ($parms['id'] == $parms['pid']) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '父节点不能是自己'
            ];
            $this->response($message, RestController::HTTP_OK);
        }
        // 菜单类型为目录
        if ($parms['type'] == 0) {
            $parms['component'] = 'Layout';
        }
        // 菜单类型为功能按钮时
        if ($parms['type'] == 2) {
            $parms['component'] = '';
            $parms['icon'] = '';
        }

        $id = $parms['id'];
        unset($parms['id']); // 择出索引id
        $where = ["id" => $id];

        if (!$this->Base_model->_update_key('sys_menu', $parms, $where)) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['title'] . ' - 菜单更新错误'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['title'] . ' - 菜单更新成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 删
    function menus_delete($id)
    {
        // $parms = $this->delete();  // delete() 不能使用此方法获取表单参数，根据规范只能使用 url /sys/dept/depts/2 传参方式
        // var_dump($id);

        // 参数检验/数据预处理
        // 存在子节点 不能删除返回
        $hasChild = $this->Base_model->hasChildMenu($id);
        if ($hasChild) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '存在子节点不能删除'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 删除外键关联表 sys_role_perm , sys_perm, sys_menu
        // 1. 根据sys_menu id及'menu' 查找 perm_id
        // 2. 删除sys_role_perm 中perm_id记录
        // 3. 删除sys_perm中 perm_type='menu' and r_id = menu_id 记录,即第1步中获取的 perm_id， 一一对应
        // 4. 删除sys_menu 中 id = menu_id 的记录
        $where = 'perm_type="menu" and r_id=' . $id;
        $arr = $this->Base_model->_get_key('sys_perm', '*', $where);
        if (empty($arr)) {
            // var_dump($this->uri->uri_string . ' 未查找到 sys_perm 表中记录');
            // var_dump($where);

            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '数据库未查找到该菜单'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $perm_id = $arr[0]['id']; // 正常只有一条记录
        $this->Base_model->_delete_key('sys_role_perm', ['perm_id' => $perm_id]);
        $this->Base_model->_delete_key('sys_perm', ['id' => $perm_id]);

        // 删除基础表 sys_menu
        if (!$this->Base_model->_delete_key('sys_menu', ['id' => $id])) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '删除错误'
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
    function menus_get()
    {
        $Bearer = $this->input->get_request_header('Authorization', true);
        list($Token) = sscanf($Bearer, 'Bearer %s');

        try {
            $jwt_object = JWT::decode($Token, config_item('jwt_key'), ['HS256']); //HS256方式，这里要和签发的时候对应
        } catch (\Firebase\JWT\ExpiredException $e) {  // access_token过期
            $message = [
                "code" => 50014,
                "message" => $e->getMessage()
            ];
            $this->response($message, RestController::HTTP_UNAUTHORIZED);
        } catch (Exception $e) {  //其他错误
            $message = [
                "code" => 50015,
                "message" => $e->getMessage()
            ];
            $this->response($message, RestController::HTTP_UNAUTHORIZED);
        }

        $MenuTreeArr = $this->permission->getPermission($jwt_object->user_id, 'menu', true);
        $MenuTree = $this->permission->genVueMenuTree($MenuTreeArr, 'id', 'pid', 0);
        $message = [
            "code" => 20000,
            "data" => $MenuTree,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 根据token拉取 treeselect 下拉选项菜单
    function treeoptions_get()
    {
        // 此 uri 可不做权限/token过期验证，则在菜单里，可以不加入此项路由path /sys/menu/treeoptions。
        $Bearer = $this->input->get_request_header('Authorization', true);
        list($Token) = sscanf($Bearer, 'Bearer %s');

        try {
            $jwt_obj = JWT::decode($Token, config_item('jwt_key'), ['HS256']); //HS256方式，这里要和签发的时候对应
        } catch (\Firebase\JWT\ExpiredException $e) {  // access_token过期
            $message = [
                "code" => 50014,
                "message" => $e->getMessage()
            ];
            $this->response($message, RestController::HTTP_UNAUTHORIZED);
        } catch (Exception $e) {  //其他错误
            $message = [
                "code" => 50015,
                "message" => $e->getMessage()
            ];
            $this->response($message, RestController::HTTP_UNAUTHORIZED);
        }

        $MenuTreeArr = $this->permission->getPermission($jwt_obj->user_id, 'menu', false);
        array_unshift($MenuTreeArr, ['id' => 0, 'pid' => -1, 'title' => '顶级菜单']);
        $MenuTree = $this->permission->genVueMenuTree($MenuTreeArr, 'id', 'pid', -1);

        $message = [
            "code" => 20000,
            "data" => $MenuTree,
        ];
        $this->response($message, RestController::HTTP_OK);
    }
}
