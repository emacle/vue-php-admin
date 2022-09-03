<?php
defined('BASEPATH') or exit('No direct script access allowed');

use \Firebase\JWT\JWT;
use chriskacerguis\RestServer\RestController;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

// Using Medoo namespace
use Medoo\Medoo;

class User extends RestController
{
    private $Medoodb;

    function __construct()
    {
        parent::__construct();
        $this->load->model('Base_model');
        $this->load->model('User_model');
        // Initialize
        $this->Medoodb = new Medoo(config_item('medoodb'));
    }

    // 重写（覆盖）RestController 中的方法 early_checks()
    // 参数带有表单密码明文时 {username:'xxx', password:'xxx'} 时， 密码进行加密处理，防止数据库明文保存密码信息
    protected function early_checks()
    {
        parent::early_checks(); // 调用父类中被本方法覆盖掉的方法

        if (array_key_exists('password', $this->_args)) {
            $this->_args['password'] = md5($this->_args['password']); // 明文密码加密处理, 在原有的功能基础上多加一点功能
        }
        if (array_key_exists('password_orig', $this->_args)) {
            $this->_args['password_orig'] = md5($this->_args['password_orig']); // 明文密码加密处理, 在原有的功能基础上多加一点功能
        }
        if (array_key_exists('password_confirmation', $this->_args)) {
            $this->_args['password_confirmation'] = md5($this->_args['password_confirmation']); // 明文密码加密处理, 在原有的功能基础上多加一点功能
        }
    }

    public function index_get()
    {
        $this->load->view('login_view');
    }

    // 签发Token
    public function issue_get()
    {
        // var_dump(JWT::$leeway);
        $key = '344'; //key
        $time = time(); //当前时间
        $payload = [
            'iss' => 'http://www.helloweba.net', //签发者 可选
            'aud' => 'http://www.helloweba.net', //接收该JWT的一方，可选
            'iat' => $time, //签发时间
            'nbf' => $time, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
            'exp' => $time + 2 * 60 * 60, //过期时间,这里设置2个小时
            'data' => [ //自定义信息，不要定义敏感信息
                'userid' => 2,
                'username' => '李小龙'
            ]
        ];
        echo JWT::encode($payload, $key); //输出Token
    }

    public function verification_get()
    {
        $key = '123456'; //key要和签发的时候一样
        var_dump($key);
        //签发的Token header.payload.signature 前两部分可以base64解密
        // $jwt = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC93d3cuaGVsbG93ZWJhLm5ldCIsImF1ZCI6Imh0dHA6XC9cL3d3dy5oZWxsb3dlYmEubmV0IiwiaWF0IjoxNTc3NjY4MDk0LCJuYmYiOjE1Nzc2NjgwOTQsImV4cCI6MTU3NzY2ODA5NCwiZGF0YSI6eyJ1c2VyaWQiOjIsInVzZXJuYW1lIjoiXHU2NzRlXHU1YzBmXHU5Zjk5In19.EM9G8aW7DCpRYW7L0vjTgTt7UevwIyocVaouq0rdn0I";

        $jwt = $this->get('token');
        var_dump(sscanf('Bearer ' . $jwt, 'Bearer %s'));
        var_dump($jwt);
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        var_dump($bodyb64);
        $arr = json_decode(base64_decode($bodyb64),true);
        var_dump($arr);
        var_dump($arr['exp']);
        var_dump(time());
        var_dump($arr['exp']>time());

        //        $arr = explode('.', $jwt);
        //        var_dump($arr);
        //        var_dump(base64_decode($arr[1]));
        //        $object = json_decode(base64_decode($arr[1]));
        //        var_dump($object->data);
        //        // var_dump($object->data->username);
        //        return;
        try {
            $decoded = JWT::decode($jwt, $key, ['HS256']); //HS256方式，这里要和签发的时候对应
            $arr = (array) $decoded;
            print_r($arr);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
            echo $e->getMessage();
        } catch (\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
            echo $e->getMessage();
        } catch (\Firebase\JWT\ExpiredException $e) {  // token过期
            echo $e->getMessage();
        } catch (Exception $e) {  //其他错误
            echo $e->getMessage();
        }
        //Firebase定义了多个 throw new，我们可以捕获多个catch来定义问题，catch加入自己的业务，比如token过期可以用当前Token刷新一个新Token
    }

    public function testapi_get()
    {

        echo "test api ok...\n\n";

        // echo APPPATH . "\n";
        // echo SELF . "\n";
        // echo BASEPATH . "\n";
        // echo FCPATH . "\n";
        // echo SYSDIR . "\n";
        // var_dump($this->config->item('rest_language'));
        // var_dump($this->config->item('language'));

        // var_dump($this->config);

        // //////////////// GuzzleHttp 测试 ///////////////////////
        // $client = new \GuzzleHttp\Client();
        // $response = $client->request('GET', 'https://api.github.com/repos/guzzle/guzzle');

        // echo $response->getStatusCode(); // 200
        // echo $response->getHeaderLine('content-type'); // 'application/json; charset=utf8'
        // echo $response->getBody(); // '{"id": 1420053, "name": "guzzle", ...}'

        // // Send an asynchronous request. 异步请求
        // $request = new \GuzzleHttp\Psr7\Request('GET', 'https://jsonplaceholder.typicode.com/todos');
        // $promise = $client->sendAsync($request)->then(function ($response) {
        //     // echo 'I completed! ' . $response->getStatusCode();
        //     // echo 'I completed! ' . $response->getHeaderLine('content-type');
        //     // echo 'I completed! ' . $response->getBody();
        //     var_dump(json_decode($response->getBody(), true));   // json String => array , 有些json 网址 返回字符串不规范前面有 #xfeff 字
        // });

        // $promise->wait();

        // //////////////// phpinfo 测试 ///////////////////////
        // phpinfo();

        // /////////////// 数据库连接测试 ///////////////////////
        // //  有结果表明数据库连接正常 reslut() 与 row_array 结果有时不太一样
        // //  一般加载到时model里面使用。
        // $this->load->database();
        // $query = $this->db->query("show tables");
        // var_dump($query);
        // var_dump($query->result());

    }

    /* Helper Methods */
    /**
     * 生成 token
     * @param
     * @return string 40个字符
     */
    private function _generate_token()
    {
        do {
            // Generate a random salt
            $salt = base_convert(bin2hex($this->security->get_random_bytes(64)), 16, 36);

            // If an error occurred, then fall back to the previous method
            if ($salt === false) {
                $salt = hash('sha256', time() . mt_rand());
            }

            $new_key = substr($salt, 0, config_item('rest_key_length'));
        } while ($this->_token_exists($new_key));

        return $new_key;
    }

    /* Private Data Methods */

    private function _token_exists($token)
    {
        return $this->rest->db
            ->where('token', $token)
            ->count_all_results('sys_user_token') > 0;
    }

    private function _insert_token($token, $data)
    {
        $data['token'] = $token;

        return $this->rest->db
            ->set($data)
            ->insert('sys_user_token');
    }

    private function _update_token($token, $data)
    {
        return $this->rest->db
            ->where('token', $token)
            ->update('auth', $data);
    }

    // 查
    function users_get()
    {
        // GET /users?offset=1&limit=20&fields=id,username,email,listorder&sort=-listorder,+id&query=~username,status&username=admin&status=1
        // fields: 显示字段参数过滤配置,不设置则为全部
        $fields = $this->get('fields');
        $fields ? $columns = explode(",", $fields) : $columns = "*";
        // 显示字段过滤配置结束

        // GET /users?offset=1&limit=20&fields=id,username,email,listorder&sort=-listorder,+id&query=~username,status&username=admin&status=1
        // 分页参数配置
        $limit = $this->get('limit') ? $this->get('limit') : 10;
        $offset = $this->get('offset') ?  ($this->get('offset') - 1) *  $limit : 0; // 第几页
        $where = [
            "LIMIT" => [$offset, $limit]
        ];
        // 分页参数配置结束

        // GET /users?offset=1&limit=20&fields=id,username,email,listorder&sort=-listorder,+id&query=~username,status&username=admin&status=1
        // 存在排序参数则 获取排序参数 加入 $where，否则不添加ORDER条件
        $sort = $this->get('sort');
        if ($sort) {
            $where["ORDER"] = [];
            $sortArr = explode(",", $sort);
            foreach ($sortArr as $k => $v) {
                if (Strings::startsWith($v, '-')) { // true DESC
                    $key = Strings::substring($v, 1); //  去 '-'
                    $where["ORDER"][$key] = "DESC";
                } else {
                    $key = Strings::substring($v, 1); //  去 '+'
                    $where["ORDER"][$key] = "ASC";
                }
            }
        }
        // 排序参数结束

        // GET /users?offset=1&limit=20&fields=id,username,email,listorder&sort=-listorder,+id&query=~username,status&username=admin&status=1
        // 指定条件模糊或搜索查询,author like %pocoyo%, status=1 此时 total $wherecnt 条件也要发生变化
        // 查询字段及字段值获取
        // 如果存在query 参数以,分隔，且每个参数的有值才会增加条件
        $wherecnt = []; // 计算total使用条件，默认为全部
        $query = $this->get('query');
        if ($query) { // 存在才进行过滤,否则不过滤
            $queryArr = explode(",", $query);
            foreach ($queryArr as $k => $v) {
                if (Strings::startsWith($v, '~')) { // true   query=~username&status=1 以~开头表示模糊查询
                    $tmpKey = Strings::substring($v, 1); // username

                    $tmpValue = $this->get($tmpKey);
                    if (!is_null($tmpValue)) {
                        $where[$tmpKey . '[~]'] = $tmpValue;
                        $wherecnt[$tmpKey . '[~]'] = $tmpValue;
                    }
                } else {
                    $tmpValue = $this->get($v);
                    if (!is_null($tmpValue)) {
                        $where[$v] = $tmpValue;
                        $wherecnt[$v] = $tmpValue;
                    }
                }
            }
        }
        // 查询字段及字段值获取结束

        // 执行查询
        $UserArr = $this->Medoodb->select(
            "sys_user",
            $columns,
            $where
        );

        $sqlCmd = $this->Medoodb->log()[0];

        // 捕获错误信息
        $err = $this->Medoodb->error();
        // array(3) => ["42S02", 1146, "Table 'vueadminv2.articlex' doesn't exist"]
        if ($err[1]) { // 如果出错 否则为空
            // var_dump($err[2]);
            // var_dump($this->Medoodb->log());
            $message = [
                "code" => 20400,
                "data" => $err[2]
            ];
            $this->response($message, RestController::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        // 获取记录总数
        $total = $this->Medoodb->count("sys_user",  $wherecnt);

        // 遍历该用户所属角色信息
        foreach ($UserArr as $k => $v) {
            $UserArr[$k]['role'] = [];
            $RoleArr = $this->User_model->getUserRoles($v['id']);
            foreach ($RoleArr as $kk => $vv) {
                array_push($UserArr[$k]['role'], intval($vv['id'])); // 字符串转数字 前端treeselect value与option 的id 必须类型一致
            }
        }

        // 遍历该用户所属部门信息
        foreach ($UserArr as $k => $v) {
            $UserArr[$k]['dept'] = [];
            $DeptArr = $this->User_model->getUserDepts($v['id']);
            foreach ($DeptArr as $kk => $vv) {
                array_push($UserArr[$k]['dept'], intval($vv['dept_id'])); // 字符串转数字 前端treeselect value与option 的id 必须类型一致
            }
        }

        $message = [
            "code" => 20000,
            "data" => [
                'items' => $UserArr,
                'total' => $total,
                "sql" => $sqlCmd
            ]
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    function getroleoptions_get()
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

        $RoleArr = $this->User_model->getRoleOptions($jwt_object->user_id);
        // string to boolean / number
        foreach ($RoleArr as $k => $v) {
            $v['isDisabled'] === 'true' ? ($RoleArr[$k]['isDisabled'] = true) : ($RoleArr[$k]['isDisabled'] = false);
            isset($v['id']) ? $RoleArr[$k]['id'] = intval($v['id']) : $RoleArr[$k]['id'] = '';
        }

        $message = [
            "code" => 20000,
            "data" => $RoleArr,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    function getdeptoptions_get()
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

        $DeptArr = $this->User_model->getDeptOptions($jwt_object->user_id);
        // string to boolean / number
        foreach ($DeptArr as $k => $v) {
            $v['isDisabled'] === 'true' ? ($DeptArr[$k]['isDisabled'] = true) : ($DeptArr[$k]['isDisabled'] = false);
            isset($v['id']) ? $DeptArr[$k]['id'] = intval($v['id']) : $DeptArr[$k]['id'] = '';
        }

        // 转换成树型结构, id,pid 为数字形式
        $DeptTree = $this->permission->genDeptTree($DeptArr, 'id', 'pid', 0);

        $message = [
            "code" => 20000,
            "data" => $DeptTree,
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 增
    function users_post()
    {
        $parms = $this->post();  // 获取表单参数，类型为数组

        // 参数数据预处理
        $RoleArr = $parms['role'];
        unset($parms['role']);    // 剔除role数组
        $DeptArr = $parms['dept'];
        unset($parms['dept']);    // 剔除role数组

        // 加入新增时间
        $parms['create_time'] = time();
        $parms['password'] = md5($parms['password']);

        $user_id = $this->Base_model->_insert_key('sys_user', $parms);
        if (!$user_id) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['username'] . ' - 用户新增失败'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 处理关联角色
        $failed = false;
        $failedArr = [];
        foreach ($RoleArr as $k => $v) {
            $arr = ['user_id' => $user_id, 'role_id' => $v];
            $ret = $this->Base_model->_insert_key('sys_user_role', $arr);
            if (!$ret) {
                $failed = true;
                array_push($failedArr, $arr);
            }
        }

        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '用户关联角色失败 ' . json_encode($failedArr)
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 处理关联部门
        $failed = false;
        $failedArr = [];
        foreach ($DeptArr as $k => $v) {
            $arr = ['user_id' => $user_id, 'dept_id' => $v];
            $ret = $this->Base_model->_insert_key('sys_user_dept', $arr);
            if (!$ret) {
                $failed = true;
                array_push($failedArr, $arr);
            }
        }

        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '用户关联部门失败 ' . json_encode($failedArr)
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['username'] . ' - 用户新增成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 改
    function users_put()
    {
        $parms = $this->put();  // 获取表单参数，类型为数组

        // 参数检验/数据预处理
        // 超级管理员角色不允许修改
        if ($parms['id'] == 1) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['username'] . ' - 超级管理员用户不允许修改'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $id = $parms['id'];
        $RoleArr = [];
        foreach ($parms['role'] as $k => $v) {
            $RoleArr[$k] = ['user_id' => $id, 'role_id' => $v];
        }

        $DeptArr = [];
        foreach ($parms['dept'] as $k => $v) {
            $DeptArr[$k] = ['user_id' => $id, 'dept_id' => $v];
        }

        unset($parms['role']);  // 剔除role数组
        unset($parms['dept']);  // 剔除dept数组
        unset($parms['id']);    // 剔除索引id
        unset($parms['password']);    // 剔除密码

        $where = ["id" => $id];

        if (!$this->Base_model->_update_key('sys_user', $parms, $where)) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => $parms['username'] . ' - 用户更新错误'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 处理角色数组编辑操作
        $RoleSqlArr = $this->User_model->getRolesByUserId($id);

        $AddArr = $this->permission->array_diff_assoc2($RoleArr, $RoleSqlArr);
        // var_dump('------------只存在于前台传参 做添加操作-------------');
        // var_dump($AddArr);
        $failed = false;
        $failedArr = [];
        foreach ($AddArr as $k => $v) {
            $ret = $this->Base_model->_insert_key('sys_user_role', $v);
            if (!$ret) {
                $failed = true;
                array_push($failedArr, $v);
            }
        }
        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '用户关联角色失败 ' . json_encode($failedArr)
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $DelArr = $this->permission->array_diff_assoc2($RoleSqlArr, $RoleArr);
        // var_dump('------------只存在于后台数据库 删除操作-------------');
        // var_dump($DelArr);
        $failed = false;
        $failedArr = [];
        foreach ($DelArr as $k => $v) {
            $ret = $this->Base_model->_delete_key('sys_user_role', $v);
            if (!$ret) {
                $failed = true;
                array_push($failedArr, $v);
            }
        }
        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '用户关联角色失败 ' . json_encode($failedArr)
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 处理部门数组编辑操作
        $DeptSqlArr = $this->User_model->getDeptsByUserId($id);

        $AddArr = $this->permission->array_diff_assoc2($DeptArr, $DeptSqlArr);
        // var_dump('------------只存在于前台传参 做添加操作-------------');
        // var_dump($AddArr);
        $failed = false;
        $failedArr = [];
        foreach ($AddArr as $k => $v) {
            $ret = $this->Base_model->_insert_key('sys_user_dept', $v);
            if (!$ret) {
                $failed = true;
                array_push($failedArr, $v);
            }
        }
        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '用户关联部门失败 ' . json_encode($failedArr)
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $DelArr = $this->permission->array_diff_assoc2($DeptSqlArr, $DeptArr);
        // var_dump('------------只存在于后台数据库 删除操作-------------');
        // var_dump($DelArr);
        $failed = false;
        $failedArr = [];
        foreach ($DelArr as $k => $v) {
            $ret = $this->Base_model->_delete_key('sys_user_dept', $v);
            if (!$ret) {
                $failed = true;
                array_push($failedArr, $v);
            }
        }
        if ($failed) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '用户关联部门失败 ' . json_encode($failedArr)
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => $parms['username'] . ' - 用户更新成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // 删
    function users_delete($id)
    {
        // $parms = $this->delete(); // delete() 不能使用此方法获取表单参数，根据规范只能使用 url /sys/dept/depts/2 传参方式
        // 参数检验/数据预处理
        // 超级管理员用户不允许删除
        if ($id == 1) {
            $message = [
                "code" => 20000,
                "type" => 'error',
                "message" => '超级管理员不允许删除'
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 删除外键关联表 sys_user_role, sys_user_dept
        $this->Base_model->_delete_key('sys_user_role', ['user_id' => $id]);
        $this->Base_model->_delete_key('sys_user_dept', ['user_id' => $id]);

        // 删除基础表 sys_user
        if (!$this->Base_model->_delete_key('sys_user', ['id' => $id])) {
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

    function login_post()
    {
        $username = $this->post('username'); // POST param
        $password = $this->post('password'); // POST param

        $result = $this->User_model->validate($username, md5($password));

        // 用户名密码正确 生成token 返回
        if ($result['success']) {
            //            $Token = $this->_generate_token();
            //            $create_time = time();
            //            $expire_time = $create_time + 2 * 60 * 60;  // 2小时过期
            //
            //            $data = [
            //                'user_id' => $result['userinfo']['id'],
            //                'expire_time' => $expire_time,
            //                'create_time' => $create_time
            //            ];
            //
            //            if (!$this->_insert_token($Token, $data)) {
            //                $message = [
            //                    "code" => 20000,
            //                    "message" => 'Token 创建失败, 请联系管理员.'
            //                ];
            //                $this->response($message, RestController::HTTP_OK);
            //            }
            $userInfo = $result['userinfo'];

            $time = time(); //当前时间

            // 公用信息
            $payload = [
                'iat' => $time, //签发时间
                'nbf' => $time, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
                'user_id' => $userInfo['id'], //自定义信息，不要定义敏感信息, 一般只有 userId 或 username
            ];

            $access_token = $payload;
            $access_token['scopes'] = 'role_access'; //token标识，请求接口的token
            $access_token['exp'] = $time + config_item('jwt_access_token_exp'); //access_token过期时间,这里设置2个小时

            $refresh_token = $payload;
            $refresh_token['scopes'] = 'role_refresh'; //token标识，刷新access_token
            $refresh_token['exp'] = $time + config_item('jwt_refresh_token_exp'); //refresh_token,这里设置30天
            $refresh_token['count'] = 0; // 刷新TOKEN计数, 在刷新token期间多次请求刷新token则表示活跃,可以重新生成刷新token以免刷新token过期后登录

            $message = [
                "code" => 20000,
                "data" => [
                    "token" => JWT::encode($access_token, config_item('jwt_key')), //生成access_tokenToken,
                    "refresh_token" => JWT::encode($refresh_token, config_item('jwt_key')) //生成refresh_token,
                ]
            ];
            $this->response($message, RestController::HTTP_OK);
        } else {
            $message = [
                "code" => 60204,
                "message" => 'Account and password are incorrect.'
            ];
            $this->response($message, RestController::HTTP_OK);
        }
    }

    // 使用 oauth2-github 官方包来进行 github 认证登录
    // 配置参数不用配置 urlAuthorize/access_token/urlResourceOwnerDetails
    function githubauth_get()
    {
        $code = $this->get('code');
        $state = $this->get('state');

        // 需要正确配置github client ID, Secret, redirect_uri
        // $client_id = 'xxxxxx';
        // $client_secret = 'xxxxxx';
        // $redirect_uri ='http://localhost:9527/auth-redirect';
        // 这里redirect_uri 应与github网站配置尽量保持一至，因为手工封包的时候容易出现 在通过authcode获取accesstoken的时候容易
        // 漏掉redirct_uri参数，而使用github默认值，可能导致校验出错

        // composer require league/oauth2-github
        $provider = new League\OAuth2\Client\Provider\Github([
            'clientId' => $client_id,    // The client ID assigned to you by the provider
            'clientSecret' => $client_secret,   // The client password assigned to you by the provider
            'redirectUri' => $redirect_uri,
        ]);

        // If we don't have an authorization code then get one
        if (!isset($code)) {
            // 没有 code 参数, 生成授权链接 AuthorizationUrl 前返回前端
            //  https://github.com/login/oauth/authorize?state=137caabc2b409f0cccd14834fc848041&response_type=code&approval_prompt=auto&redirect_uri=http://localhost:9527/auth-redirect&client_id=94aae05609c96ffb7d3b
            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorizationUrl = $provider->getAuthorizationUrl();

            // Get the state generated for you and store it to the session.
            $_SESSION['oauth2state'] = $provider->getState();

            // Redirect the user to the authorization URL.
            // header('Location: ' . $authorizationUrl);
            // exit;
            $message = [
                "code" => 20000,
                "data" => ['auth_url' => $authorizationUrl],
            ];
            $this->response($message, RestController::HTTP_OK);

            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($state) || (isset($_SESSION['oauth2state']) && $state !== $_SESSION['oauth2state'])) {

            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }

            exit('Invalid state');
        } else {
            try {
                // Try to get an access token (using the authorization code grant)
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);
                // We got an access token, let's now get the user's details
                $resourceOwner = $provider->getResourceOwner($accessToken);
                $userInfo = $resourceOwner->toArray();

                $user = $this->User_model->getUserInfoByTel($userInfo["email"]); // 结合业务逻辑

                if (!empty($user)) {

                    $time = time(); //当前时间
                    // 公用信息
                    $payload = [
                        'iat' => $time, //签发时间
                        'nbf' => $time, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
                        'user_id' => $user[0]['id'], //自定义信息，不要定义敏感信息, 一般只有 userId 或 username
                    ];

                    $access_token = $payload;
                    $access_token['scopes'] = 'role_access'; //token标识，请求接口的token
                    $access_token['exp'] = $time + config_item('jwt_access_token_exp'); //access_token过期时间,这里设置2个小时

                    $refresh_token = $payload;
                    $refresh_token['scopes'] = 'role_refresh'; //token标识，刷新access_token
                    $refresh_token['exp'] = $time + config_item('jwt_refresh_token_exp'); //refresh_token,这里设置30天
                    $refresh_token['count'] = 0; // 刷新TOKEN计数, 在刷新token期间多次请求刷新token则表示活跃,可以重新生成刷新token以免刷新token过期后登录

                    $message = [
                        "code" => 20000,
                        "data" => [
                            "token" => JWT::encode($access_token, config_item('jwt_key')), //生成access_tokenToken,
                            "refresh_token" => JWT::encode($refresh_token, config_item('jwt_key')) //生成refresh_token,
                        ]
                    ];
                    $this->response($message, RestController::HTTP_OK);
                } else {
                    $message = [
                        "code" => 60206,
                        "data" => ["status" => 'fail', "githubuserInfo" => $userInfo],
                        "message" => "此github邮箱账号(" . $userInfo['email'] . ")没有与系统账号关联,请联系系统管理员!"
                    ];
                    $this->response($message, RestController::HTTP_OK);
                }
            } catch (Exception $e) {
                // 直接使用Exception $e 可捕获所有异常包括依赖包 guzzlehttp 里的错误异常 GuzzleHttp\Exception\ConnectException  Message: cURL error 35: OpenSSL SSL_connect: SSL_ERROR_SYSCALL in connection to api.github.com:443
                // 使用 \League\OAuth2\Client\Provider\Exception\IdentityProviderException 只能捕获 IdentityProviderException
                $message = [
                    "code" => 60206,
                    "message" => $e->getMessage()
                ];
                $this->response($message, RestController::HTTP_OK);
            }
        }
    } // function githubauth_get() end

    // 使用 oauth2-client 通用包来进行 github 认证登录, 配置参数较多，(备用参考使用)
    function githubauth1_get()
    {
        $code = $this->get('code');
        $state = $this->get('state');

        // 需要正确配置github client ID, Secret, redirect_uri
        // $client_id = 'xxxxxx';
        // $client_secret = 'xxxxxx';
        // $redirect_uri ='http://localhost:9527/auth-redirect';
        // 这里redirect_uri 应与github网站配置尽量保持一至，因为手工封包的时候容易出现 在通过authcode获取accesstoken的时候容易
        // 漏掉redirct_uri参数，而使用github默认值，可能导致校验出错

        // composer 安装 oauth2-client 包
        // composer require league/oauth2-client
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => $client_id,    // The client ID assigned to you by the provider
            'clientSecret' => $client_secret,   // The client password assigned to you by the provider
            'redirectUri' => $redirect_uri,
            'urlAuthorize' => 'https://github.com/login/oauth/authorize',
            'urlAccessToken' => 'https://github.com/login/oauth/access_token',
            'urlResourceOwnerDetails' => 'https://api.github.com/user'
        ]);

        // If we don't have an authorization code then get one
        if (!isset($code)) {
            // 没有 code 参数, 生成授权链接 AuthorizationUrl 前返回前端
            //  https://github.com/login/oauth/authorize?state=137caabc2b409f0cccd14834fc848041&response_type=code&approval_prompt=auto&redirect_uri=http://localhost:9527/auth-redirect&client_id=94aae05609c96ffb7d3b
            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorizationUrl = $provider->getAuthorizationUrl();

            // Get the state generated for you and store it to the session.
            $_SESSION['oauth2state'] = $provider->getState();

            // Redirect the user to the authorization URL.
            // header('Location: ' . $authorizationUrl);
            // exit;
            $message = [
                "code" => 20000,
                "data" => ['auth_url' => $authorizationUrl],
            ];
            $this->response($message, RestController::HTTP_OK);

            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($state) || (isset($_SESSION['oauth2state']) && $state !== $_SESSION['oauth2state'])) {

            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }

            exit('Invalid state');
        } else {
            try {
                // Try to get an access token using the authorization code grant.
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $code
                ]);

                // We have an access token, which we may use in authenticated
                // requests against the service provider's API.
                // echo 'Access Token: ' . $accessToken->getToken() . "<br>";
                $resourceOwner = $provider->getResourceOwner($accessToken);
                // var_export($resourceOwner->toArray());
                // var_dump($resourceOwner->toArray()['email']);

                $userInfo = $resourceOwner->toArray();

                $user = $this->User_model->getUserInfoByTel($userInfo["email"]); // 结合业务逻辑
                if (!empty($user)) {

                    $time = time(); //当前时间
                    // 公用信息
                    $payload = [
                        'iat' => $time, //签发时间
                        'nbf' => $time, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
                        'user_id' => $user[0]['id'], //自定义信息，不要定义敏感信息, 一般只有 userId 或 username
                    ];

                    $access_token = $payload;
                    $access_token['scopes'] = 'role_access'; //token标识，请求接口的token
                    $access_token['exp'] = $time + config_item('jwt_access_token_exp'); //access_token过期时间,这里设置2个小时

                    $refresh_token = $payload;
                    $refresh_token['scopes'] = 'role_refresh'; //token标识，刷新access_token
                    $refresh_token['exp'] = $time + config_item('jwt_refresh_token_exp'); //refresh_token,这里设置30天
                    $refresh_token['count'] = 0; // 刷新TOKEN计数, 在刷新token期间多次请求刷新token则表示活跃,可以重新生成刷新token以免刷新token过期后登录

                    $message = [
                        "code" => 20000,
                        "data" => [
                            "token" => JWT::encode($access_token, config_item('jwt_key')), //生成access_tokenToken,
                            "refresh_token" => JWT::encode($refresh_token, config_item('jwt_key')) //生成refresh_token,
                        ]
                    ];
                    $this->response($message, RestController::HTTP_OK);
                } else {
                    $message = [
                        "code" => 60206,
                        "data" => ["status" => 'fail', "githubuserInfo" => $userInfo],
                        "message" => "此github邮箱账号(" . $userInfo['email'] . ")没有与系统账号关联,请联系系统管理员!"
                    ];
                    $this->response($message, RestController::HTTP_OK);
                }
                // } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            } catch (Exception $e) {
                // 直接使用Exception $e 可捕获所有异常包括信赖包 guzzlehttp 里的错误异常 GuzzleHttp\Exception\ConnectException  Message: cURL error 35: OpenSSL SSL_connect: SSL_ERROR_SYSCALL in connection to api.github.com:443
                // 使用 \League\OAuth2\Client\Provider\Exception\IdentityProviderException 只能捕获 IdentityProviderException
                // Failed to get the access token or user details.
                $message = [
                    "code" => 60206,
                    "message" => $e->getMessage()
                ];
                $this->response($message, RestController::HTTP_OK);
            }
        }
    } // function githubauth_get() end

    // 使用 oauth2-client 通用包进行 gitee 码云认证登录
    function giteeauth_get()
    {
        $code = $this->get('code');
        $state = $this->get('state');

        // 需要正确配置client ID, Secret, redirect_uri
        // $client_id = 'xxxxxx';
        // $client_secret = 'xxxxxx';
        // $redirect_uri ='http://localhost:9527/auth-redirect';
        // 这里redirect_uri 应与gitee网站配置尽量保持一至，因为手工封包的时候容易出现 在通过authcode获取accesstoken的时候容易
        // 漏掉redirct_uri参数，而使用gitee默认值，可能导致校验出错

        // composer 安装 oauth2-client 包
        // composer require league/oauth2-client
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => $client_id,    // The client ID assigned to you by the provider
            'clientSecret' => $client_secret,   // The client password assigned to you by the provider
            'redirectUri' => $redirect_uri,
            'urlAuthorize' => 'https://gitee.com/oauth/authorize',
            'urlAccessToken' => 'https://gitee.com/oauth/token',
            'urlResourceOwnerDetails' => 'https://gitee.com/api/v5/user'
        ]);

        // If we don't have an authorization code then get one
        if (!isset($code)) {
            // 没有 code 参数, 生成授权链接 AuthorizationUrl 前返回前端
            //  https://github.com/login/oauth/authorize?state=137caabc2b409f0cccd14834fc848041&response_type=code&approval_prompt=auto&redirect_uri=http://localhost:9527/auth-redirect&client_id=94aae05609c96ffb7d3b
            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorizationUrl = $provider->getAuthorizationUrl();

            // Get the state generated for you and store it to the session.
            $_SESSION['oauth2state'] = $provider->getState();

            // Redirect the user to the authorization URL.
            // header('Location: ' . $authorizationUrl);
            // exit;
            $message = [
                "code" => 20000,
                "data" => ['auth_url' => $authorizationUrl],
            ];
            $this->response($message, RestController::HTTP_OK);

            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($state) || (isset($_SESSION['oauth2state']) && $state !== $_SESSION['oauth2state'])) {

            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }

            exit('Invalid state');
        } else {
            try {
                // Try to get an access token using the authorization code grant.
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $code
                ]);
                // // We have an access token, which we may use in authenticated
                // // requests against the service provider's API.
                // echo 'Access Token: ' . $accessToken->getToken() . "<br>";
                // echo 'Refresh Token: ' . $accessToken->getRefreshToken() . "<br>";
                // echo 'Expired in: ' . $accessToken->getExpires() . "<br>";
                // echo 'Already expired? ' . ($accessToken->hasExpired() ? 'expired' : 'not expired') . "<br>";

                // Using the access token, we may look up details about the
                // resource owner.
                $resourceOwner = $provider->getResourceOwner($accessToken);

                // 与业务系统绑定帐户时 应该以 login 为唯一名比较好，或者邮箱？
                //  var_export($resourceOwner->toArray());
                //  array (
                //     'id' => 111,
                //     'login' => 'foo',
                //     'name' => 'fooname',
                //     'avatar_url' => 'https://gitee.com/assets/no_portrait.png',
                //     'blog' => '',
                //     'weibo' => '',
                //     'bio' => '',
                //     'email' => 'abc@qq.com',
                //   )
                $userInfo = $resourceOwner->toArray();

                $user = $this->User_model->getUserInfoByTel($userInfo["email"]); // 结合业务逻辑
                if (!empty($user)) {

                    $time = time(); //当前时间
                    // 公用信息
                    $payload = [
                        'iat' => $time, //签发时间
                        'nbf' => $time, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
                        'user_id' => $user[0]['id'], //自定义信息，不要定义敏感信息, 一般只有 userId 或 username
                    ];

                    $access_token = $payload;
                    $access_token['scopes'] = 'role_access'; //token标识，请求接口的token
                    $access_token['exp'] = $time + config_item('jwt_access_token_exp'); //access_token过期时间,这里设置2个小时

                    $refresh_token = $payload;
                    $refresh_token['scopes'] = 'role_refresh'; //token标识，刷新access_token
                    $refresh_token['exp'] = $time + config_item('jwt_refresh_token_exp'); //refresh_token,这里设置30天
                    $refresh_token['count'] = 0; // 刷新TOKEN计数, 在刷新token期间多次请求刷新token则表示活跃,可以重新生成刷新token以免刷新token过期后登录

                    $message = [
                        "code" => 20000,
                        "data" => [
                            "token" => JWT::encode($access_token, config_item('jwt_key')), //生成access_tokenToken,
                            "refresh_token" => JWT::encode($refresh_token, config_item('jwt_key')) //生成refresh_token,
                        ]
                    ];
                    $this->response($message, RestController::HTTP_OK);
                } else {
                    $message = [
                        "code" => 60206,
                        "data" => ["status" => 'fail', "giteeuserInfo" => $userInfo],
                        "message" => "此gitee邮箱账号(" . $userInfo['email'] . ")没有与系统账号关联,请联系系统管理员!"
                    ];
                    $this->response($message, RestController::HTTP_OK);
                }
            } catch (Exception $e) {
                // Failed to get the access token or user details.
                exit($e->getMessage());
            }
        }
    } // function giteeauth_get() end

    function refreshtoken_post()
    {
        // 此处 $Token 应为refresh token 在前端 request 拦截器中做了修改
        // 刷新token接口需要在控制器内作权限验证,比较特殊,不能使用hook ManageAuth来验证
        $Bearer = $this->input->get_request_header('Authorization', true);
        list($Token) = sscanf($Bearer, 'Bearer %s');

        try {
            $decoded = JWT::decode($Token, config_item('jwt_key'), ['HS256']); //HS256方式，这里要和签发的时候对应

            // $decoded = JWT::decode($Token, config_item('jwt_key'), ['HS256']); //HS256方式，这里要和签发的时候对应
            //            stdClass Object
            //            (
            //                [iss] => http://www.helloweba.net
            //                [aud] => http://www.helloweba.net
            //                [iat] => 1577668094
            //                [nbf] => 1577668094
            //                [exp] => 1577668094
            //                [user_id] => 2
            //                [count] => 0
            //            )

            $time = time(); //当前时间
            // 公用信息
            $payload = [
                'iat' => $time, //签发时间
                'nbf' => $time, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
                'user_id' => $decoded->user_id, //自定义信息，不要定义敏感信息, 一般只有 userId 或 username
            ];

            $access_token = $payload;
            $access_token['scopes'] = 'role_access'; //token标识，请求接口的token
            $access_token['exp'] = $time + config_item('jwt_access_token_exp'); //access_token过期时间,这里设置2个小时
            $new_access_token = JWT::encode($access_token, config_item('jwt_key')); //生成access_tokenToken
            //        {
            //          "iss": "http://pocoyo.org",
            //          "aud": "http://emacs.org",
            //          "iat": 1577757920,
            //          "nbf": 1577757920,
            //          "user_id": "1",
            //          "scopes": "role_refresh",
            //          "exp": 1577758100,
            //          "count": 0
            //        }

            $count = $decoded->count + 1;
            if ($count > config_item('jwt_refresh_count')) { // 在刷新token期间 {多次} 请求刷新token则表示活跃,可以重新生成刷新token以免刷新token过期后登录
                $refresh_token = $payload;
                $refresh_token['scopes'] = 'role_refresh'; //token标识，刷新access_token
                $refresh_token['exp'] = $time + config_item('jwt_refresh_token_exp');
                $refresh_token['count'] = 0; // 重置刷新TOKEN计数
                $new_refresh_token = JWT::encode($refresh_token, config_item('jwt_key')); // 这里可以根据需要重新生成 refresh_token
            } else { // 保持refresh_token过期时间及其他共公用信息,仅自增计数器
                $decoded->count++;
                $new_refresh_token = JWT::encode($decoded, config_item('jwt_key'));
            }

            $message = [
                "code" => 20000,
                "data" => [
                    "token" => $new_access_token,
                    "refresh_token" => $new_refresh_token
                ]
            ];
            $this->response($message, RestController::HTTP_OK);
        } catch (\Firebase\JWT\ExpiredException $e) {  // access_token过期
            $message = [
                "code" => 50015,
                "message" => 'refresh_token过期, 请重新登录'
            ];
            $this->response($message, RestController::HTTP_UNAUTHORIZED);
        } catch (Exception $e) {  //其他错误
            $message = [
                "code" => 50015,
                "message" => $e->getMessage()
            ];
            $this->response($message, RestController::HTTP_UNAUTHORIZED);
        }
    }

    // 根据token拉取用户信息 get
    function info_get()
    {
        // /sys/user/info 不用认证但是需要提取出 access_token 中的 user_id 来拉取用户信息
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
        //    $decoded = JWT::decode($Token, config_item('jwt_key'), ['HS256']); //HS256方式，这里要和签发的时候对应
        //     print_r($decoded);
        //            stdClass Object
        //            (
        //                [iss] => http://pocoyo.org
        //    [aud] => http://emacs.org
        //    [iat] => 1577348490
        //    [nbf] => 1577348490
        //    [data] => stdClass Object
        //            (
        //                [user_id] => 1
        //            [username] => admin
        //        )
        //
        //    [scopes] => role_access
        //            [exp] => 1577355690
        //)
        // $result = $this->some_model();
        $result = $this->User_model->getUserInfo($jwt_obj->user_id);
        $MenuTreeArr = $this->permission->getPermission($jwt_obj->user_id, 'menu', false);
        $asyncRouterMap = $this->permission->genVueRouter($MenuTreeArr, 'id', 'pid', 0);
        $CtrlPerm = $this->permission->getMenuCtrlPerm($jwt_obj->user_id);

        // 获取用户信息成功
        if ($result['success']) {
            $info1 = $result['userinfo'];
            // 附加信息2
            $info2 = [
                // "roles" => ["admin", "editor"],
                "roles" => $this->User_model->getUserRolesByUserId($jwt_obj->user_id),
                "introduction" => "I am a super administrator",
                // "avatar" => "https://wpimg.wallstcn.com/f778738c-e4f8-4870-b634-56703b4acafe.gif",
                "name" => "pocoyo",
                "identify" => "110000000000000000",
                "phone" => "13888888888",
                "ctrlperm" => $CtrlPerm,
                //                "ctrlperm" => [
                //                    [
                //                        "path" => "/sys/menu/view"
                //                    ],
                //                    [
                //                        "path" => "/sys/menu/add"
                //                    ],
                //                    [
                //                        "path" => "/sys/menu/download"
                //                    ]
                //                ],
                "asyncRouterMap" => $asyncRouterMap
                //                "asyncRouterMap" => [
                //                [
                //                    "path" => '/sys',
                //                    "name" => 'sys',
                //                    "meta" => [
                //                        "title" => "系统管理",
                //                        "icon" => "sysset2"
                //                    ],
                //                    "component" => 'Layout',
                //                    "redirect" => '/sys/menu',
                //                    "children" => [
                //                        [
                //                            "path" => '/sys/menu',
                //                            "name" => 'menu',
                //                            "meta" => [
                //                                "title" => "菜单管理",
                //                                "icon" => "menu1"
                //                            ],
                //                            "component" => 'sys/menu/index',
                //                            "redirect" => '',
                //                            "children" => [
                //
                //                            ]
                //                        ],
                //                        [
                //                            "path" => '/sys/user',
                //                            "name" => 'user',
                //                            "meta" => [
                //                                "title" => "用户管理",
                //                                "icon" => "user"
                //                            ],
                //                            "component" => 'pdf/index',
                //                            "redirect" => '',
                //                            "children" => [
                //
                //                            ]
                //                        ],
                //                        [
                //                            "path" => '/sys/icon',
                //                            "name" => 'icon',
                //                            "meta" => [
                //                                "title" => "图标管理",
                //                                "icon" => "icon"
                //                            ],
                //                            "component" => 'svg-icons/index',
                //                            "redirect" => '',
                //                            "children" => [
                //
                //                            ]
                //                        ]
                //                    ]
                //                ],
                //                    [
                //                        "path" => '/sysx',
                //                        "name" => 'sysx',
                //                        "meta" => [
                //                            "title" => "其他管理",
                //                            "icon" => "plane"
                //                        ],
                //                        "component" => 'Layout',
                //                        "redirect" => '',
                //                        "children" => [
                //
                //                        ]
                //                    ]
                //                ]
            ];

            $info = array_merge($info1, $info2);

            $message = [
                "code" => 20000,
                "data" => $info,
                "_SERVER" => $_SERVER,
                "_GET" => $_GET
            ];
            $this->response($message, RestController::HTTP_OK);
        } else {
            $message = [
                "code" => 50008,
                "message" => 'Login failed, unable to get user details.'
            ];

            $this->response($message, RestController::HTTP_OK);
        }
    }

    function logout_post()
    {
        $message = [
            "code" => 20000,
            "data" => 'success'
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    function list_get()
    {
        //  $result = $this->some_model();
        $result['success'] = TRUE;

        if ($result['success']) {
            $List = array(
                array('order_no' => '201805138451313131', 'timestamp' => 'iphone 7 ', 'username' => 'iphone 7 ', 'price' => 399, 'status' => 'success'),
                array('order_no' => '300000000000000000', 'timestamp' => 'iphone 7 ', 'username' => 'iphone 7 ', 'price' => 399, 'status' => 'pending'),
                array('order_no' => '444444444444444444', 'timestamp' => 'iphone 7 ', 'username' => 'iphone 7 ', 'price' => 399, 'status' => 'success'),
                array('order_no' => '888888888888888888', 'timestamp' => 'iphone 7 ', 'username' => 'iphone 7 ', 'price' => 399, 'status' => 'pending'),
            );

            $message = [
                "code" => 20000,
                "data" => [
                    "total" => count($List),
                    "items" => $List
                ]
            ];
            $this->response($message, RestController::HTTP_OK);
        } else {
            $message = [
                "code" => 50008,
                "message" => 'Login failed, unable to get user details.'
            ];

            $this->response($message, RestController::HTTP_OK);
        }
    }

    // 更新密码
    function password_put()
    {
        $parms = $this->put();
        // 参数检验/数据预处理
        try {
            // 使用check 来捕获异常信息 https://respect-validation.readthedocs.io/en/2.0/rules/AnyOf/
            v::keySet(
                v::key('username', v::notEmpty()),
                v::key('password_orig', v::notEmpty()),
                v::key('password', v::regex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[\S]{8,}$/')),
                v::key('password_confirmation', v::notEmpty())
            )->check($parms);
            v::keyValue('password_confirmation', 'equals', 'password')->check($parms);
        } catch (ValidationException $e) {
            $message = [
                "code" => 20400,
                "type" => 'error',
                "message" => $e->getMessage()
            ];
            $this->response($message, RestController::HTTP_OK);
        }

        // 原密码校验
        $has = $this->Medoodb->has(
            'sys_user',
            [
                'username' => $parms['username'],
                'password' =>    md5($parms['password_orig'])
            ]
        );

        if (!$has) {
            $message = [
                "code" => 20400,
                "type" => 'error',
                "message" => '原密码不正确'
            ];
            $this->response($message, RestController::HTTP_OK); // BAD_REQUEST (400) being the HTTP response code
        }

        // 更新密码
        $this->Medoodb->update(
            'sys_user',
            ['password' => md5($parms['password'])],
            ['username' => $parms['username']]
        );
        // 捕获错误信息
        $err = $this->Medoodb->error();
        if ($err[1]) { // 如果出错 否则为空
            $message = [
                "code" => 20400,
                "data" => $err[2]
            ];
            $this->response($message, RestController::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        $message = [
            "code" => 20000,
            "type" => 'success',
            "message" => '更新成功'
        ];
        $this->response($message, RestController::HTTP_OK);
    }
}
