import paramiko

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\Administrator\Downloads\106.53.201.4_id_ed25519')
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('106.53.201.4', username='root', pkey=pkey)

# Create test file
content = """<?php
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_HOST'] = 'test.susufuture.com';
$_SERVER['HTTPS'] = 'off';
define('IS_INDEX', true);
define('PATH_ROOT', '/www/wwwroot/test.susufuture.com');
require '/www/wwwroot/test.susufuture.com/core/start.php';

// Get the View instance
$view = core\\view\\View::getInstance();

// Test parser
ob_start();
$content = $view->parser('index.html');
$output = ob_get_contents();
ob_end_clean();

echo 'Content length: ' . strlen($content) . "\\n";
echo 'Output length: ' . strlen($output) . "\\n";
echo 'First 500 chars: ' . substr($content, 0, 500) . "\\n";
"""

sftp = client.open_sftp()
with sftp.file('/www/wwwroot/test.susufuture.com/test_parser8.php', 'w') as f:
    f.write(content)

# Run and capture output
stdin, stdout, stderr = client.exec_command("php /www/wwwroot/test.susufuture.com/test_parser8.php 2>&1")
output = stdout.read().decode('utf-8')
print('Output:', output)
print('Error:', stderr.read().decode('utf-8'))

client.close()
