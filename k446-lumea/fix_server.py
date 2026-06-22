import paramiko

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\Administrator\Downloads\106.53.201.4_id_ed25519')
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('106.53.201.4', username='root', pkey=pkey)

# Read the file
stdin, stdout, stderr = client.exec_command("cat /www/wwwroot/test.susufuture.com/core/view/View.php")
content = stdout.read().decode('utf-8')

# Fix the backslash issue
content = content.replace('\\&\\&', '&&')

# Write back using SFTP
sftp = client.open_sftp()
with sftp.file('/www/wwwroot/test.susufuture.com/core/view/View.php', 'w') as f:
    f.write(content)

# Check syntax
stdin, stdout, stderr = client.exec_command("php -l /www/wwwroot/test.susufuture.com/core/view/View.php")
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))

client.close()
print('Done')
