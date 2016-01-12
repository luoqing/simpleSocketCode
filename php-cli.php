<?php

    const MAX_STR_LEN = 128;
    
    // 第1步：设置变量，如“主机”和“端口”----host & port
    $host = isset($argv[1]) ? $argv[1] : "127.0.0.1";
    $port = isset($argv[2]) ? $argv[2] : "123654";

    // 第2步：创建socket
    $sock_cli = @socket_create(AF_INET, SOCK_STREAM, 0); // SOL_TCP

    // 第3步：连接服务器---connect
    if (false == socket_connect($sock_cli, $host, $port))
    {
        print_sock_err($sock_cli, "CONNECT");
    }

    while(1)
    {
        // 第4步，写入命令请求
        $data = fgets(STDIN);
        $len = socket_write($sock_cli, $data, strlen($data));
        if ($len < 0)
        {
            print_sock_err($sock_cli, "WRITE");
            exit;
        }

        // 第5步，读取返回结果
        $response = socket_read($sock_cli, MAX_STR_LEN);
        if ($len < 0)
        {
            print_sock_err($sock_cli, "READ");
            exit;
        }
        fwrite(STDOUT, $response);
    }

    function print_sock_err($sock, $type)
    {
        $errno = socket_last_error($sock);
        $errmsg = socket_strerror($errno);
        echo "{$type} ERROR:{$errno} - {$errmsg}" . PHP_EOL;
        socket_close($sock);
    }

    

