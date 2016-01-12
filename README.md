# simpleSocketCode

### 目的
* 实现多客户端连接服务器
* 分别实现阻塞和非阻塞
* 实现回射和聊天等功能
* 会使用select & epoll，并且了解他们之间的区别
* 会使用php和C实现简单的socket编程，理解socket编程的基本思路和基本原理

### 代码目录介绍
base---基本的服务端接收客户端发来的信息
echo---服务器和客户端可以相互发送信息
epoll---采用epoll来实现多个客户端连接服务器（能够多客户端，也可以往返回复。但是不能实现一直是单方面发送多条消息，有待改进）
chat---实现服务器和客户端的相互聊天---这个虽然是多客户端，但是是阻塞的。（后续是需要该进成非阻塞的，看看能不能实现，因为有一个问题，如果两个客户端同时给服务器端发送消息，服务器端该首先给哪个客户端回复。服务端在屏幕输入消息，是该发送给哪个客户端？）
php-server.php---php实现服务端
php-cli.php---php实现客户端的连接
localComm.php---实现的是父进程和子进程之间的通信

### 服务器间进程通信使用方法
#### 服务端
  php php-server.php [server_ip] [server_host]
  eg: php php-server.php 127.0.0.1 12345
  以本机作为服务器，开放12345的端口
#### 客户端
* 采用telnet的方法连接服务端,telnet [server_ip] [server_port]
* 如果你的客户端服务器安装了redis，可以使用redis-cli -h [server_ip] -p [server_port]
* 程序实现客户端连接，php php-cli.php [server_ip] [server_host]


### 本地进程之间的通信
localComm.php实现的是父进程和子进程之间的通信。父进程和子进程相互发送命令进行解析。
