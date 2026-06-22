<?php
return array(
    
    
    'debug' => true,

    // 授权检查开关，未配置时默认开启；本地调试如需关闭，设置为 false
    // 'license_check' => false,

    // 定义CMS名称
    'cmsname' => 'PbootCMS',
    
    // 授权码，多个授权码使用英文逗号隔开，如：'aaaaa,bbbbb'
    'sn' => 'test.susufuture.com:fcuxwW5BZ_j9risHd0bpwydooOZHtpNcjOHe2YdBEisMIDusMI2KyS_BPpqJ5K8a67awY_weXob08rdkSGz6D2CK4ypbBxi39w_6Y7KjxA9q1VpXgapdMwoFQYfdipcMc7UIA4-9emvxofVKNsh-fp9HcSPD9E4o1ooWUNjXK3yZmQRqMUQVE1jyviPWKKm1J0I86RrHeuiI-3YHgHiB_azCuHLJ2NVpI8YiwFdnrnOlr4xTVKptisCTt5ISB-Qt2_zgR7MUN9F5kC5ap2kKpXDsEPIN0sUGdWF0k4GLWvv4cCFiyvj8XiQtP3GQKwVkZ8cwOrkFtsR2X4T3GE6gDw',
    
    // 授权用户手机
    'sn_user' => '',
    
    // 模板内容输出缓存开关
    'tpl_html_cache' => 0,
    
    // 模板内容缓存有效时间（秒）
    'tpl_html_cache_time' => 900,
    
    // 会话文件使用网站路径
    'session_in_sitepath' => 1,
    
    // 默认分页大小
    'pagesize' => 15,
    
    // 分页条数字数量
    'pagenum' => 5,
    
    // 访问页面规则，如禁用浏览器、操作系统类型
    'access_rule' => array(
        'deny_bs' => 'MJ12bot,IE6,IE7'
    ),
    
    // 上传配置
    'upload' => array(
        'format' => 'jpg,jpeg,png,gif,xls,xlsx,doc,docx,ppt,pptx,rar,zip,pdf,txt,mp4,avi,flv,rmvb,mp3,otf,ttf',
        'max_width' => '1920',
        'max_height' => ''
    ),
    
    // 缩略图配置
    'ico' => array(
        'max_width' => '1000',
        'max_height' => '1000'
    ),
    
    // 模块模板路径定义
    'tpl_dir' => array(
        'home' => '/template'
    )

);
 