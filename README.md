# simpleSocketCode

### 服务器间进程通信使用方法
#### 服务端
  php php-server.php [server_ip] [server_host]
  eg: php php-server.php 127.0.0.1 12345
  以本机作为服务器，开放12345的端口
#### 客户端
* 采用telnet的方法连接服务端,telnet [server_ip] [server_port]
* 如果你的客户端服务器安装了redis，可以使用redis-cli -h [server_ip] -p [server_port]
* 程序实现客户端连接，php php-cli.php [server_ip] [server_host]
