import paramiko

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\Administrator\Downloads\106.53.201.4_id_ed25519')
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('106.53.201.4', username='root', pkey=pkey)

# Test preg_match
stdin, stdout, stderr = client.exec_command('php -r \'$preg3 = "#(^/index.php)|(^/)#"; $uri = "/"; preg_match($preg3, $uri, $matches1); var_dump($matches1);\'')
print('Result:', stdout.read().decode('utf-8'))
print('Error:', stderr.read().decode('utf-8'))

client.close()
