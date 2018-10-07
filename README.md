<h1 align="center"> address </h1>

<p align="center"> .</p>


[![Build Status](https://travis-ci.org/overtrue/weather.svg?branch=master)](https://travis-ci.org/overtrue/weather)

## 安装

```shell
$ composer require zhjun/address
```
## 配置
在使用本扩展之前，你需要去 高德开放平台 注册账号，然后创建应用，获取应用的 API Key。

    .env
  
     GAODE_MAP_API_KEY= xxxxx,xxxx,xxxx   (可多组)

     Badiu_API_KEY= xxxx,xxxx,xxxx       (可多组)
     
     
    config/service的最后加入
     
     'map'=>[
             'GaoKey' => env('GAO_API_KEY'),
             'BaiKey' => env('GAO_API_KEY'),
         ]
  

## 使用

    use Zhjun\Address\Address;
    (new Address())->getAddress($keywords,$city);
    
    Or

    app('address')->getAddress($keywords,$city);
 



## 获取经纬度
    
    $Address->getAddress('string 具体地址','string 市','int 搜索类型=2','int 范围=500');
    
## 根据地址模糊搜索

    $Address->search('string 具体地址','string 市','int 搜索类型=1');//默认为高德

## 根据经纬度获取地址

    $Address->getLocation('string 经纬度');//默认为高德

示例：

    1.$Address->getAddress('朝阳大悦城7层西贝餐厅旁%20满满元气枣糕','北京');//百度地图搜索不到
    
    Return:
    {
       "gaode_location":{//高德地图
           "lat":"39.924332",//经度
           "lng":"116.518613"//维度
       },
       "baidu_location":{//百度地图
            "lat":false,//经度
            "lng":false//维度
       }
    }
    
    2.$Address->getAddress('朝阳大悦城','北京'，1);//严格模式
    
    Return:
    {
        "gaode_location":{
            "lat":"39.924548",
            "lng":"116.519035"
        },
        "baidu_location":{
            "lat":39.930771,
            "lng":116.52481,
        },
        "distance":849.84000//百度地图和高德地图相差距离 单元:m
    }
    
    3.$Address->getAddress('朝阳大悦城','北京');//宽松模式搜索(只搜索高德,默认宽松模式)
    
    Return
    {
        "gaode_location":{
            "lat":"39.924548",
            "lng":"116.519035"
        },
    }
    
    
    4.$Address->search('北京大学','北京);//根据地址模糊搜索
     
     Ruturn
       array:3 [▼
         "status" => "1"
         "message" => "OK"
         "results" => array:20 [▼
           0 => array:10 [▼
             "id" => "B000A856LJ"
             "name" => "朝阳大悦城"
             "type" => "购物服务;商场;购物中心"
             "address" => "朝阳北路101号"
             "location" => "116.519035,39.924548"
             "pcode" => "110000"
             "province" => "北京市"
             "citycode" => "010"
             "city" => "北京市"
             "area" => "朝阳区"
           ]
           1 => array:10 [▶]
           2 => array:10 [▶]
           3 => array:10 [▶]
         ]
       ]
        
     5.$Address->getLocation('116.481488,39.990464');
     
       Return
       array:4 [▼
         "status" => "1"
         "regeocode" => array:6 [▼
           "roads" => array:3 [▶]
           "roadinters" => array:1 [▶]
           "formatted_address" => "北京市朝阳区望京街道方恒国际中心B座方恒国际中心"
           "addressComponent" => array:12 [▼
             "city" => []
             "province" => "北京市"
             "adcode" => "110105"
             "district" => "朝阳区"
             "towncode" => "110105026000"
             "streetNumber" => array:5 [▶]
             "country" => "中国"
             "township" => "望京街道"
             "businessAreas" => array:3 [▶]
             "building" => array:2 [▶]
             "neighborhood" => array:2 [▶]
             "citycode" => "010"
           ]
           "aois" => array:1 [▶]
           "pois" => array:30 [▶]
         ]
         "info" => "OK"
         "infocode" => "10000"
       ]
    
    

## License

MIT# address
