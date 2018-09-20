<?php
/**
 * Created by PhpStorm.
 * User: yons
 * Date: 2018/9/17
 * Time: AM9:43
 */

namespace Zhjun\Address\Tests;


use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Zhjun\Address\Address;
use Zhjun\Address\Exceptions\HttpException;
use Zhjun\Address\Exceptions\InvalidArgumentException;
use Zhjun\Address\Exceptions\DistanceException;
use GuzzleHttp\Client;
use Mockery\Matcher\AnyArgs;


class AdressTest extends TestCase
{
    public function testGetHttpClient()
    {
        $w = new Address('mock-key');

        // 断言返回结果为 GuzzleHttp\ClientInterface 实例
        $this->assertInstanceOf(ClientInterface::class, $w->getHttpClient());
    }

    public function testSetGuzzleOptions()
    {
        $w = new Address('mock-key');

        // 设置参数前，timeout 为 null
        $this->assertNull($w->getHttpClient()->getConfig('timeout'));

        // 设置参数
        $w->setGuzzleOptions(['timeout' => 5000]);

        // 设置参数后，timeout 为 5000
        $this->assertSame(5000, $w->getHttpClient()->getConfig('timeout'));
    }

    public function testGetLocation()
    {
        // 创建模拟接口响应值。
        $response = new Response(200, [], '{"success": true}');

        // 创建模拟 http client。
        $client = \Mockery::mock(Client::class);

        // 指定将会产生的形为（在后续的测试中将会按下面的参数来调用）。
        $client->allows()->get('https://restapi.amap.com/v3/geocode/regeo', [
            'query' => [
                'key' => 'mock-key',
                'location'=>'116.481488,39.990464',
                //'poitype'=>'116.481488,39.990464',
                'radius' => 1000,
                'batch'=>'false',
                'output' => 'json',
                'extensions' => 'all',
                //'roadlevel' => '0',
            ]
        ])->andReturn($response);

        // 将 `getHttpClient` 方法替换为上面创建的 http client 为返回值的模拟方法。
        $w = \Mockery::mock(Address::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client); // $client 为上面创建的模拟实例。
        // 然后调用 `getWeather` 方法，ååå。
        $this->assertSame(['success' => true], $w->getLocation('116.481488,39.990464'));
    }

    public function testGetLocationWithGuzzleRuntimeException()
    {
        $client = \Mockery::mock(Client::class);
        $client->allows()
            ->get(new AnyArgs()) // 由于上面的用例已经验证过参数传递，所以这里就不关心参数了。
            ->andThrow(new \Exception('request timeout')); // 当调用 get 方法时会抛出异常。
        $w = \Mockery::mock(Address::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        // 接着需要断言调用时会产生异常。
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');
        $w->getLocation('116.481488,39.990464');
    }

    public function testGetSearch()
    {
        // 创建模拟接口响应值。
        $response = new Response(200, [], '{"success": true}');

        // 创建模拟 http client。
        $client = \Mockery::mock(Client::class);

        // 指定将会产生的形为（在后续的测试中将会按下面的参数来调用）。
        $client->allows()->get('https://restapi.amap.com/v3/place/text', [
            'query' => [
                'key' => 'mock-key',
                'keywords'=>'北京大学',//地址
                'types'=>'高校院校',//类型
                'city' => '北京',//市
                'children'=>1,
                'offset'=>20,//每页记录数据
                'page'=>1,//页数
                'output' => 'json',
                'extensions' => 'all',
            ]
        ])->andReturn($response);

        // 将 `getHttpClient` 方法替换为上面创建的 http client 为返回值的模拟方法。
        $w = \Mockery::mock(Address::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client); // $client 为上面创建的模拟实例。
        // 然后调用 `getWeather` 方法，ååå。
        $this->assertLessThanOrEqual(['success' => true], $w->search('北京大学','高校院校','北京'));
    }

    /**
     * @throws InvalidArgumentException
     * 测试高德地址解析
     */
    public function testGaodeSearch(){
        $key = '9422276473854ff6d8e340df63d675f4,lKFbpq2yqhceWKen5ErigNFbpEuFGR3e';
        $Address = new Address($key);
        $address = $Address->gaodeSearch('北京大学','北京');
        $path = '/OK/';
        $this->assertRegExp($path, $address['info']);

    }
    /**
     * @throws InvalidArgumentException
     * 测试百度地址解析
     */
    public function testBaiduSearch(){
        $key = 'KNb96c0YpWlwIKt5KFIewTPURL4Cnl6G,lKFbpq2yqhceWKen5ErigNFbpEuFGR3e';
        $Address = new Address('',$key);
        $address = $Address->baiduSearch('北京大学','北京');
        $path = '/ok/';
        $this->assertRegExp($path, $address['message']);
    }

    /**
     * @throws DistanceException
     * 测试距离
     */
    public function testDistance(){
        $key1 = '9422276473854ff6d8e340df63d675f4,lKFbpq2yqhceWKen5ErigNFbpEuFGR3e';
        $key2 = 'KNb96c0YpWlwIKt5KFIewTPURL4Cnl6G,lKFbpq2yqhceWKen5ErigNFbpEuFGR3e';
        $Address = new Address($key1,$key2);
        $address = $Address->getAddress('北京大学','北京');


    }
}