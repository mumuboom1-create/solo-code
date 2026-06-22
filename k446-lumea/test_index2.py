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
"""

sftp = client.open_sftp()
with sftp.file('/www/wwwroot/test.susufuture.com/test_index2.php', 'w') as f:
    f.write(content)

# Run and capture output
stdin, stdout, stderr = client.exec_command("php /www/wwwroot/test.susufuture.com/test_index2.php 2>&1")
output = stdout.read().decode('utf-8')
print('Output length:', len(output))
print('Output:', output[:500])
print('Error:', stderr.read().decode('utf-8'))

client.close()
