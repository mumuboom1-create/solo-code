<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @author XingMeng
 * @email hnxsh@foxmail.com
 *  System environment check class
 */
namespace core\basic;

use core\basic\Config;

class Check
{

    // Application boot check
    public static function checkApp()
    {
        if (! is_dir(APP_PATH)) {
            error('System files cannot be read normally, please check if upload is complete!');
        }

        // magic_quotes_gpc check (PHP < 7.0)
        if (PHP_VERSION < '7.0' && function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) {
            error('Server PHP.ini magic_quotes_gpc is On, which causes data storage exceptions. Please set it to Off or upgrade PHP.');
        }

        // scandir function check
        if (! function_exists('scandir')) {
            error('Server PHP.ini disabled scandir function, configuration and template files cannot be read. Please remove the restriction.');
        }

        // gd extension
        if (! extension_loaded('gd')) {
            error('Server does not support gd extension, captcha will not work!');
        }

        // mbstring extension
        if (! extension_loaded('mbstring')) {
            error('Server does not support mbstring extension, please install and enable it!');
        }

        // curl extension
        if (! extension_loaded('curl')) {
            error('Server does not support curl extension, please install and enable it!');
        }
    }

    // PHP version check
    public static function checkPHP()
    {
        if (version_compare(phpversion(),'7.0.0','<')) {
            error('Your PHP version is too low. This program requires PHP >= 7.0');
        }
    }

    // mysqli extension check
    public static function checkMysqli()
    {
        if (! extension_loaded('mysqli')) {
            error('Server does not support mysqli extension, database is unavailable!');
        }
    }

    // curl extension check
    public static function checkCurl()
    {
        if (! extension_loaded('curl')) {
            error('Server does not support curl extension, API mode is unavailable!');
        }
    }

    // Basic directory check, auto-create when debug is on
    public static function checkBasicDir()
    {
        if (Config::get('debug')) {
            check_dir(APP_PATH, true);
            check_dir(APP_PATH . '/common', true);
            check_dir(CONF_PATH, true);
        }

        if (! check_dir(RUN_PATH, true)) {
            error('Cache directory creation failed, write permission may be insufficient! ' . RUN_PATH);
        }
        if (! check_dir(DOC_PATH . STATIC_DIR . '/upload', true)) {
            error('Upload directory creation failed, write permission may be insufficient! ' . DOC_PATH . STATIC_DIR . '/upload');
        }
    }

    // Default app file check, auto-create if missing
    public static function checkAppFile()
    {
        $apps = Config::get('public_app', true);
        check_dir(APP_CONTROLLER_PATH, true);
        check_file(CONF_PATH . '/config.php', true, "<?php \r\n return array(\r\n\t //'key'=>'value', separated by commas\r\n);");
        check_file(APP_CONTROLLER_PATH . '/IndexController.php', true, "<?php \r\r namespace app\\" . M . "\\controller;\r\r use core\\basic\\Controller; \r\r class IndexController extends Controller{\r\r\tpublic function index(){\r\t\t\$this->display('index.html');\r\t} \r\r}");
        check_file(APP_PATH . '/common/' . ucfirst(M) . 'Controller.php', true, "<?php \r\rnamespace app\\common;\r\ruse core\\basic\\Controller; \r\rclass " . ucfirst(M) . "Controller extends Controller{ \r\r}");
    }

    // Browser allow/deny check (deny list has priority; allow list, if set, is exclusive)
    public static function checkBs()
    {
        $allow_bs = Config::get('access_rule.allow_bs', true);
        $deny_bs = Config::get('access_rule.deny_bs', true);
        if (! $allow_bs && ! $deny_bs)
            return true;
        $user_bs = get_user_bs();
        if (in_array($user_bs, $deny_bs)) {
            error('This site does not allow ' . $user_bs . ' browser access. Please use another browser (IE, Firefox, Chrome, etc.). Domestic browsers should use high-speed mode!');
        } elseif ($allow_bs && ! in_array($user_bs, $allow_bs)) {
            error('This site only allows ' . implode(',', $allow_bs) . ' browser access. Please use one of these!');
        }
    }

    // OS allow/deny check (same priority rule as checkBs)
    public static function checkOs()
    {
        $allow_os = Config::get('access_rule.allow_os', true);
        $deny_os = Config::get('access_rule.deny_os', true);
        if (! $allow_os && ! $deny_os)
            return true;
        $user_os = get_user_os();
        if (in_array($user_os, $deny_os)) {
            error('This site does not allow ' . $user_os . ' access. Please use another operating system!');
        } elseif ($allow_os && ! in_array($user_os, $allow_os)) {
            error('This site only allows ' . implode(',', $allow_os) . ' access. Please use one of these!');
        }
    }

    public static function checkSession(){
        check_dir(RUN_PATH . '/archive', true);
        $data = json_decode(trim(substr(file_get_contents(RUN_PATH . '/archive/session_ticket.php'), 15)));
        if($data){
            if($data->expire_time && $data->expire_time < time()){
                ignore_user_abort(true);
                set_time_limit(7200);
                ob_start();
                ob_end_flush();
                flush();
                $rs = path_delete(RUN_PATH . '/session');
                if($rs){
                    $data->expire_time = time() + 60 * 30 * 1; // delay next cleanup by 30 min
                    create_file(RUN_PATH . '/archive/session_ticket.php', "<?php exit();?>".json_encode($data), true);
                }
            }
        }else{
            $start_time = time() + 60 * 60 * 1; // initial cleanup time
            $start_str = '{"expire_time":' . $start_time . '}';
            create_file(RUN_PATH . '/archive/session_ticket.php', "<?php exit();?>" . $start_str, true);
        }
    }

    // checkUrl: compatible with patched Kernel.php that calls Check::checkUrl()
    public static function checkUrl()
    {
        $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if ($url === '') {
            return true;
        }

        // decode before check to prevent encoded bypass
        $decoded = rawurldecode($url);

        // block common injection / xss keywords
        $blackList = array(
            '<', '>', '"', "'",
            'script', 'alert(', 'onerror=', 'onload=', 'javascript:',
            'union ', 'select ', 'insert ', 'update ', 'delete ',
            'create ', 'drop ', 'truncate ', 'declare ',
            'load_file', 'outfile', 'sleep(', 'benchmark(',
            '0x', '/*', '*/', '--', '#'
        );

        $check = strtolower($decoded);
        foreach ($blackList as $kw) {
            if (strpos($check, $kw) !== false) {
                error('Request blocked: contains disallowed characters.');
            }
        }

        // limit URL length
        if (strlen($url) > 2048) {
            error('Request blocked: URL too long.');
        }

        return true;
    }
}