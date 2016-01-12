<?php
/**
 * @Author: anchen
 * @Date:   2016-01-04 10:33:43
 * @Last Modified by:   anchen
 * @Last Modified time: 2016-01-04 11:10:06
 */

// 第1步：设置变量，如“主机”和“端口”----host & port
// 第2步：创建socket---socket()
// 第3步：绑定socket到端口和主机----bind()
// 第4步：启动socket监听---listen()
// 第5步：接受连接---accept
// 第6步：从客户端socket读取消息---read/recv
// 第6.1步：反转消息
// 第7步：发送消息给客户端socket---send
// 第8步：关闭socket---close

const MAX_CONNET_COUNTS = 5;
const MAX_STR_LEN = 128;
// 第1步：设置变量，如“主机”和“端口”----host & port
$host = isset($argv[1]) ? $argv[1] : "127.0.0.1";
$port = isset($argv[2]) ? $argv[2] : "123654";
var_dump($host);
var_dump($port);

// 第2步：创建socket---socket()
$sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($sock === false)
{
    print_sock_err($sock, "CREATE");
    exit;
}

// 第3步：绑定socket到端口和主机----bind()
$result = socket_bind($sock, $host, $port);
if ($result == false)
{
    print_sock_err($sock, "BIND");
    exit;
}

// 第4步：启动socket监听---listen()
if (false == socket_listen($sock, MAX_CONNET_COUNTS))
{
    print_sock_err($sock, "LISTEN");
    exit;
}

socket_set_nonblock($sock); // 设置成非阻塞的模式----这步是必须的， 这样可以实现多客户端

$now = microtime(true);
$last_time = $now;
while(1)
{
    $now = microtime(true);
    if ($now - $last_time > 0.01){
    // 第5步：接受连接---accept
    $new_sock = @socket_accept($sock);
    
    if (false === $new_sock)
	{
		//print_sock_err($sock, "ACCEPT"); // 对于连接不到的情况要跳出来，不要直接socket_close,
	//	exit;
	}
    else
    {
        $new_socks[] = $new_sock;
        socket_set_nonblock($new_sock); // 这步也是必须的
    }
    foreach ($new_socks as $new_sock)
    {
	    // 第6步：从客户端socket读取命令---read/recv
        $request = readRequestFromClient($new_sock, MAX_STR_LEN);
	    // 第7步：将命令处理结果发送消息给客户端socket---send
        if(!empty($request)) $response = sendResponseToClient($new_sock, $request); // 读取到请求再处理，否则一直读取不到报error
    }
    $last_time = $now;
    }
}

// 第8步：关闭socket---close
//socket_close($sock);
function readRequestFromClient($sock, $str_len)
{
	//$request = socket_read($sock, $str_len);
	$res = socket_recv($sock, &$request, $str_len, 0);
    fputs(STDOUT, $request);  // or fwrite(STDOUT, $request);
    return $request;

}

// 解析命令，并将处理结果进行返回
function sendResponseToClient($sock, $request)
{
    $arguments = preg_split('/[\n\r\t\s]+/i', $request);
    $cmd = array_shift($arguments);
    if ($cmd == "exit") 
    {
         socket_close($sock);
         exit;
    }

    $handler = "{$cmd}CommandHandler";
    if (function_exists($handler))
    {
        $response = call_user_func_array($handler, $arguments);
        $response .= PHP_EOL;
    }
    else
    {
        $response = $request;
    }

	// 解析客户端发送来的data，并且将处理结果进行response
    //$response = fgets(STDIN);

	// 第7步：发送消息给客户端socket---send
	if (socket_send($sock, $response, strlen($response), 0) == false)
	//if (socket_write($sock, $response, strlen($response)) == false)
	{
    	print_sock_err($sock, "WRITE");
    	exit;
	}
}

function print_sock_err($sock, $type)
{
    $errno = socket_last_error($sock);
    $errmsg = socket_strerror($errno);
    echo "{$type} ERROR:{$errno} - {$errmsg}" . PHP_EOL;
    //socket_close($sock);
}

function commandHandler($cmd, $a = false, $b = false)
{
    if (false === $a || false === $b || !is_numeric($a) || !is_numeric($b)) return "ERROR";
    // 如果参数不到，就进行报警
    switch($cmd)
    {
        case "add":
            $result = $a + $b;
        break;
        case "minus":
            $result = $a - $b;
        break;
        case "mult":
            $result = $a * $b;
        break;
        case "divide":
            if ($b == 0)  return "ERROR";
            $result = $a / $b;
        break;
    }
    return $result;
}

function addCommandHandler($a = false, $b = false)
{
    return commandHandler("add", $a, $b);
}

function minusCommandHandler($a = false, $b = false)
{
    return commandHandler("minus", $a, $b);
}

function multCommandHandler($a = false, $b = false)
{
    return commandHandler("mult", $a, $b);
}


function divideCommandHandler($a = false, $b = false)
{
    return commandHandler("divide", $a, $b);
}

function exitCommndHandler()
{
	// 关闭连接
}

