<h1 align="center"> address </h1>

<p align="center"> .</p>


[![Build Status](https://travis-ci.org/overtrue/weather.svg?branch=master)](https://travis-ci.org/overtrue/weather)

## 安装

```shell
$ composer require zhjun/address
```
## 配置
在使用本扩展之前，你需要去 高德开放平台 注册账号，然后创建应用，获取应用的 API Key。
在services中配置
.env

'gaode' => [
        'key' => env('GAODE_API_KEY'),
    ],
 'baidu' => [
         'key' => env('baidu_API_KEY'),
    ],
config('')
TODO

## 使用

use Zhjun\Address\Address;

$key = 'xxxxxxxxxxxxxxxxxxxxxxxxxxx,xxxxxxxxxxxxxxxxxxx';//key需多个,因为每一个key有使用限制

public function show(Request $request, $city)
    {
        return app('address')->getAddress($keywords,$city);
    }



## 获取经纬度

$Address->getAddress('具体地址','市','搜索类型(1)','范围');


示例：
1.$Address->getAddress('朝阳大悦城7层西贝餐厅旁%20满满元气枣糕','北京');//百度地图搜索不到
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
2.$Address->getAddress('朝阳大悦城','北京');
{
    "gaode_location":{
        "lat":"39.924548",
        "lng":"116.519035"
    },
    "baidu_location":{
        "lat":39.930771,
        "lng":116.52481,
    },
    "distance":849.84000//百度地图和高德地图相差距离
}
3.$Address->getAddress('朝阳大悦城','北京',2);//宽松模式搜索(只搜索高德,默认严格模式)
{
    "gaode_location":{
        "lat":"39.924548",
        "lng":"116.519035"
    },
}

## License

MIT# address
