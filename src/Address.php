<?php

namespace Zhjun\Address;
use GuzzleHttp\Client;
use Zhjun\Address\Exceptions\HttpException;
use Zhjun\Address\Exceptions\InvalidArgumentException;

class Address
{
    protected $gKey;//高德API密匙

    protected $bKey;//百度API密匙

    protected $guzzleOptions = [];

    public function __construct($gKey='',$bKey='')
    {
        if(!$gKey){//如果$gKey不存在就读取配置中的$gKey
            $this->gKey = $_ENV['GAO_API_KEY'];
        }else{
            $this->gKey = $gKey;
        }
        if(!$bKey){//如果bKey不存在就读取配置中的bKey
            $this->bKey = $_ENV['BAI_API_KEY'];
        }else{
            $this->bKey = $bKey;
        }
    }

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    public function setGuzzleOptions(array $options){

        $this->guzzleOptions = $options;
    }

    /**
     * @param $
     *
     */

    public function getAddress($address,$city='',$tag='',$batch='false',$output='json'){

        $gaode_url = 'https://restapi.amap.com/v3/geocode/geo';//高德API服务地址
        $baidu_url = 'http://api.map.baidu.com/place/v2/search';//百度API服务地址
        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }

        // 2. 封装 query 参数，并对空值进行过滤。
        $gaode_query = array_filter([ //高德地图参数
            'key' => $this->gKey,
            'address'=>$address,
            'city' => $city,
            'batch'=>$batch,
            'output' => $output,
        ]);
        $baidu_query = array_filter([ //百度地图参数
            'ak' => $this->bKey,
            'query'=>$address,
            'region' => $city,
            'tag' =>$tag,
            'output' => $output,
        ]);
        try {
            // 3. 调用 getHttpClient 获取实例，并调用该实例的 `get` 方法，
            // 传递参数为两个：$url、['query' => $query]，

            $response = $this->getHttpClient()->get($gaode_url, [
                'query' => $gaode_query,
            ])->getBody()->getContents();
            // 4. 返回值根据 $output 返回不同的格式，
            // 当 $output 为 json 时，返回数组格式，否则为 xml。
            $gaode_rpe = $output === 'json' ? \json_decode($response, true) : $response;
            $gaode_loca = $gaode_rpe['geocodes'][0]['location'];
            $gaode_loca = explode(',',$gaode_loca);

            $response = $this->getHttpClient()->get($baidu_url, [
                'query' => $baidu_query,
            ])->getBody()->getContents();

            $baidu_rpe = $output === 'json' ? \json_decode($response, true) : $response;
            $bai_loca = $baidu_rpe['results'][0]['location'];
            $distance = $this->get_distance($gaode_loca,$bai_loca);//计算经纬度之间的距离
            return $distance;

        } catch (\Exception $e) {
            // 5. 当调用出现异常时捕获并抛出，消息为捕获到的异常消息，
            // 并将调用异常作为 $previousException 传入。
            throw new HttpException($e->getMessage(),$e->getCode(),$e);
        }

    }

    /**
     * 根据搜索地址获取
     */
    public function search($keywords,$city,$types='',$children=1,$offset=20,$page=1,$output='json',$extensions='all'){

        $gaode_url = 'https://restapi.amap.com/v3/place/text';//高德API服务地址搜索

        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }
        if (!\in_array(\strtolower($extensions), ['base', 'all'])) {
            throw new InvalidArgumentException('Invalid type value(base/all): '.$extensions);
        }

        $gaode_query = array_filter([ //高德地图参数
            'key' => $this->gKey,
            'keywords'=>$keywords,//地址
            'types'=>$types,//类型
            'city' => $city,//市
            'children'=>$children,
            'offset'=>$offset,//每页记录数据
            'page'=>$page,//页数
            'output' => $output,
            'extensions' => $extensions,
        ]);
        $response = $this->getHttpClient()->get($gaode_url, [
            'query' => $gaode_query,
        ])->getBody()->getContents();
        // 4. 返回值根据 $output 返回不同的格式，
        // 当 $output 为 json 时，返回数组格式，否则为 xml。
        $gaode_rpe = $output === 'json' ? \json_decode($response, true) : $response;
        return $gaode_rpe;

    }

    public function getLocation($location,$radius=1000,$extensions='all',$output='json',$batch="false"){
        $url = 'https://restapi.amap.com/v3/geocode/regeo';//API服务地址

        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }


        // 2. 封装 query 参数，并对空值进行过滤。
        $query = array_filter([
            'key' => $this->key,
            'location'=>$location,
            'radius' => $radius,
            'batch'=>$batch,
            'output' => $output,
            'extensions' => $extensions,
            //'roadlevel' => $roadlevel,
        ]);
        try {
            // 3. 调用 getHttpClient 获取实例，并调用该实例的 `get` 方法，
            // 传递参数为两个：$url、['query' => $query]，
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            // 4. 返回值根据 $output 返回不同的格式，
            // 当 $output 为 json 时，返回数组格式，否则为 xml。

            return $output === 'json' ? \json_decode($response, true) : $response;
        } catch (\Exception $e) {
            // 5. 当调用出现异常时捕获并抛出，消息为捕获到的异常消息，
            // 并将调用异常作为 $previousException 传入。
            throw new HttpException($e->getMessage(),$e->getCode(),$e);
        }
    }

    /**
     * @param $from
     * @param $to
     * @param bool $km
     * @param int $decimal
     * @return float
     * 计算经纬度距离
     */
    public function get_distance($from, $to, $km = true, $decimal = 2) {
        $from = array_values($from);
        $to = array_values($to);

        sort($from);
        sort($to);
        $EARTH_RADIUS = 6370.996; // 地球半径系数
        $distance = $EARTH_RADIUS * 2 * asin(sqrt(pow(sin(($from[0] * pi() / 180 - $to[0] * pi() / 180) / 2), 2) + cos($from[0] * pi() / 180) * cos($to[0] * pi() / 180) * pow(sin(($from[1] * pi() / 180 - $to[1] * pi() / 180) / 2), 2))) * 1000;

        if ($km) {
            $distance = $distance;
        }
        return round($distance, $decimal);
    }
}