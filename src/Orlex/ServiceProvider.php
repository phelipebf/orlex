<?php
namespace Orlex;

use Pimple\Container;

use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;

use Orlex\AnnotationManager\Compiler;
use Orlex\AnnotationManager\Loader;

use Doctrine\Common\Annotations;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Cache;

class ServiceProvider implements Pimple\ServiceProviderInterface {
    
    public function register(Container $pimple) {
        ////
        // Absolute dependencies
        ////
        #$app->register(new ServiceControllerServiceProvider());

        ////
        // User Configured Values
        ////
        $pimple['orlex.cache.dir']       = null;
        $pimple['orlex.controller.dirs'] = [];
        $pimple['orlex.annotation.dirs'] = [];

        ////
        // Internal Services
        ////
        $pimple['orlex.cache'] = function($pimple){
            $cache_dir = $pimple['orlex.cache.dir'];

            if (!$cache_dir) return false;

            $cache = new Cache\FilesystemCache($cache_dir);

            return $cache;
        };

        $pimple['orlex.annotation.reader'] = function($pimple) {
            $reader = new Annotations\AnnotationReader();

            if ($cache = $pimple['orlex.cache']) {
                $reader = new Annotations\CachedReader($reader, $cache);
            }

            return $reader;
        };

        $pimple['orlex.directoryloader'] = function() {
            return new Loader\DirectoryLoader();
        };

        $pimple['orlex.annotation.registry'] = function($pimple) {
            AnnotationRegistry::registerAutoloadNamespace('Orlex\Annotation', dirname(__DIR__));
            foreach ($pimple['orlex.annotation.dirs'] as $dir => $namespace) {
                AnnotationRegistry::registerAutoloadNamespace($namespace, $dir);
            }
        };

        $pimple['orlex.route.compiler'] = function($pimple) {
            $pimple['orlex.annotation.registry'];

            $compiler = new Compiler\Route($pimple['orlex.annotation.reader'], $pimple['orlex.directoryloader']);
            $compiler->setContainer($pimple);
            return $compiler;
        };
    }

//    public function boot(Application $app) {
//        /** @var $compiler Compiler\Route */
//        $compiler = $app['orlex.route.compiler'];
//
//        foreach ($app['orlex.controller.dirs'] as $path) {
//            $compiler->compile($path);
//        }
//    }
}