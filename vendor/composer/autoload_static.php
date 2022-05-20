<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitdb8e5ce15e141a2c38ea8abe7292744b
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'Medaqueno\\Boilerwork\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Medaqueno\\Boilerwork\\' => 
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
            $loader->prefixLengthsPsr4 = ComposerStaticInitdb8e5ce15e141a2c38ea8abe7292744b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitdb8e5ce15e141a2c38ea8abe7292744b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitdb8e5ce15e141a2c38ea8abe7292744b::$classMap;

        }, null, ClassLoader::class);
    }
}
