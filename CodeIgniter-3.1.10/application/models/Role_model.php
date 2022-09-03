<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Role_model extends CI_Model
{
    /**
     * 角色模型部分
     */
    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    /***********************
     * 角色模型部分
     ************************/

    /**
     * 获取所有角色列表
     */
    function getRoleList()
    {
        $sql = "SELECT
                    r.*
                FROM
                    sys_role r 
                 ORDER BY
                    r.listorder";
        $query = $this->db->query($sql);
        return [
            "items" => $query->result_array(),
            "total" => count($query->result_array())
        ];
    }

    /**
     *  获取所有菜单并加入对应的权限id
     */
    function getAllMenus()
    {
        $sql = "SELECT
                    p.id perm_id,
                    m.*
                FROM
                    sys_menu m,
                    sys_perm p
                WHERE
                    p.perm_type = 'menu'
                AND p.r_id = m.id
                ORDER BY
                    m.listorder";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 获取所有角色列表 带perm_id
     */
    function getAllRoles()
    {
        $sql = "SELECT
                    r.*, p.id perm_id
                FROM
                    sys_role r,
                    sys_perm p
                WHERE
                    r.id = p.r_id
                AND p.perm_type = 'role'
                ORDER BY
                    r.listorder";
        $query = $this->db->query($sql);
        return [
            "items" => $query->result_array(),
            "total" => count($query->result_array())
        ];
    }

    /**
     * 获取所有部门列表 带perm_id
     */
    function getAllDepts()
    {
        $sql = "SELECT
                    p.id perm_id,
                    d.*
                FROM
                    sys_dept d,
                    sys_perm p
                WHERE
                    p.perm_type = 'dept'
                AND p.r_id = d.id
                ORDER BY
                    d.listorder";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    function getRoleMenu($RoleId)
    {
        $sql = "SELECT
                    p.id perm_id,
                    m.*
                FROM
                    sys_menu m,
                    sys_perm p,
                    sys_role_perm rp
                WHERE
                    rp.perm_id = p.id
                AND p.perm_type = 'menu'
                AND p.r_id = m.id
                AND rp.role_id = " . $RoleId . "
                ORDER BY
                    m.listorder";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 获取角色拥有的角色权限
     * @param $RoleId
     * @return mixed
     */
    function getRoleRole($RoleId)
    {
        $sql = "SELECT
                    p.id perm_id,
                    r.*
                FROM
                    sys_role r,
                    sys_perm p,
                    sys_role_perm rp
                WHERE
                    rp.perm_id = p.id
                AND p.perm_type = 'role'
                AND p.r_id = r.id
                AND rp.role_id =" . $RoleId . "
                ORDER BY
                    r.listorder";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 获取角色拥有的部门数据权限
     * @param $RoleId
     * @return mixed
     */
    function getRoleDept($RoleId)
    {
        $sql = "SELECT
                    p.id perm_id,
                    d.*
                FROM
                    sys_dept d,
                    sys_perm p,
                    sys_role_perm rp
                WHERE
                    rp.perm_id = p.id
                AND p.perm_type = 'dept'
                AND p.r_id = d.id
                AND rp.role_id = " . $RoleId . "
                ORDER BY
                    d.listorder";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 根据 角色ID 获取该角色所拥有的权限
     * [
     * ['role_id'=> 1, 'perm_id'=>1]
     * ...
     * ]
     */
    function getRolePerm($RoleId)
    {
        $sql = "SELECT
                    role_id, perm_id
                FROM
                    sys_role_perm
                WHERE
                    role_id =" . $RoleId;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /***********************
     * 角色模型部分结束
     ***********************/
}
