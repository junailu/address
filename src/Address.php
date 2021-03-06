<?php

namespace Zhjun\Address;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Location\Coordinate;
use Zhjun\Address\Exceptions\HttpException;
use Zhjun\Address\Exceptions\InvalidArgumentException;
use Zhjun\Address\Exceptions\KeyException;

class Address
{
    protected $gKey;//高德API密匙

    protected $bKey;//百度API密匙

    protected $qKey;//百度API密匙

    protected $guzzleOptions = [];

    protected $geoKey = [
        '10001'=>'key不正确或过期',
        '10002'=>'没有权限使用相应的服务或者请求接口的路径拼写错误',
        '10003'=>'访问已超出日访问量',
        '10004'=>'单位时间内访问过于频繁',
        '10005'=>'IP白名单出错，发送请求的服务器IP不在IP白名单内',
        '10006'=>'绑定域名无效',
        '10007'=>'数字签名未通过验证',
        '10008'=>'MD5安全码未通过验证',
        '10009'=>'请求key与绑定平台不符',
        '10010'=>'IP访问超限',
        '10011'=>'服务不支持https请求',
        '10012'=>'权限不足，服务请求被拒绝',
        '10013'=>'Key被删除',
        '10014'=>'云图服务QPS超限',
        '10015'=>'受单机QPS限流限制',
        '10016'=>'服务器负载过高',
        '10017'=>'所请求的资源不可用',
        '10019'=>'使用的某个服务总QPS超限',
        '10020'=>'某个Key使用某个服务接口QPS超出限制',
        '10021'=>'来自于同一IP的访问，使用某个服务QPS超出限制',
        '10022'=>'某个Key，来自于同一IP的访问，使用某个服务QPS超出限制',
        '10023'=>'某个KeyQPS超出限制',
        '20000'=>'请求参数非法',
        '20001'=>'缺少必填参数',
        '20002'=>'请求协议非法',
        '20003'=>'其他未知错误',
        '20011'=>'查询坐标或规划点（包括起点、终点、途经点）在海外，但没有海外地图权限',
    ];

    protected $isDebug      = false;
    protected $errorRange   = 2000;

    public function __construct($gKey='', $bKey='', $qKey='')
    {
        if (!$gKey) {//如果$gKey不存在就读取配置中的$gKey
            if (!function_exists('config')) {
                function config()
                {
                    return dirname(__FILE__).'/Copy/config';
                }
            }
            $key1 = config('services.map.GaoKey');
        } else {
            $key1 = $gKey;
        }

        $this->gKey = $this->rand($key1);

        if (!$bKey) {//如果bKey不存在就读取配置中的bKey
            if (!function_exists('config')) {
                function config()
                {
                    return dirname(__FILE__).'/Copy/config';
                }
            }
            $key2 = config('services.map.BaiKey');
        } else {
            $key2 = $bKey;
        }
        $this->bKey = $this->rand($key2);


        if (!$qKey) {//如果qKey不存在就读取配置中的qKey
            if (!function_exists('config')) {
                function config()
                {
                    return dirname(__FILE__).'/Copy/config';
                }
            }
            $key3 = config('services.map.qKey');
        } else {
            $key3 = $qKey;
        }
        $this->qKey = $this->rand($key3);
    }
    public function config()
    {
    }
    //对多个key值进行处理
    public function rand($key)
    {
        $key = explode(',', $key);
        $rand= array_rand($key, 1);
        return $key[$rand];
    }

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }
    /**
     * 根据搜索地址获取（高德地图）
     */
    public function gaodeSearch($keywords, $city, $types='', $children=1, $offset=20, $page=1, $output='json', $extensions='all')
    {
        $url = 'https://restapi.amap.com/v3/place/text';//高德API服务地址搜索

        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }
        if (!\in_array(\strtolower($extensions), ['base', 'all'])) {
            throw new InvalidArgumentException('Invalid type value(base/all): '.$extensions);
        }
        try {
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
            if ($data['info'] != "OK") {
                throw new KeyException($this->geoKey[$data['infocode']]);
            }
            if (count($data['pois'])<1) {
                return [];
            }
            return $data;
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
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
    public function baiduSearch($keywords, $city, $types='', $children=1, $offset=20, $page=1, $output='json', $extensions='all')
    {
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
            if ($data['message'] != "ok") {
                throw new KeyException($data['message']);
            }

            if (count($data['results'])<1) {
                return [];
            }
            return $data;
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
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
    public function getLocation($location, $radius=1000, $extensions='all', $output='json', $batch="false")
    {
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
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 计算百度地图和高德地图同一地址的经纬距离
     */
    public function getAddress($keywords, $city, $mode=2, $distance=500, $types='')
    {
        $data = [];
        if ($mode == 1) {//严格模式

            $gaodeSearch = $this->geoLocation($keywords, $city);

            $baiduSearch = $this->baiduSearch($keywords, $city);

            if (count($gaodeSearch['gaode_location'])<1) {
                if (!$baiduSearch) {
                    return false;
                }
                $baiduSearch = $baiduSearch['results'][0]['location'];

                $data['gaode_location'] = [
                    'lat'=>false,
                    'lng'=>false,
                ];
                $data['baidu_location'] = [
                    'lat'=>number_format($baiduSearch['lat'], 6),
                    'lng'=>number_format($baiduSearch['lng'], 6),
                ];
                return $data;//高德地图经纬度;
            }

            if (count($baiduSearch)<1) {
                if (!$gaodeSearch) {
                    return false;
                }
                $data['gaode_location'] = $gaodeSearch['gaode_location'];
                $data['baidu_location'] = [
                    'lat'=>false,
                    'lng'=>false,
                ];
                return $data;//高德地图经纬度;
            }
            if (count($gaodeSearch['gaode_location'])>0 && $baiduSearch['message'] == 'ok' && count($baiduSearch['results'])) {
                $gaode_loca = [$gaodeSearch['gaode_location']['lng'],$gaodeSearch['gaode_location']['lat']];//高德地图坐标
                $bai_loca = $baiduSearch['results'][0]['location'];//百度地图经纬度
                $bai_location = [$bai_loca['lng'],$bai_loca['lat']];
                $new_bai_location = $this->locationChange($bai_location);
                $bai_loca = $new_bai_location;
                $distances = $this->get_distance($gaode_loca, $bai_loca);//计算经纬度之间的距离
                $data['gaode_location'] = $gaodeSearch['gaode_location'];
                $data['baidu_location'] = [
                    'lat'=>number_format($bai_loca['lat'], 6),
                    'lng'=>number_format($bai_loca['lng'], 6),
                ];
                $data['distance'] = $distances;
                return $data;
            }
        } else {//宽松模式
            return $this->geoLocation($keywords, $city);
        }
    }

    public function geoLocation($keywords, $city)
    {
        $gaodeSearch = $this->geoAddress($keywords, $city);
        if ($gaodeSearch && isset($gaodeSearch['info'])) {
            if ($gaodeSearch['info'] == 'OK' && $gaodeSearch['count']>0) {
                $geo = $gaodeSearch['geocodes'];
                $data['site']['name'] = $geo[0]['city'];
                $data['site']['code'] = $geo[0]['citycode'];
            } else {
                $gaode = $this->gaodeSearch($keywords, $city);
                if ($gaode['info'] == "OK" && count($gaode['pois'])>0) {
                    $geo = $gaode['pois'];
                    $data['site']['name'] = $geo[0]['cityname'];
                    $data['site']['code'] = $geo[0]['citycode'];
                }
            }
        } else {
            $gaode = $this->gaodeSearch($keywords, $city);
            if ($gaode['info'] == "OK" && count($gaode['pois'])>0) {
                $geo = $gaode['pois'];
                $data['site']['name'] = $geo[0]['cityname'];
                $data['site']['code'] = $geo[0]['citycode'];
            }
        }
        $gaode_loca = $geo[0]['location'];
        $location = explode(',', $gaode_loca);
        $data['gaode_location'] = [
            'lat'=>$location[1],
            'lng'=>$location[0],
        ];
        return $data;
    }
    /**
     * @param $from
     * @param $to
     * @param bool $km
     * @param int $decimal
     * @return float
     * 计算经纬度距离
     */
    public function get_distance($from, $to, $km = true, $decimal = 2)
    {
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
    public function search($keywords, $city, $type=1)
    {
        $data = [];
        if ($type == 1) {
            $gaodeSearch = $this->gaodeSearch($keywords, $city);
            $data['status'] = $gaodeSearch['status'];
            //$data['count'] = $gaodeSearch['count'];
            $data['message'] = $gaodeSearch['info'];
            //$data['infocode'] = $gaodeSearch['infocode'];
            //$data['results'] = $gaodeSearch['infocode'];
            $tmp = [];
            foreach ($gaodeSearch['pois'] as $key=>$value) {
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
        } else {
            $baiduSearch = $this->baiduSearch($keywords, $city);
            $data['status'] = $baiduSearch['status'];
            $data['message'] = $baiduSearch['message'];
            $tmp = [];
            foreach ($baiduSearch['results'] as $key=>$value) {
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
    public function addreeSearch($keywords, $city, $type='', $citylimit='all', $datatype='all', $output="json")
    {
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
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param $ip
     * @param string $output
     * @return mixed
     * @throws HttpException
     * @throws InvalidArgumentException
     * 高德根据ip获取城市
     */
    public function getGeoIpCity($ip, $output='json')
    {
        $url = 'https://restapi.amap.com/v3/ip';

        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }
        // 2. 封装 query 参数，并对空值进行过滤。
        $query = array_filter([
            'key' => $this->gKey,
            'ip'=>$ip,
        ]);

        try {
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();
            return $data = $output === 'json' ? \json_decode($response, true) : $response;
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param $ip
     * @return mixed
     * @throws HttpException
     * 百度根据ip获取城市
     */
    public function getBaiIpCity($ip)
    {
        $url = 'https://api.map.baidu.com/location/ip';

        // 2. 封装 query 参数，并对空值进行过滤。
        $query = array_filter([ //百度地图参数
            'ak' => $this->bKey,
            'ip'=>$ip,
        ]);

        try {
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();
            return json_decode($response, true);
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param $address
     * @param $city
     * @param bool $batch
     * @param string $output
     * @return mixed|string
     * @throws HttpException
     * @throws InvalidArgumentException
     * 新根据地址获取位置
     */
    public function geoAddress($address, $city, $batch=true, $output='json')
    {
        $url = 'https://restapi.amap.com/v3/geocode/geo';

        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }
        // 2. 封装 query 参数，并对空值进行过滤。
        $query = array_filter([
            'key' => $this->gKey,
            'address' => $address,
            'city' => $city,
            'batch' => $batch,
        ]);
        try {
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();
            return $data = $output === 'json' ? \json_decode($response, true) : $response;
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }
    /**
     *把非高德的坐标转换为高德坐标
     */
    public function locationChange($location, $output='json')
    {
        $locations = $location[0].','.$location[1];
        $key = config('services.map.key');

        $url = 'https://restapi.amap.com/v3/assistant/coordinate/convert';

        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }
        try {
            $query = array_filter([
                'key' => $key,
                'locations'=>$locations,
                'coordsys'=>'baidu',
                'output'=>$output,
            ]);
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            $data = $output === 'json' ? \json_decode($response, true) : $response;
            if ($data['locations']) {
                $location = explode(',', $data['locations']);

                $location = ['lng'=>$location[0],'lat'=>$location[1]];
                return $location;
            } else {
                return $location;
            }
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }
    /**
     * @param $location
     * @param int $from
     * @param int $to
     * @param string $output
     * @return array
     * @throws HttpException
     * @throws InvalidArgumentException
     * 高德坐标转百度
     */
    public function geoChangeBai($location, $from=3, $to=5, $output='json')
    {
        $url = 'http://api.map.baidu.com/geoconv/v1/';

        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }
        try {
            $query = array_filter([
                'ak' => $this->bKey,
                'coords'=>$location,
                'from'=>$from,
                'to'=>$to,
                'output'=>$output
            ]);
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            $data = $output === 'json' ? \json_decode($response, true) : $response;

            if ($data['result'] && $data['status'] ==0) {
                $location = ['lng'=>$data['result'][0]['x'],'lat'=>$data['result'][0]['y']];
                return $location;
            } else {
                return $location;
            }
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 通过高德WEB接口定位
     * Author: CtrL
     * @param string $text
     * @return array
     */
    public function getWebAmapCoord($text = '')
    {
        $text       = trim($text);
        $jsonpStr   = 'jsonp_' . rand(100000, 999999) . '_';

        $data       = [
            's'         => 'rsv3',
            'children'  => '',
            'key'       => '8325164e247e15eea68b59e89200988b',
            'page'      => 1,
            'offset'    => 10,
            'language'  => 'zh_cn',
            'callback'  => $jsonpStr,
            'platform'  => 'JS',
            'logversion'=> '2.0',
            'sdkversion'=> '1.3',
            'appname'   => 'https://lbs.amap.com/console/show/picker',
            'csid'      => $this->createUUID(),
            'keywords'  => $text
        ];

        $coord  = [];
        $client = new Client();
        try {
            $url    = "https://restapi.amap.com/v3/place/text" . http_build_query($data);
            if ($this->isDebug) {
                dump($url);
            }
            $response   = $client->get($url, [
                'headers' => [
                    'User-Agent'    => 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36',
                    'Referer'       => 'https://lbs.amap.com/console/show/picker',
                ]
            ]);
            $result     = $response->getBody()->getContents();
            preg_match("/^{$jsonpStr}\((.*?)\)$/i", trim($result), $m);
            if ($m) {
                $location   = array_get(
                    array_first(
                        array_get(json_decode($m[1], true), 'pois', [])
                    ),
                    'location',
                    ''
                );
                if ($location && strpos($location, ',')) {
                    list($coord['lng'], $coord['lat'])  = explode(',', $location);
                }
            }
        } catch (\Throwable $throwable) {
        }

        return $coord;
    }

    /**
     * 生成UUID
     * Author: CtrL
     * @return string
     */
    public function createUUID()
    {
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = substr($str, 0, 8) . '-';
        $uuid .= substr($str, 8, 4) . '-';
        $uuid .= substr($str, 12, 4) . '-';
        $uuid .= substr($str, 16, 4) . '-';
        $uuid .= substr($str, 20, 12);
        return strtoupper($uuid);
    }

    /**
     * 通过3个地图商Api解析地址经纬度
     * Author: CtrL
     *
     * @param $address
     * @param string $city
     * @param bool $debug
     *
     * @return array|mixed
     * @throws \Throwable
     */
    public function getLocations($address, $city = '', $debug = false)
    {
        $this->isDebug  = $debug;

        $client = new Client();

        // 去掉特殊字符.
        $address    = str_replace(
            [
                "，", "。", " ", '快递柜'
            ],
            '',
            $address
        );

        // 处理括号
        $address    = str_replace("（", "(", $address);
        $address    = str_replace("）", ")", $address);

        // 判断括号里是否有分店类关键词
        preg_match("/\(.*?[店,区,厂].*?\)?/i", $address, $m);
        if (empty($m)) {
            // 视为括号里信息没用.
            $address    = str_replace(array_first($m), '', $address);
        }

        // 歧义地址转换.
        $address    = str_replace("朝悦百汇", "朝悦百惠", $address);

        // 地图商都无法准确解析的情况.
        if ($city) {
            $mapArr     = config("address_trans.".$city, []);
            foreach ($mapArr as $str => $coord) {
                if (strpos($address, $str) !== false && $coord) {
                    $res    = explode(',', $coord);
                    return [
                        'lat'   => array_last($res),
                        'lng'   => array_first($res)
                    ];
                }
            }
        }

        // 有商场、大厦，直接定位.
        preg_match('/^(.*?)(商场|大厦|号楼|层)/i', $address, $res);
        if ($res) {
            $address    = array_first($res);
        }

        if (!$address) {
            return [];
        }

        $city_names = [
            '010'   => "北京",
            '021'   => '上海',
            '0755'  => '深圳'
        ];
        $city_name  = array_get($city_names, $city);

        if (strpos($address, $city_name) === false) {
            $address    = $city_name . $address;
        }

        $coords     = [];
        // 取web版的高德解析.
        // $coords['amap_web'] = $this->getWebAmapCoord($address);

        $address    = urlencode($address);
        $amapUrl    = "https://restapi.amap.com/v3/geocode/geo?key=".$this->gKey."&address=" . $address
            . ($city ? "&city={$city}" : "");

        $baiduUrl   = "http://api.map.baidu.com/geocoder/v2/?output=json&ak="
            . $this->bKey."&ret_coordtype=gcj02ll&address=" . $address;

        $api    = [
            'amap'  => $client->getAsync($amapUrl),
            'baidu' => $client->getAsync($baiduUrl),
        ];
        $result = \GuzzleHttp\Promise\unwrap($api);

        if ($this->isDebug) {
            dump($amapUrl, $baiduUrl);
        }

        $baidu_comprehension    = 0;
        foreach ($result as $k => $value) {
            /**
             * @var Response $value
             */
            $data   = json_decode($value->getBody()->getContents(), true);
            switch ($k) {
                case 'amap':
                    $coords[$k] = [];
                    // 高德地图
                    $coord      = array_get(array_first(array_get($data, "geocodes", [])), 'location', '');
                    if ($coord && !in_array(
                        $coord,
                        [
                                '116.601144,39.948574', // 朝阳区坐标，排除.
                                '116.287149,39.858427', // 丰台区坐标,
                                '116.329519,39.972134',
                            ]
                        )) {
                        $coord      = explode(',', $coord);
                        $coords[$k] = [
                            'lng'   => array_first($coord),
                            'lat'   => array_last($coord),
                        ];
                    }
                    break;

                case 'baidu':
                    // 百度Api精度
                    $baidu_comprehension = array_get($data, 'result.comprehension');
                    $coords[$k] = array_get($data, 'result.location', []);
                    break;
            }
        }
        if ($this->isDebug) {
            dump($coords);
        }

        // 有结果.
        $result = $this->getShort($coords, $address);
        if ($result) {
            return $result;
        }

        // 没有结果参考百度的精度.
        if (!$result && $baidu_comprehension >= 100) {
            return $coords['baidu'];
        }

        // 实在没有 返回高德地图坐标
        return $coords['amap'];
    }

    /**
     * 测算最近的距离
     * Author: CtrL
     */
    public function getShort($data = [], $address = '')
    {
        $vincenty   = new \Location\Distance\Vincenty();

        // 没有高德定位.
        if (!$data['amap']) {
            return $data['baidu'];
        } else {
            $coordinate_amap    = new Coordinate(array_get($data, "amap.lat"), array_get($data, "amap.lng"));
        }

        // 没有百度定位.
        if (!$data['baidu']) {
            return $data['amap'];
        } else {
            $coordinate_baidu   = new Coordinate(array_get($data, "baidu.lat"), array_get($data, "baidu.lng"));
        }

        // 没有百度坐标或者有离高德2km内的坐标，视为准确定位.
        if ($coordinate_amap->getDistance($coordinate_baidu, $vincenty) < $this->errorRange) {
            return $this->formatCoord($coordinate_amap);
        }

        // 都没有靠近，加入腾讯地图定位来计算
        $qmapUrl    = "https://apis.map.qq.com/ws/geocoder/v1/?key=".$this->qKey."&address=" . $address;
        if ($this->isDebug) {
            dump($qmapUrl);
        }
        try {
            $data['qmap']   = array_get(
                json_decode((new Client())->get($qmapUrl)->getBody()->getContents(), true),
                'result.location',
                []
            );
        } catch (\Throwable $throwable) {
            $data['qmap']   = [];
        }
        // 没有腾讯坐标.
        if (!array_get($data, "qmap")) {
            return [];
        }
        $coordinate_qmap    = new Coordinate(array_get($data, "qmap.lat"), array_get($data, "qmap.lng"));

        // 计算腾讯和高德距离.
        if ($coordinate_qmap->getDistance($coordinate_amap, $vincenty) < $this->errorRange) {
            return $this->formatCoord($coordinate_amap);
        }

        if ($coordinate_qmap->getDistance($coordinate_baidu, $vincenty) < $this->errorRange) {
            return $this->formatCoord($coordinate_baidu);
        }

        return [];
    }

    protected function formatCoord(Coordinate $coordinate)
    {
        $format             = new \Location\Formatter\Coordinate\DecimalDegrees();

        $location   =  explode(' ', $coordinate->format($format));
        return [
            'lng'   => array_last($location),
            'lat'   => array_first($location)
        ];
    }
     /**
     * @param $origin 起始经纬度
     * @param $destination 终点经纬度
     * @param string $extensions 返回参数
     * @param int $strategy 驾车选择策略
     * @param null $province  用汉字填入车牌省份缩写，用于判断是否限行
     * @param null $number 填入除省份及标点之外，车牌的字母和数字（需大写）。用于判断限行相关。
     * @param int $cartype 车辆类型
     * @param string $output json返回数据类型
     * @return array
     * @throws HttpException
     * @throws InvalidArgumentException
     */
    public function routePlan($origin,$destination,$extensions="base",$strategy=0,$province=null,$number=null,$cartype=0,$output="json"){
        $url = 'https://restapi.amap.com/v3/direction/driving';
    
        if (!\in_array(\strtolower($output), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$output);
        }
        try {
            $query = array_filter([
                'key' => $this->gKey,
                'origin' => $origin,
                'destination' => $destination,
                'strategy' => $strategy,
                'extensions' => $extensions,
                'cartype' => $cartype,
                'number' => $number,
                'province' => $province,
                'output'=>$output
            ]);
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();
        
            $data = $output === 'json' ? \json_decode($response, true) : $response;
            
            if($data['info'] == "OK" && $data['status'] == 1){
                return $data['route']['paths'][0]['duration'];
            }else{
                throw new KeyException($this->geoKey[$data['infocode']]);
            }
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
