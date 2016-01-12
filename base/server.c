
#include <pthread.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <stdio.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <unistd.h>
#include <string.h>
#include <stdlib.h>
#include <fcntl.h>
#include <sys/shm.h>

#define MYPORT  8887
#define QUEUE   20
#define BUFFER_SIZE 1024

int conn = 0;
void* recvData()
{
	char recvbuf[BUFFER_SIZE];
	char tipbuf[BUFFER_SIZE];
	memset(recvbuf, 0, sizeof(recvbuf));
	memset(tipbuf, 0, sizeof(tipbuf));
	int len = recv(conn, recvbuf, sizeof(recvbuf),0);
	//if(strcmp(recvbuf,"exit\n")==0)
	//		break;
	strcpy(tipbuf, "RECV: ");
	strcat(tipbuf, recvbuf);
	fputs(tipbuf, stdout);
}

void* sendData()
{
    char sendbuf[BUFFER_SIZE];
	memset(sendbuf, 0, sizeof(sendbuf));
	if (fgets(sendbuf, sizeof(sendbuf), stdin) == NULL) exit; 
	send(conn, sendbuf, sizeof(sendbuf), 0);

}

int main()
{
    ///定义sockfd
    int server_sockfd = socket(AF_INET,SOCK_STREAM, 0);

    ///定义sockaddr_in
    struct sockaddr_in server_sockaddr;
    server_sockaddr.sin_family = AF_INET;
    server_sockaddr.sin_port = htons(MYPORT);
    server_sockaddr.sin_addr.s_addr = htonl(INADDR_ANY);

    ///bind，成功返回0，出错返回-1
    if(bind(server_sockfd,(struct sockaddr *)&server_sockaddr,sizeof(server_sockaddr))==-1)
    {
        perror("bind");
        exit(1);
    }

    ///listen，成功返回0，出错返回-1
    if(listen(server_sockfd,QUEUE) == -1)
    {
        perror("listen");
        exit(1);
    }

    ///客户端套接字
    struct sockaddr_in client_addr;
    socklen_t length = sizeof(client_addr);

    ///成功返回非负描述字，出错返回-1
    conn = accept(server_sockfd, (struct sockaddr*)&client_addr, &length);
    if(conn<0)
    {
        perror("connect");
        exit(1);
    }

    char *pAddrname = inet_ntoa(client_addr.sin_addr);
    printf("%s:%d", pAddrname, MYPORT);
	char recvbuf[BUFFER_SIZE];
	char tipbuf[BUFFER_SIZE];
    char sendbuf[BUFFER_SIZE];
    while (1)
    {
        int len = recv(conn, recvbuf, sizeof(recvbuf),0);
        if(strcmp(recvbuf,"exit\n")==0)
            break;
        strcpy(tipbuf, "RECV: ");
        strcat(tipbuf, recvbuf);
        fputs(tipbuf, stdout);
       
        if (fgets(sendbuf, sizeof(sendbuf), stdin) == NULL) break; 
        send(conn, sendbuf, sizeof(sendbuf), 0);
        memset(sendbuf, 0, sizeof(sendbuf));
        memset(recvbuf, 0, sizeof(recvbuf));
        memset(tipbuf, 0, sizeof(tipbuf));
        //send(conn, recvbuf, len, 0);
    }
/*
    pthread_t thread[2];
    while (1)
    {
        pthread_create(&thread[0], NULL, recvData, NULL);
        pthread_create(&thread[1], NULL, sendData, NULL);
    }
*/
    close(conn);
    close(server_sockfd);
    return 0;
}


