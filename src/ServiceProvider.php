<?php
namespace Zhjun\Address;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(Address::class, function(){
            return new Address(config('services.map.GaoKey'),config('services.map.BaiKey'));
        });

        $this->app->alias(Address::class, 'address_parse');
    }

    public function provides()
    {
        return [Address::class, 'address_parse'];
    }
}
