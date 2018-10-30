<?php

namespace Zhjun\Address;
use GuzzleHttp\Client;
use Zhjun\Address\Exceptions\AddressException;
use Zhjun\Address\Exceptions\DistanceException;
use Zhjun\Address\Exceptions\HttpException;
use Zhjun\Address\Exceptions\InvalidArgumentException;
use Zhjun\Address\Exceptions\KeyException;

class Address
{
    protected $gKey;//高德API密匙

    protected $bKey;//百度API密匙

    protected $guzzleOptions = [];

    public function __construct($gKey='',$bKey='')
    {
        if(!$gKey){//如果$gKey不存在就读取配置中的$gKey
            if(!function_exists('config')){
                function config(){
                    return dirname(__FILE__).'/Copy/config';
                }
            }
            $key1 = config('services.map.GaoKey');
        }else{
            $key1 = $gKey;
        }

        $this->gKey = $this->rand($key1);

        if(!$bKey){//如果bKey不存在就读取配置中的bKey
            if(!function_exists('config')){
                function config(){
                    return dirname(__FILE__).'/Copy/config';
                }
            }
            $key2 = config('services.map.BaiKey');
        }else{
            $key2 = $bKey;
        }
        $this->bKey = $this->rand($key2);

    }
    public function config(){

    }
    //对多个key值进行处理
    public function rand($key){
        $key = explode(',',$key);
        $rand= array_rand($key,1);
        return $key[$rand];
    }

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    public function setGuzzleOptions(array $options){

        $this->guzzleOptions = $options;
    }
    /**
     * 根据搜索地址获取（高德地图）
     */
    public function gaodeSearch($keywords,$city,$types='',$children=1,$offset=20,$page=1,$output='json',$extensions='all'){

        $url = 'https://restapi.amap.com/v3/place/text';//高德API服务地址搜索

        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }
        if (!\in_array(\strtolower($extensions), ['base', 'all'])) {
            throw new InvalidArgumentException('Invalid type value(base/all): '.$extensions);
        }
        try{
            $query = array_filter([ //高德地图参数
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
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();
            // 4. 返回值根据 $output 返回不同的格式，
            // 当 $output 为 json 时，返回数组格式，否则为 xml。
            $data = $output === 'json' ? \json_decode($response, true) : $response;
            if($data['info'] != "OK"){
                throw new KeyException('gaode Key exception');
            }
            if(count($data['pois'])<1){
                return [];
            }
            return $data;
        }catch(\Exception $e){

            throw new HttpException($e->getMessage(),$e->getCode(),$e);

        }


    }

    /**
     * @param $keywords
     * @param $city
     * @param string $types
     * @param int $children
     * @param int $offset
     * @param int $page
     * @param string $output
     * @param string $extensions
     * @throws InvalidArgumentException
     * 根据地址搜索(百度地图)
     */
    public function baiduSearch($keywords,$city,$types='',$children=1,$offset=20,$page=1,$output='json',$extensions='all'){

        $url = 'http://api.map.baidu.com/place/v2/search';//百度API服务地址
        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }

        if (!\in_array(\strtolower($extensions), ['base', 'all'])) {
            throw new InvalidArgumentException('Invalid type value(base/all): '.$extensions);
        }
        try {
            $query = array_filter([ //百度地图参数
                'ak' => $this->bKey,
                'query'=>$keywords,
                'region' => $city,
                'tag' =>$types,
                'output' => $output,
            ]);
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            $data = $output === 'json' ? \json_decode($response, true) : $response;
            if($data['message'] != "ok"){
                throw new KeyException('baidu Key exception');
            }

            if(count($data['results'])<1) {
                return [];
            }
            return $data;
        }catch(\Exception $e){

            throw new HttpException($e->getMessage(),$e->getCode(),$e);
        }


    }

    /**
     * @param $location
     * @param int $radius
     * @param string $extensions
     * @param string $output
     * @param string $batch
     * @return mixed|string
     * @throws HttpException
     * @throws InvalidArgumentException
     * 根据坐标搜索地址
     */
    public function getLocation($location,$radius=1000,$extensions='all',$output='json',$batch="false"){

        $url = 'https://restapi.amap.com/v3/geocode/regeo';//API服务地址

        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }

        // 2. 封装 query 参数，并对空值进行过滤。
        $query = array_filter([
            'key' => $this->gKey,
            'location'=>$location,
            'radius' => $radius,
            'batch'=>$batch,
            'output' => $output,
            'extensions' => $extensions,
        ]);
        try {
            // 3. 调用 getHttpClient 获取实例，并调用该实例的 `get` 方法，
            // 传递参数为两个：$url、['query' => $query]，
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            // 4. 返回值根据 $output 返回不同的格式，
            // 当 $output 为 json 时，返回数组格式，否则为 xml。

            return $data = $output === 'json' ? \json_decode($response, true) : $response;
        } catch (\Exception $e) {
            // 5. 当调用出现异常时捕获并抛出，消息为捕获到的异常消息，
            // 并将调用异常作为 $previousException 传入。
            throw new HttpException($e->getMessage(),$e->getCode(),$e);
        }
    }

    /**
     * 计算百度地图和高德地图同一地址的经纬距离
     */
    public function getAddress($keywords,$city,$mode=2,$distance=500,$types=''){

        $data = [];
        if($mode == 1){//严格模式

            $gaodeSearch = $this->gaodeSearch($keywords,$city);

            $baiduSearch = $this->baiduSearch($keywords,$city);

            if(count($gaodeSearch)<1){

                $baiduSearch = $baiduSearch['results'][0]['location'];

                $data['gaode_location'] = [
                    'lat'=>false,
                    'lng'=>false,
                ];
                $data['baidu_location'] = [
                    'lat'=>number_format($baiduSearch['lat'],6),
                    'lng'=>number_format($baiduSearch['lng'],6),
                ];
                return $data;//高德地图经纬度;
            }
            if(count($baiduSearch)<1){
                $location = $gaodeSearch['pois'][0]['location'];
                $location = explode(',',$location);
                $data['gaode_location'] = [
                    'lat'=>$location[1],
                    'lng'=>$location[0],
                ];
                $data['baidu_location'] = [
                    'lat'=>false,
                    'lng'=>false,
                ];
                return $data;//高德地图经纬度;
            }
            if($gaodeSearch['info'] == "OK" && $baiduSearch['message'] == 'ok' && count($gaodeSearch['pois'])>0 && count($baiduSearch['results'])){
                $gaode_loca = $gaodeSearch['pois'][0]['location'];
                $gaode_loca = explode(',',$gaode_loca);//高德地图经纬度
                $bai_loca = $baiduSearch['results'][0]['location'];//百度地图经纬度
                $distances = $this->get_distance($gaode_loca,$bai_loca);//计算经纬度之间的距离
                if($distances>$distance){//如果距离超出规定的距离就抛出异常
                    throw new DistanceException('address resolution exception:'.$distances);
                }
                $data['gaode_location'] = [
                    'lat'=>$gaode_loca[1],
                    'lng'=>$gaode_loca[0],
                ];
                $data['baidu_location'] = [
                    'lat'=>number_format($baiduSearch['lat'],6),
                    'lng'=>number_format($baiduSearch['lng'],6),
                ];
                $data['distance'] = $distances;
                return $data;
            }
        }else{//宽松模式
            $gaodeSearch = $this->gaodeSearch($keywords,$city);
            if($gaodeSearch['info'] == "OK" && count($gaodeSearch['pois'])>0){
                $gaode_loca = $gaodeSearch['pois'][0]['location'];
                $location = explode(',',$gaode_loca);
                $data['gaode_location'] = [
                    'lat'=>$location[1],
                    'lng'=>$location[0],
                ];
                return $data;
            }

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
    //根据地址模糊搜索
    public function search($keywords,$city,$type=1){
        $data = [];
        if($type == 1){
            $gaodeSearch = $this->gaodeSearch($keywords,$city);
            $data['status'] = $gaodeSearch['status'];
            //$data['count'] = $gaodeSearch['count'];
            $data['message'] = $gaodeSearch['info'];
            //$data['infocode'] = $gaodeSearch['infocode'];
            //$data['results'] = $gaodeSearch['infocode'];
            $tmp = [];
            foreach($gaodeSearch['pois'] as $key=>$value){
                $tmp[$key]['id'] = $value['id'];
                $tmp[$key]['name'] = $value['name'];
                $tmp[$key]['type'] = $value['type'];
                $tmp[$key]['address'] = $value['address'];
                $tmp[$key]['location'] = $value['location'];
                //$tmp[$key]['tel'] = $value['tel'];
                $tmp[$key]['pcode'] = $value['pcode'];
                $tmp[$key]['province'] = $value['pname'];
                $tmp[$key]['citycode'] = $value['citycode'];
                $tmp[$key]['city'] = $value['cityname'];
                //$tmp[$key]['adcode'] = $value['adcode'];
                $tmp[$key]['area'] = $value['adname'];
            }
            $data['results'] = $tmp;

        }else{
            $baiduSearch = $this->baiduSearch($keywords,$city);
            $data['status'] = $baiduSearch['status'];
            $data['message'] = $baiduSearch['message'];
            $tmp = [];
            foreach($baiduSearch['results'] as $key=>$value){
                $tmp[$key]['name'] = $value['name'];
                $tmp[$key]['location'] = $value['location']['lat'].','.$value['location']['lng'];
                $tmp[$key]['address'] = $value['address'];
                $tmp[$key]['province'] = $value['province'];
                $tmp[$key]['city'] = $value['city'];
                $tmp[$key]['area'] = $value['area'];
                $tmp[$key]['uid'] = $value['uid'];
            }
            $data['results'] = $tmp;
        }
        return $data;
    }

    /**
     * @param $keywords
     * @param $city
     * @param string $type
     * @param string $citylimit
     * @param string $datatype
     * @param string $output
     * @return mixed|string
     * @throws HttpException
     * @throws InvalidArgumentException
     */
    public function addreeSearch($keywords,$city,$type='',$citylimit='all',$datatype='all',$output="json"){
        $url = 'https://restapi.amap.com/v3/assistant/inputtips';//API服务地址

        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }

        // 2. 封装 query 参数，并对空值进行过滤。
        $query = array_filter([
            'key' => $this->gKey,
            'keywords'=>$keywords,
            'city' => $city,
            'type'=>$type,
            'citylimit'=>$citylimit,
            'output' => $output,
            'datatype' => $datatype,
        ]);
        try {
            // 3. 调用 getHttpClient 获取实例，并调用该实例的 `get` 方法，
            // 传递参数为两个：$url、['query' => $query]，
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            // 4. 返回值根据 $output 返回不同的格式，
            // 当 $output 为 json 时，返回数组格式，否则为 xml。
            return $data = $output === 'json' ? \json_decode($response, true) : $response;
        } catch (\Exception $e) {
            // 5. 当调用出现异常时捕获并抛出，消息为捕获到的异常消息，
            // 并将调用异常作为 $previousException 传入。
            throw new HttpException($e->getMessage(),$e->getCode(),$e);
        }
    }
}
