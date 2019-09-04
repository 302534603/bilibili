<?php
namespace app\home\command;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;



class  Test extends  Command{
    public $client;
    protected function configure()
    {
        $this->setName('test')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
//        echo 123;
       $this->get();
    }
    public function get()
    {
        set_time_limit(0);
        $this->client = new Client();
        $requests = function ($total) {
            for ($i = 10000; $i < $total; $i++) {
                $url = "https://api.bilibili.com/x/space/acc/info?mid=$i&jsonp=jsonp";
                yield new Request('GET', $url);
            }
        };
        $pool = new Pool($this->client, $requests(15000), [
            'concurrency' => 15,
            'fulfilled' => function ($response, $index) {
                sleep(1);
                //成功后的请求
                $res = json_decode($response->getbody()->getContents(), true);
                if ($res['code'] == 0) {
                    $t['name'] = $res['data']['name'];
                    $t['mid'] = $res['data']['mid'];
                    $t['sex'] = $res['data']['sex'];
                    $t['level'] = $res['data']['level'];
                    $url = "https://api.bilibili.com/x/relation/stat?jsonp=jsonp&vmid=" . $t['mid'];
                    $info = json_decode($this->client->get($url)->getbody()->getContents(), true);
                    $t['following'] = $info['data']['following'];
                    $t['follower'] = $info['data']['follower'];
                    DB::table('bili')->insert($t);
                }
            },
            'rejected' => function ($reason, $index) {
                sleep(1);
            },
        ]);
        $promise = $pool->promise();
        $promise->wait();
    }
}