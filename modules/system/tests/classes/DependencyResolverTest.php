<?php

use Illuminate\Http\Request;
use October\Rain\Extension\ExtendableTrait;
use System\Classes\DependencyResolver;

class DependencyResolverTest extends TestCase
{
    public function testDependencyInjection()
    {
        $dependencyResolver = new DependencyResolver;
        $dependencyTestClass = new DependencyTestClass;

        $result = $dependencyResolver->resolve(
            $dependencyTestClass,
            'testDependencyInjection',
            []
        );

        $this->assertInstanceOf(Request::class, $result[0]);
    }

    public function testDependencyInjectionWithParameters()
    {
        $dependencyResolver = new DependencyResolver;
        $dependencyTestClass = new DependencyTestClass;

        $result = $dependencyResolver->resolve(
            $dependencyTestClass,
            'testDependencyInjection',
            ['test']
        );

        $this->assertInstanceOf(Request::class, $result[0]);
        $this->assertEquals('test', $result[1]);
    }

   public function testDependencyInjectionDynamicMethod()
   {
       $dependencyResolver = new DependencyResolver;
       $dependencyTestClass = new DependencyTestClass;
       $dependencyTestClass->addDynamicMethod('testDynamic', static function (Request $request) {});

       $result = $dependencyResolver->resolve(
           $dependencyTestClass,
           'testDynamic',
           []
       );

       $this->assertInstanceOf(Request::class, $result[0]);
   }

   public function testDependencyInjectionDynamicMethodWithParameters()
   {
       $dependencyResolver = new DependencyResolver;
       $dependencyTestClass = new DependencyTestClass;
       $dependencyTestClass->addDynamicMethod('testDynamic', static function (Request $request) {});

       $result = $dependencyResolver->resolve(
           $dependencyTestClass,
           'testDependencyInjection',
           ['testDynamic']
       );

       $this->assertInstanceOf(Request::class, $result[0]);
       $this->assertEquals('testDynamic', $result[1]);
   }
}

class DependencyTestClass
{
    use ExtendableTrait;

    public function testDependencyInjection(Request $request)
    {
    }

    public function testDependencyInjectionWithParameters(Request $request, string $key)
    {
    }
}
