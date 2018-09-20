<?php
namespace Zhjun\Address;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(Address::class, function(){
            return new Address(config('services.address.gkey'),config('services.address.bkey'));
        });

        $this->app->alias(Address::class, 'address');
    }

    public function provides()
    {
        return [Address::class, 'address'];
    }
}