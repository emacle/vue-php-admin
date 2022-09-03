<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Base_model extends CI_Model
{
    /*
     * 1. 通用数据库操作方法 增删改查
     * 2. 权限/菜单模型部分
     */
    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /************************
     * 数据库操作模型部分
     ***********************/

    /**
     * 获取值
     * $table: 表名
     * $select: 查找项， $select = '*' 或 $select = 'id,title'
     * $where: 条件项 $where= 'id=1' 或 $where = 'id=1 and title="blah"'
     * $order: $order = 'id desc'
     * 返回值是数组
     */
    function _get_key($table, $select = '', $where = '', $order = '')
    {
        if ($select) {
            $this->db->select($select);
        }
        if ($where) {
            $this->db->where($where);
        }
        if ($order) {
            $this->db->order_by($order);
        }

        $query = $this->db->get($table);
        return $query->result_array();
    }

    function _insert_key($table, $data)
    {
        $this->db->insert($table, $data);
        // 如果$table没有配置自增主键，则insert_id返回值为0
        return $this->db->insert_id();
    }

    // TODO: 更新错误判断 https://stackoverflow.com/questions/20030642/check-if-db-update-successful-with-codeigniter-when-potentially-no-rows-are-upd
    function _update_key($table, $data, $where)
    {
        $this->db->trans_start();
        $this->db->where($where);
        $this->db->update($table, $data);
        $this->db->trans_complete();
        // return $this->db->affected_rows();
        // was there any update or error?
        if ($this->db->affected_rows() == '1') {
            return TRUE;
        } else {
            // any trans error?
            if ($this->db->trans_status() === FALSE) {
                return FALSE;
            }
            return TRUE;
        }
    }

    function _key_exists($table, $where)
    {
        $this->db->where($where);
        $this->db->from($table);
        return $this->db->count_all_results();
    }

    function _delete_key($table, $where)
    {
        $this->db->where($where);
        $this->db->delete($table);
        return $this->db->affected_rows();
    }

    function _total($table)
    {
        $sql = "select count(*) cnt from $table";
        $query = $this->db->query($sql);
        if (($query->row_array()) == null) {
            return 0;
        } else {
            $result = $query->result_array();
            return $result[0]->cnt;
        }
    }

    function total($table, $field, $keyword)
    {
        $sql = "select count(*) numrows from $table where $field like '%$keyword%' ";
        $query = $this->db->query($sql);
        if (($query->row_array()) == null) {
            return 0;
        } else {
            $result = $query->result_array();
            return $result;
        }
    }

    /************************
     * 数据库操作模型部分结束
     ***********************/

    /************************
     * 权限模型部分
     ***********************/

    /** 根据perm_type 获取关联的基础表名称
     * @return array
     */
    function getBaseTable($perm_type)
    {
        $sql = "select r_table from sys_perm_type where type = '" . $perm_type . "'";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     *  根据$userId, perm_type 获取 perm_id,perm_type,r_id
     * @return array
     */
    function getPerm($basetable, $userId, $perm_type, $menuCtrl)
    {
        $hasCtrl = $menuCtrl ? "" : " WHERE basetbl.type != 2";

        $sql = "SELECT
                       DISTINCT t.id perm_id,
                        basetbl.*
                    FROM
                        (
                            SELECT
                                p.*
                            FROM
                                sys_user_role ur,
                                sys_role_perm rp,
                                sys_perm p,
			                    sys_role r
                            WHERE
                                ur.user_id = $userId
                            AND rp.role_id = ur.role_id
                            AND p.id = rp.perm_id
                            AND r.id = ur.role_id
                            AND r.status=1
                            AND p.perm_type = '" . $perm_type . "'
                        ) t
                    LEFT JOIN " . $basetable . " basetbl ON t.r_id = basetbl.id" . $hasCtrl . " order by basetbl.listorder";

        $query = $this->db->query($sql);
        return $query->result_array();

    }

    function getCtrlPerm($userId)
    {
        $sql = "SELECT
                        basetbl.path
                    FROM
                        (
                            SELECT
                                p.*
                            FROM
                                sys_user_role ur,
                                sys_role_perm rp,
                                sys_perm p,
			                    sys_role r
                            WHERE
                                ur.user_id = $userId
                            AND rp.role_id = ur.role_id
                            AND p.id = rp.perm_id
                            AND r.id = ur.role_id
                            AND r.status=1
                            AND p.perm_type = 'menu'
                        ) t
                    LEFT JOIN  sys_menu basetbl ON t.r_id = basetbl.id 
                    WHERE basetbl.type = 2";

        $query = $this->db->query($sql);
        return $query->result_array();
    }


    /**
     * 判断 token 是否过期
     * // 50008:非法的token; 50012:其他客户端登录了;  50014:Token 过期了;
     */
    function TokenExpired($token)
    {
        $sql = "SELECT
                *
            FROM
                `sys_user_token`
            WHERE
                token = '$token'";
        $query = $this->db->query($sql);
        $Arr = $query->result_array();
        if (empty($Arr)) {
            return ['code' => 50008, 'message' => "非法的token"];
        }

        $now = time();
        if ($now > $Arr[0]['expire_time']) {
            return ['code' => 50014, 'message' => "Token 过期了"];
        }

        // TODO: 当token合法时，自动续期?
        $update_time = time();
        $expire_time = $update_time + 2 * 60 * 60;  // 2小时过期
        $data = [
            'expire_time' => $expire_time,
            'last_update_time' => $update_time
        ];
        $this->_update_key('sys_user_token', $data, ['token' => $token]);

        return ['code' => 20000, 'message' => "Token 合法"];
    }

    /***********************
     * 权限模型部分结束
     ***********************/

    /************************
     * 菜单模型部分
     ************************/

    /**
     * 菜单是否拥有子节点
     */
    function hasChildMenu($id)
    {
        $sql = "SELECT getChildLst(" . $id . ") children";
        $query = $this->db->query($sql);
        // var_dump($query->result_array()[0]["children"]);
        // string(14) "$,2,5,6,8,9,10"
        $array = explode(",", $query->result_array()[0]["children"]);

        if (count($array) > 2) {
            return true;
        } else {
            return false;
        }
    }

    /***********************
     * 菜单模型部分结束
     ***********************/
}