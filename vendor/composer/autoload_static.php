<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitba528725c868630781930f9654ed174d
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'ApproveOrders\\Modules\\' => 22,
            'ApproveOrders\\Compatibility\\' => 28,
            'ApproveOrders\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ApproveOrders\\Modules\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/modules',
        ),
        'ApproveOrders\\Compatibility\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/compatibility',
        ),
        'ApproveOrders\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitba528725c868630781930f9654ed174d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitba528725c868630781930f9654ed174d::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitba528725c868630781930f9654ed174d::$classMap;

        }, null, ClassLoader::class);
    }
}
