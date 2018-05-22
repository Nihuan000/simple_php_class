<?php

/**
 * Created by PhpStorm.
 * User: nihuan
 * Date: 17-8-23
 * Time: 下午6:05
 */
require dirname(__FILE__) . '/../../EsPHP/vendor/autoload.php';
class EsCustom
{
    private $client;
    private $index = 'chat_record';
    private $type = 'chat_response';
    protected $bulk_template;

    public function __construct()
    {
        $hosts = array(
            '127.0.0.1:8200'
        );
        $this->es = Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();

        $this->bulk_template = [
            'request_time' => 0,
            'from_name' => '',
            'from_id' => 0,
            'target_id' => 0,
            'target_name' => '',
            'response_time' => 0,
            'answer_times' => 0
        ];
    }


    /**
     * 索引数据公共方法
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-08-23
     * @param $chat_id
     * @param $type
     * @param array $body
     * @return mixed
     */
    public function chat_index($chat_id,$type,$body = []){
        return $this->client->index(['id' => $chat_id, 'index' => $this->index, 'type' => $type, 'body' => $body]);
    }


    /**
     * 批量导入聊天时长
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-12-15
     * @param array $data
     * @return mixed
     */
    public function bulk_index($data = []){
        $bulk = ['index' => $this->index, 'type' => $this->type];
        foreach ($data as $item){
            $bulk_template = $this->bulk_template;
            $bulk_template['request_time'] = (int)$item['request_time'];
            $bulk_template['from_name'] = $item['from_name'];
            $bulk_template['from_id'] = (int)$item['from_id'];
            $bulk_template['target_id'] = (int)$item['target_id'];
            $bulk_template['target_name'] = $item['target_name'];
            $bulk_template['response_time'] = (int)$item['response_time'];
            $bulk_template['answer_times'] = (int)$item['answer_times'];
            echo 'from_name:' . $bulk_template['from_name'] . PHP_EOL;
            echo 'target_name:' . $bulk_template['target_name'] . PHP_EOL;
            echo 'request_time:' . $bulk_template['request_time'] . PHP_EOL;
            echo 'answer_times:' . $bulk_template['answer_times'] . PHP_EOL;

            $bulk['body'][]=array(
                'index' => array(
                    '_id'=>$item['chat_id']
                ),
            );
            $bulk['body'][]=$bulk_template;
        }
        $bulkRes = $this->client->bulk($bulk);
        return $bulkRes;
    }

}
