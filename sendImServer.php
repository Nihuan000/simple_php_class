<?php

/**
 * Created by PhpStorm.
 * User: nihuan
 * Date: 17-11-20
 * Time: 下午7:07
 */
class sendImServer
{

    private $switch_qm = 0;
    private $qm_server_url = 'http://localhost/QM/receiver.php';
    private function imConfig(){
        $im = [
            /*腾讯IM聊天设置*/
            'IM_APPID' => '1245215',
            'IM_YUN_URL' => 'https://console.tim.qq.com',
            'IM_YUN_VERSION' => 'v4',
            'IM_CONTENT_TYPE' => 'json',
            'IM_METHOD' => 'post',
            'IM_APN' => '0',
            'IM_PRIVATE_KEY' => '/public/IMcloud/private_key',
            'IM_SIGNATURE' => '/public/IMcloud/bin/signature',
        ];
        return $im;
    }
    /**
     * 腾讯云通讯参数生成
     * @Author Nihuan
     * @Version 1.0
     * @Date 16-11-03
     * @return string
     */
    private function IMService(){
        $im = self::imConfig();
        $usersig = self::getIMtoken('soubu_admin');
        $sdkappid = $im['IM_APPID'];
        $content_type = $im['IM_CONTENT_TYPE'];
        $parameter =  "usersig=" . $usersig
            . "&identifier=soubu_admin"
            . "&sdkappid=" . $sdkappid
            . "&contenttype=" . $content_type;
        return $parameter;
    }


    /**
     * 腾讯云通讯token生成
     * @Author Nihuan
     * @Version 1.0
     * @Date 16-10-28
     * @param $uid
     * @return bool
     */
    private function getIMtoken($uid){
        $token = '';
        if($uid == false){
            return false;
        }else{
            $im = self::imConfig();
            $private_key = $im['IM_PRIVATE_KEY'];
            $bin_path = $im['IM_SIGNATURE'];
            $appid = $im['IM_APPID'];
            $command = $bin_path
                . ' ' . escapeshellarg($private_key)
                . ' ' . escapeshellarg($appid)
                . ' ' . escapeshellarg($uid);
            exec($command, $out, $status);
            if ($status != 0)
            {
                echo 'msg:' . json_encode($out);
            }
            if(!empty($out)){
                $token = $out[0];
            }
        }
        return $token;
    }


    /**
     * 发送腾讯IM聊天
     * @Author Nihuan
     * @Version 1.0
     * @Date 16-10-28
     * @param $fromId
     * @param $uid
     * @param $content
     * @return bool
     */
    public function sendImSms($fromId, $uid, $content){
        if( !is_numeric($fromId) || !is_numeric($uid) || empty($fromId) || empty($uid) || empty($content) ){
            return false;
        }else {
            $im = self::imConfig();
            if($this->switch_qm == 1){
                $params = ['fromId' => $fromId, 'toId' => $uid, 'content' => $content, 'step' => 3];
                $curl_params = ['url' => $this->qm_server_url,'timeout' => 5];
                $curl_params['post_params'] = json_encode([
                    'message' => $params
                ]);
                $curl_result = self::publicCURL($curl_params, 'post');
                return $curl_result;
            }else{
                $im_version = $im['IM_YUN_VERSION'];
                $content_arr = json_decode($content,true);
                $params = [
                    'SyncOtherMachine' => 2,
                    'MsgRandom' => rand(1, 65535),
                    'MsgTimeStamp' => time(),
                    'From_Account'=>$fromId,
                    'To_Account' => $uid,
                    'MsgBody' => [['MsgType'=>'TIMCustomElem','MsgContent'=> ['Data' => $content , 'Desc' => is_null($content_arr['msgContent']) ? '' : $content_arr['msgContent']]]],
                    'OfflinePushInfo' => ['PushFlag' => 0, 'Ext' => '']
                ];
                $paramsString = json_encode($params,JSON_UNESCAPED_UNICODE);
                $parameter = self::IMService();

                $curl_params = ['url'=>'https://console.tim.qq.com/' . $im_version . '/openim/sendmsg?' . $parameter, 'timeout'=>15];
                $curl_params['post_params'] = $paramsString;
                $curl_result = self::publicCURL($curl_params, 'post');

                $reStatus = json_decode($curl_result);
                if($reStatus->ErrorCode == 0) {
                    return true;
                }
                else {
                    return $reStatus->ErrorCode;
                }
            }
        }
    }


    //253短信通知发送
    public function sendSms($phone,$content)
    {
        $sendSms = ['phone'=>$phone, 'msg'=>$content,'un' => 'aaaaa','pw' => 'bbbbb','rd' => 1];
        $postArr = http_build_query($sendSms);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,'http://sms.253.com/msg/send?');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);

        curl_setopt($ch,CURLOPT_POST,TRUE);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postArr);
        $result = curl_exec($ch);
        curl_close($ch);
        $result=preg_split("/[,\r\n]/",$result);
        if($result[1] == 0){
            return true;
        }else{
            return $result[1];
        }
    }


    /**
     * 公共curl方法
     * @Author Nihuan
     * @Version 1.0
     * @Date 18-5-22
     * @param $params
     * @param string $request_type
     * @return mixed
     */
    private function publicCURL($params, $request_type = 'get') {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $params['url']);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $params['timeout']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        if( isset($params['other_options']) ) {
            curl_setopt_array($ch, $params['other_options']);
        }

        if($request_type === 'post') {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            if (isset($params['post_params'])) curl_setopt($ch,CURLOPT_POSTFIELDS,$params['post_params']);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}