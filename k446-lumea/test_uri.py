import paramiko

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\Administrator\Downloads\106.53.201.4_id_ed25519')
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('106.53.201.4', username='root', pkey=pkey)

# Test REQUEST_URI
stdin, stdout, stderr = client.exec_command('php -r \'var_dump($_SERVER["REQUEST_URI"]);\'')
print('REQUEST_URI:', stdout.read().decode('utf-8'))
print('Error:', stderr.read().decode('utf-8'))

client.close()
