<h1 align="center"> address </h1>

<p align="center"> .</p>


[![Build Status](https://travis-ci.org/overtrue/weather.svg?branch=master)](https://travis-ci.org/overtrue/weather)

## 安装

```shell
$ composer require zhjun/address
```

## Usage

TODO

## 配置

引入扩展包后,调用Address控制器，调用$address->getAddress('具体地址','市','搜索类型(1)','范围');

搜索类型1:为百度和高德同时搜索,返回百度和高德的坐标和地图坐标的差距

搜索类型2:为模糊搜索只搜索高德地图,并返回高德地图上的经纬度

范围:如果搜索的时候高德和百度的经纬差距大于范围就返回异常,反之返回经纬度和差距

## License

MIT# address
