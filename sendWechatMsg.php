<?php
/**
 * Created by PhpStorm.
 * User: nihuan
 * Date: 18-3-15
 * Time: 下午5:28
 * Desc: 微信公众号推送类
 */

class sendWechatMsg
{
    /**
     * 获取微信通知模板
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-01-16
     * @param string $keyword
     * @return mixed
     */
    public function get_wechat_template($keyword = ''){
        $template = self::wechatMsg();
        $wechatTemp = $template[$keyword];

        return $wechatTemp;
    }


    /**
     * 微信模板推送信息
     * @Author Nihuan
     * @Version 1.0
     * @Date 18-3-15
     * @param string $toUser 对方openid
     * @param array $info   要放入的信息
     * @param string $template_id  模版id
     * @param string $url 跳转链接,不需要跳转传空
     * @param $redis
     * @return bool|mixed
     */
    public function sendTemplet($toUser,$info,$template_id,$url = '',$redis) {
        echo '发送给:' . $toUser . PHP_EOL;
        if(empty($toUser))return false;
        $data['touser'] = $toUser;
        $data['template_id'] = $template_id;
        $url = urldecode($url);
        $data['url'] = is_null($url) ? '' : $url;
        $info['remark']['value'] = "\n" . $info['remark']['value'];
        unset($info['url']);
        unset($info['temp_id']);
        $data['data'] = $info;

        $access_token = self::getAccessToken($redis);
        $ud = curl_init();
        curl_setopt($ud,CURLOPT_URL,"https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token);
        curl_setopt($ud, CURLOPT_CUSTOMREQUEST, strtoupper('POST'));
        curl_setopt($ud, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ud, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ud, CURLOPT_HTTPHEADER, ["Accept-Charset: utf-8"]);
        curl_setopt($ud, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ud, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ud, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ud, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ud, CURLOPT_RETURNTRANSFER, true);
        $tmp = curl_exec($ud);
        echo $tmp . PHP_EOL;
        if (curl_errno($ud)) {
            echo 'Errno'.curl_error($ud);
            return false;
        }
        curl_close($ud);
        return json_decode($tmp,true);
    }


    /**
     * 获取微信token
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-04-25
     * @param $redis
     * @return bool|string
     */
    private function getAccessToken($redis){
        $wechat = [
            'wechat_type' => 'client_credential',
            'wechat_appid' => 'wx123456',
            'wechat_secret' => 'c45121245142',
        ];
        $access_token = $redis->get('access_token');
        $wechat_type = $wechat['wechat_type'];
        $wechat_appid = $wechat['wechat_appid'];
        $wechat_secret = $wechat['wechat_secret'];
        if(empty($access_token)||$access_token==null){
            $res = file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=' . $wechat_type . '&appid='. $wechat_appid.'&secret=' . $wechat_secret);
            $res = json_decode($res, true);
            $access_token = $res['access_token'];
            $redis->setex("access_token",50,$access_token);
        }
        return $access_token;
    }


    /**
     * 微信模板消息
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-04-25
     * @return array
     */
    private function wechatMsg(){
        return [
            //新订单通知
            'newOrder' => [
                'temp_id' => 'TEyxx7R2ihhAR37CHs1JenKfx5xW5aP1QPBCSrHYJzE',
                'first' => ['value' => '您收到了一笔新的订单'],
                'keyword1' => ['value' => ''],//提交时间
                'keyword2' => ['value' => ''],//订单类型
                'keyword3' => ['value' => ''],
                'keyword4' => ['value' => ''],
                'keyword5' => ['value' => ''],
                'remark' => ['value' => '点这里！可直接处理该订单。', 'color' => '#173177'],
                'url' => 'http://localhost/wechat/index.new.html#buyorderdetail/>X<|ordertype=1',
            ],
            //订单待支付
            'will_pay' => [
                'temp_id' => 'PGWWPIhWPSxr16YRyIdbcz4Z6g3ZaPm1-xU2ZOkL1Fs',
                'first' => ['value' => ''],
                'keyword1' => ['value' => ''],
                'keyword2' => ['value' => ''],
                'keyword3' => ['value' => ''],
                'keyword4' => ['value' => ''],
                'remark' => ['value' => '', 'color' => '#173177'],
                'url' => 'http://localhost/wechat/index.new.html#buyorderdetail/',
            ],
            //未读留言通知
            'unread_msg' => [
                'temp_id' => 'DnDpFChXKNgpSaF9VIfuoWd2YxJ1Uq5fyM98xnGjanU',
                'first' => ['value' => ''],
                'keyword1' => ['value' => ''],
                'keyword2' => ['value' => ''],
                'keyword3' => ['value' => ''],
                'remark' => ['value' => '', 'color' => '#173177'],
                'url' => 'https://localhost/web/chat.new.html#chat/>X<|>Y<',
            ]
        ];
    }
}