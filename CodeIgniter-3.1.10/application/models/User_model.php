<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class User_model extends CI_Model
{
    /**
     * 用户模型部分
     *
     */
    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 登陆校验用户名密码
     * @param $username
     * @param $passwd
     */
    function validate($username, $passwd)
    {
        $sql = "SELECT
                    *
                FROM
                    sys_user
                WHERE
                    username = '" . $username . "'
                AND password = '" . $passwd . "'";

        $query = $this->db->query($sql);
        if (($query->row_array()) == null) {
            $result = array(
                'success' => false,
                'userinfo' => null
            );
        } else {
            $result = array(
                'success' => true,
                'userinfo' => $query->row_array()
            );
        }
        return $result;
    }

    // /**
    //  * 获取所有用户列表
    //  */
    // function getUserList($filters, $sort, $page, $pageSize)
    // {

    //     // 默认排序
    //     $orderStr = '';
    //     if ($sort['prop'] && $sort['order']) {
    //         $orderStr = " ORDER BY " . $sort['prop'] . " " . ($sort['order'] === 'ascending' ? 'asc' : 'desc');
    //     }

    //     $filterStr = '';
    //     $j = 0;
    //     foreach ($filters as $k => $v) {
    //         if ($v['value'] !== '' && !is_null($v['value'])) {
    //             if ($j) {
    //                 $filterStr = $filterStr . " and ";
    //             }

    //             if ($v['prop'] === 'username') {
    //                 $filterStr .= $v['prop'] . " like '%" . $v['value'] . "%' ";
    //             }
    //             if ($v['prop'] === 'status') {
    //                 $filterStr .= $v['prop'] . "=" . $v['value'];
    //             }

    //             $j++;
    //         }
    //     }

    //     if ($filterStr) {
    //         $filterStr = " and " . $filterStr;
    //     }

    //     $sql = "SELECT
    //                  *
    //             FROM
    //                 sys_user where 1=1 "
    //         . $filterStr
    //         . $orderStr . " limit " . ($page - 1) * $pageSize . "," . $pageSize;

    //     $query = $this->db->query($sql);
    //     return $query->result_array();
    // }

    // function getUserListCnt($filters)
    // {
    //     $filterStr = '';
    //     $j = 0;
    //     foreach ($filters as $k => $v) {
    //         if ($v['value'] !== '' && !is_null($v['value'])) {
    //             if ($j) {
    //                 $filterStr = $filterStr . " and ";
    //             }

    //             if ($v['prop'] === 'username') {
    //                 $filterStr .= $v['prop'] . " like '%" . $v['value'] . "%' ";
    //             }
    //             if ($v['prop'] === 'status') {
    //                 $filterStr .= $v['prop'] . "=" . $v['value'];
    //             }

    //             $j++;
    //         }
    //     }

    //     if ($filterStr) {
    //         $filterStr = " and " . $filterStr;
    //     }
    //     $sql = "SELECT
    //                 count(u.id) cnt
    //             FROM
    //                 sys_user u  where 1=1 " . $filterStr;

    //     $query = $this->db->query($sql);
    //     if (empty($query->result_array())) {
    //         return 0;
    //     } else {
    //         $result = $query->result_array();
    //         return $result[0]['cnt'];
    //     }
    // }

    /**
     * 获取所有角色列表
     * 并且根据$userId 获取对应$userId用户所拥有的角色类权限选项
     * 当用户含有未拥有的角色类权限时 设置 isDisabled 禁用选择
     * 新增编辑时使用
     */
    function getRoleOptions($userId)
    {
        $sql = "SELECT
                    r.id,
                    r.name,
                    r.remark,
                    r.status,
                    IF (
                        t.name IS NULL,
                        'true',
                        'false'
                    ) isDisabled, 
                    r.listorder
                FROM
                    sys_role r
                LEFT JOIN (
                SELECT
                    r.id,
                    r.name,
                    r.remark,
                    r.status
                FROM
                    sys_user_role ur,
                    sys_role_perm rp,
                    sys_perm p,
                    sys_role r
                WHERE
                    ur.user_id= $userId
                AND ur.role_id = rp.role_id
                AND rp.perm_id = p.id
                AND p.perm_type = 'role'
                AND p.r_id = r.id
                AND r. STATUS = 1
                ) t ON r.id = t.id
                ORDER BY r.listorder";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 获取所有部门列表
     * 并且根据$userId 获取对应$userId用户所拥有的部门类数据权限选项
     * 当用户含有未拥有的部门类数据权限时 设置 isDisabled 禁用选择
     * 新增编辑时使用
     */
    function getDeptOptions($userId)
    {
        $sql = "SELECT
                    d.*,
                IF (
                    t.name IS NULL,
                    'true',
                    'false'
                ) isDisabled
                FROM
                    sys_dept d
                LEFT JOIN (
                    SELECT
                        d.*
                    FROM
                        sys_user_role ur,
                        sys_role_perm rp,
                        sys_perm p,
                        sys_dept d
                    WHERE
                        ur.user_id = $userId
                    AND ur.role_id = rp.role_id
                    AND rp.perm_id = p.id
                    AND p.perm_type = 'dept'
                    AND p.r_id = d.id
                    AND d. STATUS = 1
                ) t ON d.id = t.id";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 根据 用户ID 获取该用户被分配的角色
     */
    function getUserRoles($Id)
    {
        $sql = "SELECT
                    r.id,
                    r.name,
                    r.remark,
                    r.status
                FROM
                    sys_user_role ur,
                    sys_role r
                WHERE
                    ur.role_id = r.id
                AND ur.user_id =" . $Id;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 根据 用户ID 获取该用户归属的部门
     */
    function getUserDepts($Id)
    {
        $sql = "SELECT
                    ud.dept_id
                FROM
                    sys_user_dept ud
                WHERE
                    ud.user_id =" . $Id;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 根据 用户ID 获取该用户所拥有的角色
     * [
     * ['user_id'=> 1, 'role_id'=>1]
     * ...
     * ]
     */
    function getRolesByUserId($Id)
    {
        $sql = "SELECT
                    ur.user_id,ur.role_id
                FROM
                    sys_user_role ur
                 WHERE ur.user_id=" . $Id;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

   /**
     * 根据 用户ID 获取该用户所拥有的角色
     * [
     * ['user_id'=> 1, 'role_id'=>1]
     * ...
     * ]
     */
    function getDeptsByUserId($Id)
    {
        $sql = "SELECT
                    ud.user_id, ud.dept_id
                FROM
                    sys_user_dept ud
                 WHERE ud.user_id=" . $Id;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 根据 $userId 拉取用户信息
     * @param $userId
     */
    function getUserInfo($userId)
    {
        $sql = "SELECT
                    u.id,
                    u.username,
                    u.tel,
                    u.email,
                    u.avatar,
                    u.sex,
                    u.last_login_ip,
                    u.last_login_time,
                    u.status
                FROM
                    sys_user u
                WHERE
                    u.id = " . $userId;

        $query = $this->db->query($sql);
        if (($query->row_array()) == null) {
            $result = array(
                'success' => false,
                'userinfo' => null
            );
        } else {
            $result = array(
                'success' => true,
                'userinfo' => $query->row_array()
            );
        }
        return $result;
    }
    
    /*
     * github登录认证
     */
    function getUserInfoByTel($email)
    {
        $sql = "SELECT
                   u.*
                FROM
                    sys_user u
                 WHERE u.email='" . $email . "'";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 根据$userId 拉取用户角色信息
     * @param $userId
     */
    function getUserRolesByUserId($userId)
    {
        $sql = "SELECT
                    DISTINCT r.id,r.name
                FROM
                    sys_user_role ur,
                    sys_role r
                WHERE
                    r.id = ur.role_id
                    AND r.status=1
                    AND ur.user_id =" . $userId;

        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    /***********************
     * 用户模型部分结束
     ***********************/
}