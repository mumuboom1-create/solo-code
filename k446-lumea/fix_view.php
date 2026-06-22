<?php
// Read the current file
$lines = file('/www/wwwroot/test.susufuture.com/core/view/View.php');

// Remove lines from 138 onwards (if any)
$count = count($lines);
for ($i = 137; $i < $count; $i++) {
    unset($lines[$i]);
}

// Add missing code
$lines[] = "        \$content = ob_get_contents();\n";
$lines[] = "        ob_end_clean();\n";
$lines[] = "        return \$content;\n";
$lines[] = "    }\n";
$lines[] = "\n";
$lines[] = "    // \u7f13\u5b58\u9875\u9762\uff0c \u5f00\u542f\u7f13\u5b58\u5f00\u5173\u65f6\u6709\u6548\n";
$lines[] = "    public function cache(\$content)\n";
$lines[] = "    {\n";
$lines[] = "        if (Config::get('tpl_html_cache') \&\& ! query_string('p,s')) {\n";
$lines[] = "            \$lg = cookie('lg');\n";
$lines[] = "            if (Config::get('open_wap') \&\& (is_mobile() || Config::get('wap_domain') == get_http_host())) {\n";
$lines[] = "                \$wap = 'wap';\n";
$lines[] = "            } else {\n";
$lines[] = "                \$wap = '';\n";
$lines[] = "            }\n";
$lines[] = "            \$cacheFile = \$this->cachePath . '/' . md5(get_http_url() . \$_SERVER[\"REQUEST_URI\"] . \$lg . \$wap) . '.html'; // \u7f13\u5b58\u6587\u4ef6\n";
$lines[] = "            file_put_contents(\$cacheFile, \$content) ?: error('\u7f13\u5b58\u6587\u4ef6' . \$cacheFile . '\u751f\u6210\u51fa\u9519\uff01\u8bf7\u68c0\u67e5\u76ee\u5f55\u662f\u5426\u6709\u53ef\u5199\u6743\u9650\uff01'); // \u5199\u5165\u7f13\u5b58\u6587\u4ef6\n";
$lines[] = "            return true;\n";
$lines[] = "        }\n";
$lines[] = "        return false;\n";
$lines[] = "    }\n";
$lines[] = "}\n";

file_put_contents('/www/wwwroot/test.susufuture.com/core/view/View.php', implode('', $lines));
echo "Fixed!\n";
