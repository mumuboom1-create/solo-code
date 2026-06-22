import paramiko

pkey = paramiko.Ed25519Key.from_private_key_file(r'C:\Users\Administrator\Downloads\106.53.201.4_id_ed25519')
transport = paramiko.Transport(('106.53.201.4', 22))
transport.connect(username='root', pkey=pkey)
sftp = paramiko.SFTPClient.from_transport(transport)
sftp.put(r'D:\其他项目代码\K446(自适应手机端)高端大气的科技类pbootcms网站模板 带三级栏目、下载和招聘功能\fix_view.php', '/tmp/fix_view.php')
sftp.close()
transport.close()
print('Uploaded')
