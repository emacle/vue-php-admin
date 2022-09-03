<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
// Using Medoo namespace
use Medoo\Medoo;
use Nette\Utils\Arrays;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;

class Log extends RestController
{
    private $Medoodb;

    function __construct()
    {
        parent::__construct();
        // Initialize
        $this->Medoodb = new Medoo(config_item('medoodb'));
    }

    // 查
    function logs_get()
    {
        // GET /logs?offset=1&limit=10&sort=-uri&query=~uri,method,time&uri=logs&method=post&time=1586188800,1586880000
        // 分页参数配置
        $limit = $this->get('limit') ? $this->get('limit') : 10;
        $offset = $this->get('offset') ?  ($this->get('offset') - 1) *  $limit : 0; // 第几页
        $where = [
            "LIMIT" => [$offset, $limit]
        ];
        // 分页参数配置结束

        // GET /logs?offset=1&limit=10&sort=-uri&query=~uri,method,time&uri=logs&method=post&time=1586188800,1586880000
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

        // GET /logs?offset=1&limit=10&sort=-uri&query=~uri,method,time&uri=logs&method=post&time=1586188800,1586880000
        // fields: 显示字段参数过滤配置,不设置则为全部
        $fields = $this->get('fields');
        $fields ? $columns = explode(",", $fields) : $columns = "*";
        // 显示字段过滤配置结束

        // GET /logs?offset=1&limit=10&sort=-uri&query=~uri,method,time&uri=logs&method=post&time=1586188800,1586880000
        // 指定条件模糊或搜索查询,uri like %pocoyo%, 此时 total $wherecnt 条件也要发生变化
        // 查询字段及字段值获取
        // 如果存在query 参数以,分隔，且每个参数的有值才会增加条件
        $wherecnt = []; // 计算total使用条件，默认为全部
        $query = $this->get('query');
        if ($query) { // 存在才进行过滤,否则不过滤
            $queryArr = explode(",", $query);
            foreach ($queryArr as $k => $v) {
                if (Strings::startsWith($v, '~')) { // true query=~uri以~开头表示模糊查询
                    $tmpKey = Strings::substring($v, 1); // uri
                    $tmpValue = $this->get($tmpKey);
                    if (!is_null($tmpValue)) {
                        $where[$tmpKey . '[~]'] = $tmpValue;
                        $wherecnt[$tmpKey . '[~]'] = $tmpValue;
                    }
                } else if ($v == 'time') { // &time=1585670400,1586793600
                    $tmpValue = $this->get($v); // time => 1585670400,1586793600
                    if (!is_null($tmpValue)) {
                        $timeArr = explode(",", $tmpValue);  // => [1585670400,1586793600]
                        $where[$v . '[<>]'] = $timeArr;
                        $wherecnt[$v . '[<>]'] = $timeArr;
                    }
                } else { // method
                    $tmpValue = $this->get($v);
                    if (!is_null($tmpValue)) {
                        $where[$v] = $tmpValue;
                        $wherecnt[$v] = $tmpValue;
                    }
                }
            }
        }
        // 查询字段及字段值获取结束

        $data = $this->Medoodb->select(
            "logs",
            $columns,
            $where
        );

        // var_dump($this->Medoodb->log());
        // var_dump($this->Medoodb->error());

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

        // 处理获取的数据 由params 字段 获得真实ip，用户id，格式化时间等信息
        // 必要时可剔除 params 字段再返回前端
        foreach ($data as $k => $v) {
            $currentParms = (config_item('rest_logs_json_params') === true) ? json_decode($v['params'], true) : unserialize($v['params']);
            $data[$k]['params'] = $currentParms;
            $data[$k]['ip'] = Arrays::get($currentParms, 'X-Real-IP', $v['ip_address']);
            $data[$k]['time'] = date('Y-m-d H:i:s', $v['time']);

            if (array_key_exists('Authorization', $currentParms)) {
                list($Token) = sscanf($currentParms['Authorization'], 'Bearer %s');

                $tks = explode('.', $Token); // jwt token . 分 第2段为 $payload
                if (count($tks) != 3) {
                    $data[$k]['username'] = 'Token: ' . $Token;  // 非jwt token api测试时生成该log
                } else {
                    $jwt_object = json_decode(base64_decode($tks[1]));

                    $userArr = $this->Medoodb->select(
                        'sys_user',
                        'username',
                        ['id' =>  $jwt_object->user_id]
                    );
                    $data[$k]['username'] = $userArr[0];
                }
            } else if (array_key_exists('username', $currentParms)) { // login
                $data[$k]['username'] = $currentParms['username'];
            } else {
                $data[$k]['username'] = 'No-Token';  // no token api测试时生成该log
            }
        }

        $total = $this->Medoodb->count("logs",  $wherecnt);
        $message = [
            "code" => 20000,
            "data" => [
                "items" => $data,
                "total" => $total
            ]
        ];
        $this->response($message, RestController::HTTP_OK);
    }

    // mysql -h localhost -uroot -proot vueadminv2 < /var/www/vue-element-github/CodeIgniter-3.1.10/vueadminv2_orig.sql
    // 数据库恢复,写死的必须在Linux服务器执行命令，windows机器
    function dbrestore_post()
    {
        $message = [
            "code" => 20000,

            "message" => '测试'
        ];
        $this->response($message, RestController::HTTP_OK);
        return;
        $cmd = "(mysql -h localhost -uroot -proot vueadminv2 < /var/www/vue-element-github/CodeIgniter-3.1.10/vueadminv2_orig.sql) 2>&1";
        exec($cmd, $output, $return_var);

        !$return_var ? $msg = "成功" : $msg = "失败";

        $message = [
            "code" => 20000,
            "output" => $output,
            "return_var" => $return_var,
            "message" => $msg
        ];
        $this->response($message, RestController::HTTP_OK);
    }
}
