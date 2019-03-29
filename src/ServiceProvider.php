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

        $this->mergeConfigFrom(
            __DIR__.'/config/address_trans.php', 'address_trans'
        );
    }

    public function provides()
    {
        return [Address::class, 'address_parse'];
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/address_trans.php' => config_path('address_trans.php'),
        ]);
    }
}
