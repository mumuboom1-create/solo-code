<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @author XingMeng
 * @email hnxsh@foxmail.com
 *  Kernel: bootstrap and route dispatch
 */

namespace core\basic;

class Kernel
{
    public static function run()
    {
        self::checkLicense();

        if (method_exists('core\basic\Check', 'checkUrl')) {
            \core\basic\Check::checkUrl();
        }

        $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        if (defined('URL_BIND')) {
            $url = preg_replace('{/?' . URL_BIND . '\.php}i', '', $url);
        }

        if (defined('SITE_INDEX_DIR') && SITE_INDEX_DIR) {
            $url = preg_replace('{^' . preg_quote(SITE_INDEX_DIR, '{') . '}i', '', $url);
        }

        if (strpos($url, '?') !== false) {
            list($path, $query) = explode('?', $url, 2);
            parse_str($query, $queryArr);
            foreach ($queryArr as $k => $v) {
                $_GET[$k] = $v;
            }
        } else {
            $path = $url;
        }

        $path = trim($path, '/');

        if ($path === '' && !empty($_GET)) {
            foreach ($_GET as $k => $v) {
                if ($v === '' && strpos($k, '/') !== false) {
                    $path = trim($k, '/');
                    unset($_GET[$k]);
                    break;
                }
            }
        }

        if (!defined('URL')) {
            $scheme = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $reqUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            define('URL', $scheme . '://' . $host . $reqUri);
        }
        if (!defined('P')) {
            define('P', $path);
        }
        if (!defined('G')) {
            define('G', isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
        }

        self::dispatch($path);
    }

    private static function dispatch($path)
    {
        $path = self::applyRoutes($path);

        $path = self::applyDomainBind($path);

        $publicApps = Config::get('public_app');
        if (is_string($publicApps)) {
            $publicApps = array_map('trim', explode(',', $publicApps));
        } elseif (!is_array($publicApps) || empty($publicApps)) {
            $publicApps = array('home', 'admin', 'api');
        }

        $defaultModule = defined('URL_BIND') ? URL_BIND : (isset($publicApps[0]) ? $publicApps[0] : 'home');

        $suffix = Config::get('url_rule_suffix');
        if ($suffix && strlen($path) > strlen($suffix) && substr($path, -strlen($suffix)) === $suffix) {
            $path = substr($path, 0, -strlen($suffix));
        }

        $segments = $path === '' ? array() : explode('/', $path);

        $module = $defaultModule;
        if (!empty($segments)) {
            $first = strtolower($segments[0]);
            $apps_lower = array_map('strtolower', $publicApps);
            if (in_array($first, $apps_lower, true)) {
                $module = $first;
                array_shift($segments);
            }
        }

        $controllerName = !empty($segments) ? array_shift($segments) : 'index';
        $actionName = !empty($segments) ? array_shift($segments) : 'index';

        if ($suffix && strlen($actionName) > strlen($suffix) && substr($actionName, -strlen($suffix)) === $suffix) {
            $actionName = substr($actionName, 0, -strlen($suffix));
        }

        for ($i = 0, $n = count($segments); $i + 1 < $n; $i += 2) {
            $_GET[$segments[$i]] = $segments[$i + 1];
        }

        $controllerName = preg_replace('/[^a-zA-Z0-9_]/', '', $controllerName);
        $actionName = preg_replace('/[^a-zA-Z0-9_]/', '', $actionName);
        if ($controllerName === '') { $controllerName = 'index'; }
        if ($actionName === '') { $actionName = 'index'; }

        if (!defined('M')) { define('M', $module); }
        if (!defined('C')) { define('C', ucfirst($controllerName)); }
        if (!defined('A')) { define('A', $actionName); }
        if (!defined('APP_CONTROLLER_PATH')) { define('APP_CONTROLLER_PATH', APP_PATH . '/' . M . '/controller'); }
        if (!defined('APP_VIEW_PATH')) {
            $tplDir = Config::get('tpl_dir.' . M);
            if (!is_string($tplDir) || $tplDir === '') {
                $tplDir = '/template';
            }
            define('APP_VIEW_PATH', ROOT_PATH . $tplDir);
        }

        $commonFunc = APP_PATH . '/common/function.php';
        if (file_exists($commonFunc)) {
            require_once $commonFunc;
        }
        $moduleFunc = APP_PATH . '/' . M . '/function.php';
        if (file_exists($moduleFunc)) {
            require_once $moduleFunc;
        }

        $className = '\\app\\' . M . '\\controller\\' . ucfirst($controllerName) . 'Controller';
        $classFile = APP_CONTROLLER_PATH . '/' . ucfirst($controllerName) . 'Controller.php';

        if (!file_exists($classFile)) {
            error('Controller ' . ucfirst($controllerName) . ' not found: ' . $classFile);
        }

        $instance = new $className();

        if ($module === 'home' && strtolower($controllerName) === 'index' && $actionName === 'index' && $path === '') {
            if (method_exists($instance, 'getIndexPage')) {
                $instance->getIndexPage();
                return;
            }
        }

        if (method_exists($instance, $actionName)) {
            $callable = array($instance, $actionName);
        } elseif (method_exists($instance, '_empty')) {
            $callable = array($instance, '_empty');
        } else {
            error('Method ' . $actionName . ' not found in controller ' . ucfirst($controllerName));
        }

        $result = call_user_func($callable, $actionName);
        if ($result !== null) {
            echo $result;
        }
    }

    private static function applyRoutes($path)
    {
        $routes = Config::get('url_route');
        if (!is_array($routes) || empty($routes)) {
            return $path;
        }
        foreach ($routes as $key => $value) {
            $value = trim($value, '/');
            $key = trim($key, '/');
            $source = array(0 => array());
            if (preg_match_all('/\(.*?\)/', $key, $source)) {
                foreach ($source[0] as $kk => $vk) {
                    $key = str_replace($vk, '$' . ($kk + 1), $key);
                }
            }
            if (preg_match('{^' . $value . '$}i', $path)) {
                $path = preg_replace('{^' . $value . '$}i', $key, $path);
                break;
            } elseif (preg_match('{^' . $value . '/}i', $path)) {
                $path = preg_replace('{^' . $value . '/}i', $key . '/', $path);
                break;
            }
        }
        return $path;
    }

    private static function applyDomainBind($path)
    {
        $domains = Config::get('app_domain_bind');
        if (!is_array($domains) || empty($domains)) {
            return $path;
        }
        $host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
        $host = preg_replace('/:\d+$/', '', $host);
        if (isset($domains[$host])) {
            $bind = trim($domains[$host], '/');
            if ($bind && strpos($path, $bind) !== 0) {
                $path = $bind . ($path ? '/' . $path : '');
            }
        }
        return $path;
    }

    private static function checkLicense()
    {
        $enableCheck = Config::get('license_check');
        if ($enableCheck === null) {
            $enableCheck = true;
        }
        if (!$enableCheck) {
            return;
        }

        $publicKey = "-----BEGIN PUBLIC KEY-----\n"
            . "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAk4FsK4RVh/GDpSuWz8rV\n"
            . "WtyOMFUydZmcFx+DJPzmqJ5td8g5/KuzbntPHVdQP+Ivbb1DWh/ZFMHAMqIJ9ytK\n"
            . "y6fFCV+HFVKZZvsNnqZ0xpXp/eg9wSrxTc3o4ZGPrBl3wYpXiPFKomL8p56e6I3/\n"
            . "VHMk5iAamVf8rCQtLuPJalU/SRM0ppyL86dbwF+adsRpNZpTM7A0XIfJCzcSzVuZ\n"
            . "sWN+v/VxbiY5dMQDPr1jKnbpzbbHsr+IpTYo8AukA8ibhF3/lAlvttl+7AptYTZQ\n"
            . "3uChyyNDEiqowPyDr0G4seWumct0KlWYMXLfpEM9AIF2AS69LLPguuAiNzqqd6bj\n"
            . "lQIDAQAB\n"
            . "-----END PUBLIC KEY-----";

        $host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
        $host = preg_replace('/:\d+$/', '', $host);
        $host = preg_replace('/^www\./', '', $host);

        $configSn = Config::get('sn');

        if (self::isLocalEnv($host)) {
            return;
        }

        if (!self::verifyLicense($host, $configSn, $publicKey)) {
            self::showUnauthorizedPage($host);
        }
    }

    private static function verifyLicense($domain, $sn, $publicKey)
    {
        if (empty($sn) || empty($domain)) {
            return false;
        }

        $snList = array_map('trim', explode(',', $sn));

        foreach ($snList as $code) {
            $parts = explode(':', $code, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $licensedDomain = $parts[0];
            $signatureBase64Url = $parts[1];

            if ($licensedDomain !== $domain) {
                continue;
            }

            $signatureBase64 = strtr($signatureBase64Url, '-_', '+/');
            $pad = strlen($signatureBase64) % 4;
            if ($pad) {
                $signatureBase64 .= str_repeat('=', 4 - $pad);
            }
            $signature = base64_decode($signatureBase64);
            if ($signature === false || strlen($signature) < 10) {
                continue;
            }

            $result = openssl_verify($domain, $signature, $publicKey, OPENSSL_ALGO_SHA256);
            if ($result === 1) {
                return true;
            }
        }
        return false;
    }

    private static function isLocalEnv($host)
    {
        $localPatterns = array('localhost', '127.0.0.1', '::1');
        if (in_array($host, $localPatterns)) {
            return true;
        }
        if (preg_match('/^(10\.|172\.(1[6-9]|2\d|3[01])\.|192\.168\.)/', $host)) {
            return true;
        }
        return false;
    }

    private static function showUnauthorizedPage($host)
    {
        $authPagePath = ROOT_PATH . '/core/auth_notice.html';
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=utf-8');
        if (file_exists($authPagePath)) {
            $html = file_get_contents($authPagePath);
            $html = str_replace('{domain}', htmlspecialchars($host), $html);
            echo $html;
        } else {
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Unauthorized</title></head>';
            echo '<body style="text-align:center;padding:80px;font-family:sans-serif;">';
            echo '<h2>License verification failed</h2>';
            echo '<p>The current domain <strong>' . htmlspecialchars($host) . '</strong> is not authorized. Please contact the developer for a valid license code.</p>';
            echo '</body></html>';
        }
        exit();
    }
}
