<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit80bbd6537f5346e7f6f486b29fdadc16
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit80bbd6537f5346e7f6f486b29fdadc16', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit80bbd6537f5346e7f6f486b29fdadc16', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit80bbd6537f5346e7f6f486b29fdadc16::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
