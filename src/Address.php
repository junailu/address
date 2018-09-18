<?php

namespace Zhjun\Address;
use GuzzleHttp\Client;
use Zhjun\Address\Exceptions\HttpException;
use Zhjun\Address\Exceptions\InvalidArgumentException;

class Address
{
    protected $key;

    protected $type;
    protected $guzzleOptions = [];

    public function __construct($key='',$type)
    {
        if(!$key){//如果key不存在就读取配置中的key
            $this->key = $_ENV('MAP_API_KEY');
        }else{
            $this->key = $key;
        }
        $this->type = $type;
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

    public function getAddress($address,$city='',$batch='false',$output='json',$callback='showLocation'){

        $gaode_url = 'https://restapi.amap.com/v3/geocode/geo';//高德API服务地址
        $baidu_url = 'http://api.map.baidu.com/geocoder/v2/';//百度API服务地址


        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }

        // 2. 封装 query 参数，并对空值进行过滤。
        if($this->type == 'gaode'){
            $query = array_filter([
                'key' => $this->key,
                'address'=>$address,
                'city' => $city,
                'batch'=>$batch,
                'output' => $output,
            ]);
        }else{
            $query = array_filter([
                'ak' => $this->key,
                'address'=>$address,
//                'city' => $city,
                'output' => $output,
                'callback'=>$callback,
            ]);
        }
        try {
            // 3. 调用 getHttpClient 获取实例，并调用该实例的 `get` 方法，
            // 传递参数为两个：$url、['query' => $query]，
            if($this->type == 'gaode'){
                $response = $this->getHttpClient()->get($gaode_url, [
                    'query' => $query,
                ])->getBody()->getContents();
                // 4. 返回值根据 $output 返回不同的格式，
                // 当 $output 为 json 时，返回数组格式，否则为 xml。
                return $output === 'json' ? \json_decode($response, true) : $response;
            }else{
                $response = $this->getHttpClient()->get($baidu_url, [
                    'query' => $query,
                ])->getBody()->getContents();

                var_dump($response);
                return $response;
            }

        } catch (\Exception $e) {
            // 5. 当调用出现异常时捕获并抛出，消息为捕获到的异常消息，
            // 并将调用异常作为 $previousException 传入。
            throw new HttpException($e->getMessage(),$e->getCode(),$e);
        }

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

}