<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4c195c68c80760781f50113b00e19581
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'Razorpay\\Tests\\' => 15,
            'Razorpay\\Api\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Razorpay\\Tests\\' => 
        array (
            0 => __DIR__ . '/..' . '/razorpay/razorpay/tests',
        ),
        'Razorpay\\Api\\' => 
        array (
            0 => __DIR__ . '/..' . '/razorpay/razorpay/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'R' => 
        array (
            'Requests' => 
            array (
                0 => __DIR__ . '/..' . '/rmccue/requests/library',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit4c195c68c80760781f50113b00e19581::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit4c195c68c80760781f50113b00e19581::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit4c195c68c80760781f50113b00e19581::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit4c195c68c80760781f50113b00e19581::$classMap;

        }, null, ClassLoader::class);
    }
}
