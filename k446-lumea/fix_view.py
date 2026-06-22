import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('106.53.201.4', username='root', key_filename=r'C:\Users\Administrator\Downloads\106.53.201.4_id_ed25519')
sftp = client.open_sftp()

# Read current View.php
with sftp.file('/www/wwwroot/test.susufuture.com/core/view/View.php', 'r') as f:
    content = f.read().decode('utf-8')

# Fix the file_put_contents line that was commented out
old_line = "            $content = Parser::compile($this->tplPath, $tpl_file); // 解析模板            file_put_contents($tpl_c_file, $content) ?: error('编译文件' . $tpl_c_file . '生成出错！请检查目录是否有可写权限！'); // 写入编译文件"
new_lines = """            $content = Parser::compile($this->tplPath, $tpl_file); // 解析模板
            file_put_contents($tpl_c_file, $content) ?: error('编译文件' . $tpl_c_file . '生成出错！请检查目录是否有可写权限！'); // 写入编译文件"""

if old_line in content:
    content = content.replace(old_line, new_lines)
    with sftp.file('/www/wwwroot/test.susufuture.com/core/view/View.php', 'w') as f:
        f.write(content.encode('utf-8'))
    print("Fixed View.php")
else:
    print("Pattern not found, checking current content...")
    # Show lines around 120-125
    lines = content.split('\n')
    for i, line in enumerate(lines[118:126], start=119):
        print(f"{i}: {line}")

# Verify fix
stdin, stdout, stderr = client.exec_command("php -l /www/wwwroot/test.susufuture.com/core/view/View.php")
print(stdout.read().decode())
print(stderr.read().decode())

client.close()
