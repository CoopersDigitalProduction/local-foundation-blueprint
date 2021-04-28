<?php

namespace DeliciousBrains\WPMDB\Container\Composer;

use DeliciousBrains\WPMDB\Container\Composer\Autoload\ClassLoader;
use DeliciousBrains\WPMDB\Container\Composer\Semver\VersionParser;
class InstalledVersions
{
    private static $installed = array('root' => array('pretty_version' => 'dev-2.0-lewis-styling-fixes', 'version' => 'dev-2.0-lewis-styling-fixes', 'aliases' => array(), 'reference' => '99ed7f872b4aed84f70066e175472f6fa3cc2b0c', 'name' => 'deliciousbrains/composer-tmp'), 'versions' => array('container-interop/container-interop' => array('pretty_version' => '1.2.0', 'version' => '1.2.0.0', 'aliases' => array(), 'reference' => '79cbf1341c22ec75643d841642dd5d6acd83bdb8'), 'container-interop/container-interop-implementation' => array('provided' => array(0 => '^1.0')), 'deliciousbrains/composer-tmp' => array('pretty_version' => 'dev-2.0-lewis-styling-fixes', 'version' => 'dev-2.0-lewis-styling-fixes', 'aliases' => array(), 'reference' => '99ed7f872b4aed84f70066e175472f6fa3cc2b0c'), 'doctrine/cache' => array('pretty_version' => 'v1.4.0', 'version' => '1.4.0.0', 'aliases' => array(), 'reference' => '2346085d2b027b233ae1d5de59b07440b9f288c8'), 'mnapoli/php-di' => array('replaced' => array(0 => '*')), 'php-di/invoker' => array('pretty_version' => '1.3.3', 'version' => '1.3.3.0', 'aliases' => array(), 'reference' => '1f4ca63b9abc66109e53b255e465d0ddb5c2e3f7'), 'php-di/php-di' => array('pretty_version' => '5.4.0', 'version' => '5.4.0.0', 'aliases' => array(), 'reference' => 'e348393488fa909e4bc0707ba5c9c44cd602a1cb'), 'php-di/phpdoc-reader' => array('pretty_version' => '2.1.1', 'version' => '2.1.1.0', 'aliases' => array(), 'reference' => '15678f7451c020226807f520efb867ad26fbbfcf'), 'phpoption/phpoption' => array('pretty_version' => '1.7.5', 'version' => '1.7.5.0', 'aliases' => array(), 'reference' => '994ecccd8f3283ecf5ac33254543eb0ac946d525'), 'psr/container' => array('pretty_version' => '1.0.0', 'version' => '1.0.0.0', 'aliases' => array(), 'reference' => 'b7ce3b176482dbbc1245ebf52b181af44c2cf55f'), 'symfony/polyfill-ctype' => array('pretty_version' => 'v1.19.0', 'version' => '1.19.0.0', 'aliases' => array(), 'reference' => 'aed596913b70fae57be53d86faa2e9ef85a2297b'), 'vlucas/phpdotenv' => array('pretty_version' => 'v4.2.0', 'version' => '4.2.0.0', 'aliases' => array(), 'reference' => 'da64796370fc4eb03cc277088f6fede9fde88482')));
    private static $canGetVendors;
    private static $installedByVendor = array();
    public static function getInstalledPackages()
    {
        $packages = array();
        foreach (self::getInstalled() as $installed) {
            $packages[] = \array_keys($installed['versions']);
        }
        if (1 === \count($packages)) {
            return $packages[0];
        }
        return \array_keys(\array_flip(\call_user_func_array('array_merge', $packages)));
    }
    public static function isInstalled($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (isset($installed['versions'][$packageName])) {
                return \true;
            }
        }
        return \false;
    }
    public static function satisfies(\DeliciousBrains\WPMDB\Container\Composer\Semver\VersionParser $parser, $packageName, $constraint)
    {
        $constraint = $parser->parseConstraints($constraint);
        $provided = $parser->parseConstraints(self::getVersionRanges($packageName));
        return $provided->matches($constraint);
    }
    public static function getVersionRanges($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }
            $ranges = array();
            if (isset($installed['versions'][$packageName]['pretty_version'])) {
                $ranges[] = $installed['versions'][$packageName]['pretty_version'];
            }
            if (\array_key_exists('aliases', $installed['versions'][$packageName])) {
                $ranges = \array_merge($ranges, $installed['versions'][$packageName]['aliases']);
            }
            if (\array_key_exists('replaced', $installed['versions'][$packageName])) {
                $ranges = \array_merge($ranges, $installed['versions'][$packageName]['replaced']);
            }
            if (\array_key_exists('provided', $installed['versions'][$packageName])) {
                $ranges = \array_merge($ranges, $installed['versions'][$packageName]['provided']);
            }
            return \implode(' || ', $ranges);
        }
        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }
    public static function getVersion($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }
            if (!isset($installed['versions'][$packageName]['version'])) {
                return null;
            }
            return $installed['versions'][$packageName]['version'];
        }
        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }
    public static function getPrettyVersion($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }
            if (!isset($installed['versions'][$packageName]['pretty_version'])) {
                return null;
            }
            return $installed['versions'][$packageName]['pretty_version'];
        }
        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }
    public static function getReference($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }
            if (!isset($installed['versions'][$packageName]['reference'])) {
                return null;
            }
            return $installed['versions'][$packageName]['reference'];
        }
        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }
    public static function getRootPackage()
    {
        $installed = self::getInstalled();
        return $installed[0]['root'];
    }
    public static function getRawData()
    {
        return self::$installed;
    }
    public static function reload($data)
    {
        self::$installed = $data;
        self::$installedByVendor = array();
    }
    private static function getInstalled()
    {
        if (null === self::$canGetVendors) {
            self::$canGetVendors = \method_exists('DeliciousBrains\\WPMDB\\Container\\Composer\\Autoload\\ClassLoader', 'getRegisteredLoaders');
        }
        $installed = array();
        if (self::$canGetVendors) {
            foreach (\DeliciousBrains\WPMDB\Container\Composer\Autoload\ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
                if (isset(self::$installedByVendor[$vendorDir])) {
                    $installed[] = self::$installedByVendor[$vendorDir];
                } elseif (\is_file($vendorDir . '/composer/installed.php')) {
                    $installed[] = self::$installedByVendor[$vendorDir] = (require $vendorDir . '/composer/installed.php');
                }
            }
        }
        $installed[] = self::$installed;
        return $installed;
    }
}
