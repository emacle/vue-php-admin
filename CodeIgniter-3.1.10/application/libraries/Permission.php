<?php

defined('BASEPATH') or exit('No direct script access allowed');

use Nette\Utils\Strings;

/**
 * Permission Class
 *
 *  基础权限类，及生成树类
 *
 */
class Permission
{

    private $idKey = 'id';            //主键的键名
    private $fidKey = 'fid';        //父ID的键名
    private $root = 0;                //最顶层fid
    private $pId = 0;                //父fid
    private $data = array();        //源数据
    private $treeArray = array();    //属性数组
    private $state = 'closed';        //默认关闭


    /**
     * 获得一个带children的树形数组
     * @return multitype:
     */
    public function getTreeArray($data, $idKey, $fidKey, $root, $closed = '0')
    {
        if ($idKey) $this->idKey = $idKey;
        if ($fidKey) $this->fidKey = $fidKey;
        if ($root) $this->root = $root;

        if ($data) {
            //var_dump($data);
            $this->data = $data;
            $this->getChildren($this->root, $closed);
        }

        //去掉键名
        //var_dump($this->treeArray);
        return array_values($this->treeArray);
    }

    /**
     * @param int $root 父id值
     * @return null or array
     */
    private function getChildren($root, $closed)
    {
        $children = '';

        foreach ($this->data as &$node) {
            if ($root == $node[$this->fidKey]) {
                $node['children'] = $this->getChildren($node[$this->idKey], $closed);
                $children[] = $node;
            }
            //只要一级节点
            if ($this->root == $node[$this->fidKey]) {
                //$s=array('state'=>'close');
                //array_push($node,'close');
                if ($closed) {
                    $node['state'] = $this->state;
                }

                $this->treeArray[$node[$this->idKey]] = $node;
            }
        }
        return $children;
    }

    // /**
    //  * 只解析 $payload 部分, 不作权限验证
    //  * @param $jwt
    //  * @return bool|mixed
    //  *  stdClass Object
    //  *   (
    //  *    [iss] => http://www.helloweba.net
    //  *    [aud] => http://www.helloweba.net
    //  *    [iat] => 1577668094
    //  *    [nbf] => 1577668094
    //  *    [exp] => 1577668094
    //  *   [user_id] => 2
    //  *   [count] => 0
    //  *  )
    //  *
    //  */
    // function parseJWT($jwt)
    // {
    //     if (isset($jwt)) {
    //         $tks = explode('.', $jwt); // jwt token . 分 第2段为 $payload
    //         if (count($tks) != 3) {
    //             return FALSE;
    //         } else {
    //             $decoded_obj = json_decode(base64_decode($tks[1]));
    //             return $decoded_obj;
    //         }
    //     } else {
    //         return FALSE;
    //     }
    // }

    /**
     * 根据 $userId ， 权限类型 获取该 userid->role 对应的所有权限
     * @parms，$type 根据$perm_type = 'menu'时，判断是否菜单带有功能控件
     * return array
     */
    function getPermission($userId, $perm_type, $menuCtrl = true)
    {
        $CI = &get_instance();
        $CI->load->model('Base_model');

        $BasetblArr = $CI->Base_model->getBaseTable($perm_type);
        if (empty($BasetblArr)) {
            var_dump($this->uri->uri_string . '$this->Base_model->getBaseTable 获取基础表失败...');
            return;
        }
        $tblname = $BasetblArr[0]['r_table'];

        $PermArr = $CI->Base_model->getPerm($tblname, $userId, $perm_type, $menuCtrl);

        return $PermArr;
    }

    /**
     * @parms， 根据$userId 获取拥有的菜单功能按钮控件权限 与vue前台perm.js 配合判断按钮隐藏与否
     * return array
     */
    function getMenuCtrlPerm($userId)
    {
        $CI = &get_instance();
        $CI->load->model('Base_model');
        $PermArr = $CI->Base_model->getCtrlPerm($userId);
        // 返回样例
        //        array(2) {
        //        [0]=>
        //          array(1) {
        //            ["path"]=>
        //            string(13) "/sys/menu/add"
        //          }
        //          [1]=>
        //          array(1) {
        //            ["path"]=>
        //            string(14) "/sys/menu/edit"
        //          }
        //        }
        return $PermArr;
    }

    /**
     * 后端根据 $userId,$uri 判断是否该用户是否过期及拥有的功能按钮控件操作权限
     * 增/删/改/查 控制器引用
     * @parms $userId,$uri
     * return Array
     * return ['code' => 50008, 'message' => "非法的token"];
     * return ['code' => 50014, 'message' => "Token 过期了"];
     * return ['code' => 50016, 'message' => "无操作权限"];
     * return ['code' => 50000, 'message' => "有操作权限"];
     */
    function HasPermit($userId, $uri, $http_method)
    {
        $CI = &get_instance();
        $CI->load->model('Base_model');
        // 50008:非法的token; 50012:其他客户端登录了;  50014:Token 过期了;
        //        $tokenArr = $CI->Base_model->TokenExpired($userId);
        //
        //        if ($tokenArr['code'] != 20000) {
        //            return $tokenArr;
        //        }

        $PermArr = $CI->Base_model->getCtrlPerm($userId);
        // getCtrlPerm 返回样例
        //        array(2) {
        //        [0]=>
        //          array(1) {
        //            ["path"]=>
        //            string(13) "/sys/menu/add"
        //          }
        //          [1]=>
        //          array(1) {
        //            ["path"]=>
        //            string(14) "/sys/menu/edit"
        //          }
        //        }
        if (empty($PermArr)) {
            return ['code' => 50016, 'message' => "无操作权限 " . $uri, 'data' => $PermArr];
        }

        // var_dump($uri); // string(19) "api/v2/sys/menu/add"
        // var_dump($PermArr);
        // api/v2/menu/menus/id/2   '/sys/menu/menus/get'
        foreach ($PermArr as $k => $v) {
            // var_dump($v['path']);
            // $res = explode("/",'/sys/menu/menus/get');
            // // var_dump($res);
            // var_dump(array_pop($res)); // 获取最后一个元素，并且原数组删除最后一个
            // var_dump($res);
            // var_dump(implode("/",$res));

            $res = explode("/", $v['path']);
            $http_method_db = array_pop($res); // 获取最后一个元素，并且原数组删除最后一个

            $uri_db = implode("/", $res);

            // var_dump($http_method_db);

            // var_dump($uri_db);

            if (Strings::contains($uri, $uri_db) && $http_method === $http_method_db) {
                return ['code' => 50000, 'message' => "有操作权限 " . $uri, 'data' => $PermArr];
            }
        }
        return ['code' => 50016, 'message' => "无操作权限1 " . $uri, 'data' => $PermArr];
    }

    /**
     * 将数据格式化成树形结构路由菜单
     */
    function genVueRouter($data, $idKey, $fidKey, $pId)
    {
        $tree = array();
        foreach ($data as $k => $v) {
            // 找到父节点为$pId的节点，然后进行递归查找其子节点，
            if ($v[$fidKey] == $pId) {
                // 数据库取出为string类型，强制类型转换成整形，方便前端使用
                isset($v['id']) ? $v['id'] = intval($v['id']) : '';
                isset($v['pid']) ? $v['pid'] = intval($v['pid']) : '';
                isset($v['type']) ? $v['type'] = intval($v['type']) : '';
                isset($v['hidden']) ? $v['hidden'] = intval($v['hidden']) : '';
                isset($v['listorder']) ? $v['listorder'] = intval($v['listorder']) : '';

                // 构造 vue-admin 路由结构 meta
                $v['meta'] = [
                    'title' => $v['title'],
                    'icon' => $v['icon']
                ];

                unset($v['title']);
                unset($v['icon']);

                $v['children'] = $this->genVueRouter($data, $idKey, $fidKey, $v[$idKey]);
                $tree[] = $v;     // 循环数组添加元素 属于同一层级
            }
        }
        // print_r($tree);
        return $tree;
    }

    // 菜单管理 -> 菜单列表
    function genVueMenuTree($data, $idKey, $fidKey, $pId)
    {
        $tree = array();
        foreach ($data as $k => $v) {
            // 找到父节点为$pId的节点，然后进行递归查找其子节点，
            if ($v[$fidKey] == $pId) {
                // 数据库取出为string类型，强制类型转换成整形，方便前端使用
                isset($v['id']) ? $v['id'] = intval($v['id']) : '';
                isset($v['pid']) ? $v['pid'] = intval($v['pid']) : '';
                isset($v['type']) ? $v['type'] = intval($v['type']) : '';
                isset($v['hidden']) ? $v['hidden'] = intval($v['hidden']) : '';
                isset($v['listorder']) ? $v['listorder'] = intval($v['listorder']) : '';

                $v['children'] = $this->genVueMenuTree($data, $idKey, $fidKey, $v[$idKey]);

                // vue treeselect 组件子节点为空时会列出，将空的子节点删除 children key.
                if (empty($v['children'])) {
                    unset($v['children']);
                }
                $tree[] = $v;     // 循环数组添加元素 属于同一层级
                //   print_r($tree);
            }
        }
        return $tree;
    }

    // 生成部门机构树
    function genDeptTree($data, $idKey, $fidKey, $pId)
    {
        $tree = array();
        foreach ($data as $k => $v) {
            // 找到父节点为$pId的节点，然后进行递归查找其子节点，
            if ($v[$fidKey] == $pId) {
                // 数据库取出为string类型，强制类型转换成整形，方便前端使用
                isset($v['id']) ? $v['id'] = intval($v['id']) : '';
                isset($v['pid']) ? $v['pid'] = intval($v['pid']) : '';
                isset($v['listorder']) ? $v['listorder'] = intval($v['listorder']) : '';

                $v['children'] = $this->genDeptTree($data, $idKey, $fidKey, $v[$idKey]);
                // vue treeselect 组件子节点为空时会列出，将空的子节点删除 children key.
                if (empty($v['children'])) {
                    unset($v['children']);
                }
                $tree[] = $v;     // 循环数组添加元素 属于同一层级
            }
        }
        return $tree;
    }

    /**
     * 指定格式两个二维数组比较差集
     * @param $array1
     * @param $array2
     * @return array
     */
    // $arr1 = [
    // ['role_id'=>1,'perm_id'=>1],
    // ['role_id'=>1,'perm_id'=>2]
    // ];
    function array_diff_assoc2($array1, $array2)
    {
        $ret = array();
        foreach ($array1 as $k => $v) {
            #               var_dump($v);
            $isExist = false;
            foreach ($array2 as $k2 => $v2) {
                if (empty(array_diff_assoc($v, $v2))) {
                    $isExist = true;
                    break;
                }
            }
            if (!$isExist) array_push($ret, $v);
        }
        return $ret;
    }

    /**
     * 将数据格式化成树形结构
     */
    function genTree($data, $idKey, $fidKey, $pId)
    {
        //        $tree = '';
        $tree = array();
        foreach ($data as $k => $v) {
            // 找到父节点为$pId的节点，然后进行递归查找其子节点，
            // 同时将子节点赋值至该节点的'children'元素，同时判断是否叶子节点
            if ($v[$fidKey] == $pId) {
                $v['children'] = $this->genTree($data, $idKey, $fidKey, $v[$idKey]);
                //   print_r($pId);
                //                $v['isLeaf']=$v['children']?0:1;
                //                $v['state']=$v['children']?'closed':'open';
                $tree[] = $v;     // 循环数组添加元素 属于同一层级
                //                print_r($v);
                //   print_r($tree);
            }
        }
        return $tree;
    }

    /**
     * 将数据格式化成树形结构 ___非递归方式，使用了数组指针，与前面json方式一样，array 必须以1开头___
     * @author Xuefen.Tong
     * @param array $items
     * @return array
     */
    function genTree9($items)
    {
        $tree = array(); //格式化好的树
        foreach ($items as $item)
            if (isset($items[$item['pid']]))
                $items[$item['pid']]['son'][] = &$items[$item['id']];
            else
                $tree[] = &$items[$item['id']];
        return $tree;
    }

    /**
     * 获取全部机构
     * TODO：根据当前USERID 获取用户最高级机构所有下属机构
     */
    function getDept()
    {
        $CI = &get_instance();
        $CI->load->model('Dept_model', 'Dept');

        $array = $CI->Dept->getDeptids();
        //            var_dump($array);
        $max = 10000;
        $j = 0;
        for ($i = 0; $i < count($array); $i++) {
            //                var_dump($array[$i]->DeptId);
            $arr = $CI->Dept->getDeptFatherId($array[$i]->DeptId);
            //                var_dump($arr[0]->FatherLst);
            //                var_dump(explode(",",$arr[0]->FatherLst));
            //                var_dump(count(explode(",",$arr[0]->FatherLst)));
            $fid_length = count(explode(",", $arr[0]->FatherLst));

            if ($fid_length < $max) {
                $max = $fid_length;
                $j = $i;  // 指针父机构最小长度
            }
        }

        $fdeptid = $array[$j]->DeptId;  // 最高机构ID
        $data = $CI->Dept->getOptDept($fdeptid);  //获取下属所有机构树

        $ret = $CI->Dept->getFahterId($fdeptid); //获取最高机构直属父ID作为终止节点
        $root = $ret[0]->FatherId;   // treelib 需要终止节点
        //            $root 最顶层fid
        //            $b=$this->treelib->getTreeArray($data,'Id','FatherId',$root,1);

        //            $b=$this->treelib->getTreeArray($data,'Id','FatherId',$root,1);
        //            $b=str_replace(',"state":"closed","children":""',',"state":"open","children":""',json_encode($b));
        //            echo json_encode($b);

        //            $b=$this->genTree($data,'Id','FatherId',$root);

        $b = $this->genTree($data, 'id', 'FatherId', $root);
        echo json_encode($b);
    }
}
