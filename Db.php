<?php

/**
 * Created by PhpStorm.
 * User: nihuan
 * Date: 17-10-27
 * Time: 下午1:34
 * Desc: 数据库操作类
 */
class Db
{
    private $dbConf;
    private $sbConf;
    private $redisConf;
    protected $otherConf;
    protected $searchConf;
    protected $testRedisConf;
    protected $searchConn = null;
    protected $connect = null;
    protected $sbConn = null;
    protected $redis_conn = null;
    private $local_host  = "http://localhost/";
    private $ali_host  = "https://api.website.com/";
    protected $ossConfig = null;
    protected $test_redis = null;
    protected $black_list = [];

    protected function __construct()
    {
        require dirname(__FILE__) . '../public/config.php';
        $this->dbConf = Config::dbConf;
        $this->sbConf = Config::SoubuConf;
        $this->searchConf = Config::SearchConf;
        $this->redisConf = Config::RedisConf;
        $this->testRedisConf = Config::testRedisConf;
        $this->ossConfig = Config::OssConf;
        $this->otherConf = Config::OtherConf;

        //默认库
        $this->connect = new mysqli($this->dbConf['host'],$this->dbConf['user'],$this->dbConf['password'],$this->dbConf['dbname'],$this->dbConf['port']);
        if(!mysqli_connect_errno()){
            $this->connect->query("SET NAMES utf8");
        }else{
            var_dump('Db Err:' . mysqli_connect_error());
        }

        //其他库
        $this->sbConn = new mysqli($this->sbConf['host'],$this->sbConf['user'],$this->sbConf['password'],$this->sbConf['dbname'],$this->sbConf['port']);
        if(!mysqli_connect_errno()){
            $this->sbConn->query("SET NAMES utf8");
        }else{
            var_dump('Soubu Db Err:' . mysqli_connect_error());
        }

        //搜索库
        $this->searchConn = new mysqli($this->searchConf['host'],$this->searchConf['user'],$this->searchConf['password'],$this->searchConf['dbname'],$this->searchConf['port']);
        if(!mysqli_connect_errno()){
            $this->searchConn->query("SET NAMES utf8");
        }else{
            var_dump('Search Db Err:' . mysqli_connect_error());
        }
        $this->redis_conn = self::get_redis();
        $this->test_redis = self::get_test_redis();
    }


    /**
     * 多记录查询
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-10-30
     * @param $table
     * @param $where
     * @param string $order
     * @param int $page
     * @param string $field
     * @param string $group
     * @param int $connect_id
     * @return array
     */
    protected function selectAll($table,$where,$order = '',$page = 20,$field = '',$group = '',$connect_id = 1){
        $list = [];
        if(empty($field)){
            $field = '*';
        }
        $sql = "SELECT {$field} FROM {$table} WHERE {$where}";
        if(!empty($group)){
            $sql .= " GROUP BY {$group}";
        }
        if(!empty($order)){
            $sql .= " ORDER BY {$order} ";
        }
        $sql .= " limit {$page}";
        if($connect_id == 1){
            $result = $this->connect->query($sql);
        }elseif($connect_id == 2){
            $result = $this->sbConn->query($sql);
        }else{
            $result = $this->searchConn->query($sql);
        }
        while ($row = mysqli_fetch_assoc($result)){
            $list[] = $row;
        }
        return $list;
    }

    /**
     * 单条记录查询
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-10-30
     * @param $table
     * @param $where
     * @param string $field
     * @param string $order
     * @param int $connect_id
     * @return array
     */
    protected function selectOne($table,$where,$order = '',$field = '',$connect_id = 1){
        $row = [];
        if(empty($field)){
            $field = '*';
        }
        $sql = "SELECT {$field} FROM {$table} WHERE {$where} ";
        if(!empty($order)){
            $sql .= " ORDER BY {$order} ";
        }
        $sql .= "limit 1";
        if($connect_id == 1){
            $result = $this->connect->query($sql);
        }elseif($connect_id == 2){
            $result = $this->sbConn->query($sql);
        }else{
            $result = $this->searchConn->query($sql);
        }
        $row = mysqli_fetch_assoc($result);
        return $row;
    }


    /**
     * 原生SQL执行方法
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-11-25
     * @param $query
     * @param int $get_type 获取类型 1:数据查询 2:数据统计
     * @param int $connect_id
     * @return array | int
     */
    protected function selectQuery($query,$get_type = 1,$connect_id = 1){
        $list = [];
        if($connect_id == 1){
            $result = $this->connect->query($query);
        }elseif($connect_id == 2){
            $result = $this->sbConn->query($query);
        }else{
            $result = $this->searchConn->query($query);
        }
        if($get_type == 1){
            while ($row = mysqli_fetch_assoc($result)){
                $list[] = $row;
            }
            return $list;
        }else{
            $count = mysqli_num_rows($result);
            return $count;
        }
    }

    /**
     * 获取总条数
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-10-30
     * @param $table
     * @param $where
     * @param $group
     * @param int $connect_id
     * @return int
     */
    protected function getCount($table,$where,$group = '',$connect_id = 1){
        $count = 0;
        $sql = "SELECT count(*) AS count FROM {$table} WHERE {$where}";
        if(!empty($group)){
            $sql .= " GROUP BY {$group}";
        }
        if($connect_id == 1){
            $result = $this->connect->query($sql);
        }elseif($connect_id == 2){
            $result = $this->sbConn->query($sql);
        }else{
            $result = $this->searchConn->query($sql);
        }
        if($result != false){
            $countRes = mysqli_fetch_assoc($result);
            $count = (int)$countRes['count'];
        }
        return $count;
    }

    /**
     * 修改记录
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-10-30
     * @param $table
     * @param $where
     * @param array $data
     * @param int $connect_id
     * @return bool
     */
    protected function update($table,$where,$data = [],$connect_id = 1){
        $up = '';
        if(!empty($data)){
            foreach ($data as $k => $v) {
                $up .= "{$k} = '{$v}',";
            }
        }
        $up = substr($up,0,strlen($up)-1);
        $sql = "UPDATE {$table} SET $up WHERE {$where}";
        if($connect_id == 1){
            $result = $this->connect->query($sql);
        }elseif($connect_id == 2){
            $result = $this->sbConn->query($sql);
        }else{
            $result = $this->searchConn->query($sql);
        }
        return $result;
    }


    /**
     * 写入单条数据
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-10-30
     * @param $table
     * @param array $value
     * @param int $connect_id
     * @return bool
     */
    protected function insertOne($table,$value = [],$connect_id = 1){
        if(!empty($value)){
            $sql = "INSERT INTO {$table} (";
            $key = array_keys($value);
            $value = array_values($value);
            foreach ($key as $k) {
                $sql .= "{$k},";
            }
            $sql = substr($sql,0,strlen($sql) -1);
            $sql .= ') VALUES (';
            foreach ($value as $item) {
                $sql .= "'{$item}',";
            }
            $sql = substr($sql,0,strlen($sql)-1) . ');';
            if($connect_id == 1){
                $this->connect->query($sql);
                $last_id = mysqli_insert_id($this->connect);
            }elseif($connect_id == 2){
                $this->sbConn->query($sql);
                $last_id = mysqli_insert_id($this->sbConn);
            }else{
                $this->searchConn->query($sql);
                $last_id = mysqli_insert_id($this->searchConn);
            }
            return $last_id;
        }else{
            return false;
        }
    }

    /**
     * 写入多条数据
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-10-30
     * @param $table
     * @param $values
     * @param $others
     * @param int $connect_id
     * @return bool
     */
    protected function insertAll($table,$values = [],$others = [],$connect_id = 1){
        $result = false;
        if(!empty($values)){
            $sql = "INSERT INTO {$table}";
            $keys = $keyField = '';
            $insert = '';
            foreach ($values as $key => $value){
                $inKey = array_keys($value);
                $inVal = array_values($value);
                if(empty($keys)){
                    $keys .= '(';
                    foreach ($inKey as $item) {
                        $keys .= "{$item},";
                    }
                    if(!empty($others)){
                        $other_key = array_keys($others);
                        $other_values = array_values($others);
                        foreach ($other_key as $ok) {
                            $keys .= "{$ok},";
                        }

                    }
                    $keys = substr($keys,0,strlen($keys)-1) . ') VALUES ';
                }
                $keyField = $keys;
                $insert .= '(';
                foreach ($inVal as $sv) {
                    $insert .= "'{$sv}',";
                }
                if(isset($other_values)){
                    foreach ($other_values as $ov) {
                        $insert .= "'{$ov}',";
                    }
                }
                $insert = substr($insert,0,strlen($insert)-1) . "),";
            }
            if(!empty($insert)){
                $insert = substr($insert,0,strlen($insert)-1) . ';';
                if($connect_id == 1){
                    $result = $this->connect->query($sql . $keyField . $insert);
                }elseif($connect_id == 2){
                    $result = $this->sbConn->query($sql . $keyField . $insert);
                }else{
                    $result = $this->searchConn->query($sql . $keyField . $insert);
                }
            }
        }
        return $result;
    }


    /**
     * 黑名单列表获取
     * @Author Nihuan
     * @Version 1.0
     * @Date 18-4-11
     * @param $type
     * @return array
     */
    protected function get_black_list($type = 0){
        $list = [];
        $where = "is_delete = 0";
        if($type > 0){
            $where .= "type = {$type}";
        }
        $sql = "SELECT user_id FROM black_list WHERE {$where} LIMIT 200";
        $agent = $this->connect->query($sql);
        if(!empty($agent)){
            while ($row = mysqli_fetch_assoc($agent)){
                $list[] = $row['uid'];
            }
        }
        //自营/代运营帐号判断
        $black_list = is_null($list) ? [] : $list;
        return $black_list;
    }

    protected function get_redis(){
        $this->redis_conn = new Redis();
        $this->redis_conn->connect($this->redisConf['host'], $this->redisConf['port']);
        $this->redis_conn->auth($this->redisConf['pass']);
        $this->redis_conn->select($this->redisConf['db']);
        return $this->redis_conn;
    }


    /**
     * 搜索引擎数据请求
     * @Author Nihuan
     * @Version 1.0
     * @Date 18-01-25
     * @param $method_name
     * @param array $parameter
     * @param $user_id
     * @return array|bool
     */
    protected function search_service($method_name, $parameter = [],$user_id)
    {
        $list = [];
        $header = [
            'Content-Type: application/json',
            'requestBasic:' . json_encode(['fromType' => $this->otherConf['OPEN_FROM_TYPE']]),
            'userId:' . $user_id
        ];
        $post_data = [
            'serviceName' => $this->otherConf['RECOMMEND_SERVICE_NAME'],
            'applicationName' => $this->otherConf['SEARCH_APPLICATION_NAME'],
            'methodName' => $method_name
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->otherConf['SEARCH_SERVICE_URL']);
        if(!empty($parameter)){
            $post_data['inputParameter'] = $parameter;
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        }
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);
        if($res !== false){
            $result = json_decode($res,true);
            if($result['code'] == 0){
               return $result['code'] == 0 ? true : false;
            }
        }
        return $list;
    }


    /**
     * 个推消息发送
     * @Author Nihuan
     * @Version 1.0
     * @Date 18-02-02
     * @param string $cid
     * @param array $content
     * @return array
     * @throws
     */
    protected function getui_massage($cid,$content = array()){
        define('MYROOT',dirname(__FILE__));
        require_once MYROOT . '/Getui/IGt.Push.php';
        require_once MYROOT . '/Getui/igetui/IGt.AppMessage.php';
        require_once MYROOT . '/Getui/igetui/IGt.APNPayload.php';
        require_once MYROOT . '/Getui/IGt.Batch.php';
        require_once MYROOT . '/Getui/igetui/utils/AppConditions.php';
        require_once MYROOT . '/Getui/igetui/IGt.MultiMedia.php';
        $category = json_encode($content);

        //模板设置
        $template = new IGtTransmissionTemplate();
        $template->set_appId($this->otherConf['GETUI_APP_ID']);
        $template->set_appkey($this->otherConf['GETUI_APP_KEY']);
        $template->set_transmissionType(2);
        $template->set_transmissionContent($category);

        //透传内容设置
        $payload = new IGtAPNPayload();
        $payload->contentAvailable = 0;
        $payload->category = $category;
        $payload->badge = "+1";

        //消息体设置
        $alterMsg = new DictionaryAlertMsg();
        $alterMsg->body = (string)$content['content'];
        $alterMsg->title = $content['title'];
        $payload->alertMsg = $alterMsg;
        if($content['pic'] != ''){
            $media = new IGtMultiMedia();
            $medicType = new MediaType();
            $media->type = $medicType::pic;
            $media->url = $content['pic'];
            $payload->add_multiMedia($media);
        }

        $template->set_apnInfo($payload);

        //推送实例化
        $igt = new IGeTui(NULL,$this->otherConf['GETUI_APP_KEY'],$this->otherConf['GETUI_MASTER_SECRET'],false);

        //个推信息体
        $messageNoti = new IGtSingleMessage();
        $messageNoti->set_isOffline(true);//是否离线
        $messageNoti->set_offlineExpireTime(24 * 60 * 60);//离线时间
        $messageNoti->set_data($template);//设置推送消息类型

        //接收方
        $target = new IGtTarget();
        $target->set_appId($this->otherConf['GETUI_APP_ID']);
        $target->set_clientId($cid);

        try {
            $rep = $igt->pushMessageToSingle($messageNoti, $target);
        }catch(RequestException $e){
            $requstId =$e->getRequestId();
            $rep = $igt->pushMessageToSingle($messageNoti, $target,$requstId);
        }
        return $rep;
    }

}