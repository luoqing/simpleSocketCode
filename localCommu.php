<?php
    // 完成进程之间的本地通信
    // 创建主进程的程序
    // 先创建几个子进程
    // 每个子进程中连接主进程
    
    $proc_cnt = $argv[1];
    if ($proc_cnt == false)   $proc_cnt = 1;
    $statusArr = array();
    // 1. createDaemonSocket
    $local_socket_file = "/tmp/local.sock";
    $local_socket = createLocalSocket($local_socket_file);
    const MAX_STR_LEN = 128;
  
    // 2.startWorksers
    for ($i = 0; $i < $proc_cnt; $i ++)
    {
        $fork_pid = pcntl_fork();
        if ($fork_pid < 0)
        {
            exit("unable fork");
        }
        else if ($fork_pid)
        {
            $workers[$i]['index'] = $i;
            $workers[$i]['pid'] = $fork_pid;  // 子进程pid
        }
        else
        {
            //socket_close($local_socket); // 为了防止因为多进程打开多个fd引起问题
            $pid = posix_getpid();
            $cur_worker['pid'] = $pid;
            // 与主进程通讯，读取相关命令请求，并且循环的处理逻辑
            $sock_cli = connectLocalSocket($local_socket_file);
            if ($sock_cli) 
            {
                socket_set_nonblock($sock_cli);
            }
            else
            {
                 echo "connect error" . PHP_EOL;
                 continue;
            }

            while(1)
            {   
                // 读取命令
                $result = handleReadData($sock_cli, MAX_STR_LEN, "Worker");
                if (!empty($result))
                {
                    $data = array("sum", $pid, $result);
                    formatSendData($sock_cli, $data);
                }
                // 此处也要子进程进行一些业务处理，可以连接redis，pop出一些数据，进行处理
                doQueue();
                
                usleep(1000);
            }


        }


    }

    // 3. startLoop
    $worker_clients = array();
    $lastActiveTime = microtime(true);
    while(1)
    {
        $new_sock = @socket_accept($local_socket);
        if ($new_sock)
        {
            $worker_clients[] = $new_sock;
            socket_set_nonblock($new_sock);
        }

        $now = microtime(true);

        if ($now - $lastActiveTime > 0.2)
        {
            foreach ($worker_clients as $client)
            {
                // 发送命令给子进程, 并获取到返回结果
                formatSendData($client, "status");
                $result = handleReadData($client, MAX_STR_LEN, "Master");
                if (!empty($result)) var_dump($result);
            }
            $lastActiveTime = $now;
        }
        

        
        usleep(1000);
    }
    
    function doQueue()
    {
        global $statusArr;
         $pid = posix_getpid();
        $statusArr[$pid] ++;
        $redis = new Redis();
        $redis->connect('127.0.0.1', '6379');
        // 这个中间可以是处理逻辑
        $data = "you are so beautiful!";
        $key = "test:lo";
        $redis->rpush($key, $data);
        $data = $redis->lpop($key);
        $redis->close();
        
    }

    function sumMasterHandler($arguments)
    {
        // 将这些完成
        return implode(",", $arguments);
    }

    function statusWorkerHandler($arguments)
    {
        // 其实还可以返回cpu哪些信息
        $pid = posix_getpid();
        global $statusArr;
        return json_encode($statusArr);
    }

    function parseData($data)
    {
        $arguments = preg_split('/[\n\r\t\s]+/i', trim($data));

        return $arguments;
    }

    function handleReadData($sock, $str_len, $type)
    {
        $data = socket_read($sock, $str_len);
        $bulk = parseData($data);
        $cmd = array_shift($bulk);
        $result = false;
        $handler_func = "{$cmd}{$type}Handler";
        if (function_exists($handler_func))
        {
            $result = call_user_func_array($handler_func, array($bulk));  // 这个函数写错，老出问题
        }
        else
        {
            $result = $data;
        }
        return $result;
    }

    function formatSendData($sock, $data)
    {
        if (is_array($data)) {
        	$data = implode("\t", $data);
        }
        $data = $data . PHP_EOL;
        if (socket_write($sock, $data, strlen($data)) < 0)
        {
            print_sock_err($sock, "SEND_CMD_TO_WORKER_{$data}");
            //socket_close($sock);
        }
    }

        const MAX_CONN_COUNTS = 10;
    function createLocalSocket($local_socket_file)
    {
        if (file_exists($local_socket_file)) unlink($local_socket_file);
        $local_socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if ($local_socket === false)
        {
            print_sock_err($local_socket, "CREATE");
            socket_close($local_socket);
            exit;
        }
        
        if (false === socket_bind($local_socket, $local_socket_file))
        {
            print_sock_err($local_socket, "BIND");
            socket_close($local_socket);
            exit;
        }

        if (false === socket_listen($local_socket))
        {
            print_sock_err($local_socket, "LISTEN");
            socket_close($local_socket);
            exit;
        }

        if (!socket_set_nonblock($local_socket)) {
            print_sock_err($local_socket, "NON_BLOCK");
        }

        return $local_socket;

    }

    function connectLocalSocket($local_socket_file)
    {
        $socket_cli = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if ($socket_cli === false)
        {
            print_sock_err($socket_cli, "CREATE");
            socket_close($socket_cli);
            exit;
        }

        if (false === @socket_connect($socket_cli, $local_socket_file))
        {
            print_sock_err($socket_cli, "LISTEN");
            socket_close($socket_cli);
            exit;
        }

        return $socket_cli;

    }

    function print_sock_err($sock, $type)
    {
        $errno = socket_last_error($sock);
        $errmsg = socket_strerror($errno);
        echo "{$type} ERROR:{$errno} - {$errmsg}" . PHP_EOL;
    }

