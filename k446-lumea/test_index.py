import paramiko

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\Administrator\Downloads\106.53.201.4_id_ed25519')
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('106.53.201.4', username='root', pkey=pkey)

# Create test file
content = """<?php
define('IS_INDEX', true);
define('PATH_ROOT', '/www/wwwroot/test.susufuture.com');
require '/www/wwwroot/test.susufuture.com/core/start.php';
"""

sftp = client.open_sftp()
with sftp.file('/www/wwwroot/test.susufuture.com/test_index.php', 'w') as f:
    f.write(content)

# Run and capture output
stdin, stdout, stderr = client.exec_command("php /www/wwwroot/test.susufuture.com/test_index.php 2>&1")
print('Output:', stdout.read().decode('utf-8')[:500])
print('Error:', stderr.read().decode('utf-8'))

client.close()
