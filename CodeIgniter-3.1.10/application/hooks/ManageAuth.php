<?php
defined('BASEPATH') or exit('No direct script access allowed');

use \Firebase\JWT\JWT;
use chriskacerguis\RestServer\RestController;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;

class ManageAuth
{
    private $_ci;
    private $http_method;
    function __construct()
    {
        $this->_ci = &get_instance();  //获取CI对象
        $this->http_method = Strings::lower($_SERVER['REQUEST_METHOD']); // => get/post/put/delete
    }

    //token及权限认证
    public function auth()
    {
        // var_dump(uri_string()); // => api/v2/sys/user/login , api/example/users, rest_server, welcome
        // uri_string() == '' 表示 http://www.cirest.com:8890/ 地址 uri_string 为空，同样在白名单不认证
        $in_whiteList = uri_string() == '' ? true : Arrays::some(config_item('jwt_white_list'), function ($value): bool {
            // 白名单里的某一项 eg. '/sys/user/testapi' 包含于 uri_string() => 'api/v2/sys/user/testapi' 中则立即返回true, 所有项都不包含于才返回false
            $res = explode("/", $value);
            $http_method_wl = array_pop($res); // 获取最后一个元素，并且原数组删除最后一个
            $uri_wl = implode("/", $res);

            // var_dump($http_method_wl);
            // var_dump($uri_wl);
            // var_dump(uri_string());
            // var_dump(Strings::contains(uri_string(), $uri_wl));
            // var_dump($this->http_method === $http_method_wl);
            // var_dump(Strings::contains(uri_string(), $uri_wl) && $this->http_method === $http_method_wl);

            return Strings::contains(uri_string(), $uri_wl) && $this->http_method === $http_method_wl;
        });

        if (!$in_whiteList) { // 不在白名单里需要校验 token expired etc..
            $headers = $this->_ci->input->request_headers();

            // 防止在浏览器直接进入api，页面抛出异常错误
            if (!array_key_exists('Authorization', $headers)) {
                $message = [
                    "code" => 50015,
                    "message" => 'request_headers has not token info.'
                ];
                $this->_ci->response($message, RestController::HTTP_FORBIDDEN);
            }

            // Extract the jwt from the Bearer
            list($Token) = sscanf($headers['Authorization'], 'Bearer %s');

            try {
                $decoded = JWT::decode($Token, config_item('jwt_key'), ['HS256']); //HS256方式，这里要和签发的时候对应
                $userId = $decoded->user_id;

                $retPerm = $this->_ci->permission->HasPermit($userId, uri_string(), $this->http_method);
                if ($retPerm['code'] != 50000) {
                    $this->_ci->response($retPerm, RestController::HTTP_OK);
                }
            } catch (\Firebase\JWT\ExpiredException $e) {  // access_token过期
                $message = [
                    "code" => 50014,
                    "message" => $e->getMessage()
                ];
                $this->_ci->response($message, RestController::HTTP_UNAUTHORIZED);
            } catch (Exception $e) {  //其他错误
                $message = [
                    "code" => 50015,
                    "message" => $e->getMessage()
                ];
                $this->_ci->response($message, RestController::HTTP_UNAUTHORIZED);
            }
        }
    } // auth() end
}
