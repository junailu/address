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


    public function testGetAddress()
    {
        // 创建模拟接口响应值。
        $response = new Response(200, [], '{"success": true}');

        // 创建模拟 http client。
        $client = \Mockery::mock(Client::class);

        // 指定将会产生的形为（在后续的测试中将会按下面的参数来调用）。
        $client->allows()->get('https://restapi.amap.com/v3/geocode/geo', [
            'query' => [
                'key' => 'mock-key',
                'address'=>'方恒国际中心A座',
                'city' => '北京',
                'batch'=>'false',
                'output' => 'json',
            ]
        ])->andReturn($response);

        // 将 `getHttpClient` 方法替换为上面创建的 http client 为返回值的模拟方法。
        $w = \Mockery::mock(Address::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client); // $client 为上面创建的模拟实例。
        // 然后调用 `getWeather` 方法，ååå。
        $this->assertSame(['success' => true], $w->getAddress('方恒国际中心A座','北京'));
    }


    // 检查 $output 参数
    public function testGetAddressWithInvalidOutput()
    {
        $w = new Address('mock-key');

        // 断言会抛出此异常类
        $this->expectException(InvalidArgumentException::class);

        // 断言异常消息为 'Invalid response format: array'
        $this->expectExceptionMessage('Invalid response format: array');

        // 因为支持的格式为 xml/json，所以传入 array 会抛出异常
        $w->getAddress('方恒国际中心A座','北京','false','array');
        // 如果没有抛出异常，就会运行到这行，标记当前测试没成功
        $this->fail('Faild to assert getAddress throw exception with invalid argument.');
    }

    public function testGetAddressWithGuzzleRuntimeException()
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
        $w->getAddress('方恒国际中心A座','北京');
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
}