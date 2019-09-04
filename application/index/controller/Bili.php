<?php

namespace app\index\controller;


use QL\QueryList;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use think\console\Command;
use think\Db;


class Bili
{
    public $sql;
    public $client;
    public function get()
    {
        set_time_limit(0);
        $this->client = new Client();
        $requests = function ($total) {
            for ($i = 7522; $i < $total; $i++) {
                $url = "https://api.bilibili.com/x/space/acc/info?mid=$i&jsonp=jsonp";
                yield new Request('GET', $url);
            }
        };
        $pool = new Pool($this->client, $requests(10000), [
            'concurrency' => 10,
            'delay' => '1500',
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


    public function fans()
    {
        $url = "https://api.bilibili.com/x/relation/followings?vmid=2673037&pn=1&ps=20&order=desc&jsonp=jsonp&callback=__jp9";
        $ql=QueryList::get($url,
            [
                'headers' => [
                    'User-Agent' => ' Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:68.0) Gecko/20100101 Firefox/68.0',
                    'Cookie'=>'LIVE_BUVID=AUTO7615675843621183; _uuid=0B3C61E6-2E30-C596-7C23-CA1FF7F8C0D462923infoc; buvid3=B23ED554-87FF-4C0D-B46D-A4969D0E39FF190944infoc; UM_distinctid=16cfb4f29ba6e-001fad5e2de3468-4c312272-1fa400-16cfb4f29beb4',
                    'Host'=>'api.bilibili.com',
                    'Conection'=>'keep-alive',
                    'Accept'=>'*/*',
                    'Accept-Language'=>'zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2',
                    'Accept-Encoding'=>'gzip, deflate, br',
                    'Referer'=>'https://space.bilibili.com/2673037/fans/follow?tagid=-1',
                    'Pragma'=>'no-cache',
                    'Cache-Control'=>'no-cache'
                ]
            ]);
        print_r($ql->getHtml());
    }

}