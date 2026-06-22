<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2016年11月6日
 *  模板显示类
 */
namespace core\view;

use core\basic\Config;

class View
{

    // 模板路径
    protected $tplPath;

    // 编译路径
    protected $tplcPath;

    // 缓存路径
    protected $cachePath;

    // 存储注入变量
    protected $vars = array();

    // 存储包含文件
    protected $incFile = array();

    // 实例
    protected static $view;

    // 获取单一实例
    public static function getInstance()
    {
        if (! self::$view) {
            self::$view = new self();
        }
        return self::$view;
    }

    // 禁止通过new实例化类
    private function __construct()
    {
        $this->tplPath = APP_VIEW_PATH;
        $this->tplcPath = RUN_PATH . '/complile';
        $this->cachePath = RUN_PATH . '/cache';
        check_dir($this->tplcPath, true);
        check_dir($this->cachePath, true);
    }

    private function __clone()
    {
        die('不允许克隆对象！请使用getInstance获取实例');
    }

    // 变量注入
    public function assign($var, $value)
    {
        if (! empty($var)) {
            if (isset($this->vars[$var])) {
                error('模板变量$' . $var . '出现重复注入！');
            }
            $this->vars[$var] = $value;
            return true;
        } else {
            error('传递的设置模板变量有误');
        }
    }

    // 变量获取
    public function getVar($var)
    {
        if (! empty($var)) {
            if (isset($this->vars[$var])) {
                return $this->vars[$var];
            } else {
                return null;
            }
        } else {
            error('传递的获取模板变量有误');
        }
    }

    // 解析模板文件
    public function parser($file)
    {
        $this->tplPath = APP_VIEW_PATH;
        echo 'P_ENTER|';
        // 设置主题
        echo 'P_THEME=' . $theme . '|';$theme = isset($this->vars['theme']) ? $this->vars['theme'] : 'default';echo 'P_THEME_DONE|';
        
        $theme = preg_replace_r('{\.\.(\/|\\\\)}', '', $theme); // 过滤掉相对路径
        $file = preg_replace_r('{\.\.(\/|\\\\)}', '', $file); // 过滤掉相对路径
        
        if (strpos($file, '/') === 0) { // 绝对路径模板
            $tpl_file = ROOT_PATH . $file;
            echo 'ABS_TPLF=' . $tpl_file . '|';
        } elseif (! ! $pos = strpos($file, '@')) { // 跨模块调用
            $path = APP_PATH . '/' . substr($file, 0, $pos) . '/view/' . $theme;
            define('APP_THEME_DIR', str_replace(DOC_PATH, '', $path));
            if (! is_dir($path)) { // 检查主题是否存在
                die('ERROR_THEME_NOT_FOUND: ' . $path);
            } else {
                $this->tplPath = $path;
            }
            $tpl_file = $path . '/' . substr($file, $pos + 1);
            echo 'CROSS_TPLF=' . $tpl_file . '|';
        } else {
            // 定义当前应用主题目录
            define('APP_THEME_DIR', str_replace(DOC_PATH, '', APP_VIEW_PATH) . '/' . $theme);
            if (! is_dir($this->tplPath = $this->tplPath . '/' . $theme)) { // 检查主题是否存在
                die('ERROR_THEME_DIR: ' . APP_THEME_DIR);
            }
            $tpl_file = $this->tplPath . '/' . $file; // 模板文件
        echo 'NORM_TPLF=' . $tpl_file . '|';
        echo 'FILE_PARAM=' . $file . '|';
        }
        $note = Config::get('tpl_html_dir') ? '<br>同时检测到您后台配置中配置了模板子目录，请核对是否是此原因导致！' : '';
        echo 'CHECK_FILE=' . $tpl_file . '|';
        echo 'FILE_EXISTS=' . (file_exists($tpl_file) ? 'Y' : 'N') . '|';
        file_exists($tpl_file) ?: die('ERROR_FILE_NOT_FOUND: ' . basename($file) . ' - ' . $tpl_file);
        $tpl_c_file = $this->tplcPath . '/' . md5($tpl_file) . '.php'; // 编译文件
                                                                       
        // 当编译文件不存在，或者模板文件修改过，则重新生成编译文件
        if (! file_exists($tpl_c_file) || filemtime($tpl_c_file) < filemtime($tpl_file) || ! Config::get('tpl_parser_cache')) {
            echo 'BEFORE_COMPILE|';$content = Parser::compile($this->tplPath, $tpl_file);echo 'AFTER_COMPILE|'; // 解析模板
            file_put_contents($tpl_c_file, $content) ?: error('编译文件' . $tpl_c_file . '生成出错！请检查目录是否有可写权限！'); // 写入编译文件
            $compile = true;
        }
        echo 'COMPILE_SKIP|';
        echo 'TPL_C_FILE=' . $tpl_c_file . '|';
        echo 'BUF_START|';
        ob_start();
        $rs = include $tpl_c_file;
        $ob_content = ob_get_contents();
        ob_end_clean();
        echo 'OB_CONTENT_LEN=' . strlen($ob_content) . '|';
        if (! isset($compile)) {
            if (is_array($rs)) {
                foreach ($rs as $value) {
                    if (! file_exists($value) || filemtime($tpl_c_file) < filemtime($value) || ! Config::get('tpl_parser_cache')) {
                        $content = Parser::compile($this->tplPath, $tpl_file);
                        file_put_contents($tpl_c_file, $content) ?: error('...');
                        ob_start();
                        include $tpl_c_file;
                        $ob_content = ob_get_contents();
                        ob_end_clean();
                        break;
                    }
                }
            }
        }
        echo 'OB_CONTENT_LEN2=' . strlen($ob_content) . '|';
        return $ob_content;
    }

    // 缓存页面， 开启缓存开关时有效
    public function cache($content)
    {
        if (Config::get('tpl_html_cache') && ! query_string('p,s')) {
            $lg = cookie('lg');
            if (Config::get('open_wap') && (is_mobile() || Config::get('wap_domain') == get_http_host())) {
                $wap = 'wap';
            } else {
                $wap = '';
            }
            $cacheFile = $this->cachePath . '/' . md5(get_http_url() . $_SERVER["REQUEST_URI"] . $lg . $wap) . '.html'; // 缓存文件
            file_put_contents($cacheFile, $content) ?: error('缓存文件' . $cacheFile . '生成出错！请检查目录是否有可写权限！'); // 写入缓存文件
            return true;
        }
        return false;
    }
}