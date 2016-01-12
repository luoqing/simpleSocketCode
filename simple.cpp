#include<iostream>
#include<sys/socket.h>
#include<sys/epoll.h>
#include<netinet/in.h>
#include<arpa/inet.h>
#include<fcntl.h>
#include<unistd.h>
#include<stdio.h>
#include<errno.h>


using namespace std;

int main(int argc,char *argv[])
{
    int maxi,listenfd,connfd,sockfd,epfd,nfds;
    ssize_t n;
    char line[100];

    listenfd = socket(AF_INET,SOCK_STREAM,0);

    //声明epoll_event结构体变量,ev用于注册事件,数组用于回传要处理的事件
    struct epoll_event ev,events[20];
    epfd = epoll_create(256);
    ev.date.fd = listenfd;
    ev.events = EPOLLIN|EPOLLET;
    epoll_ctl(epfd,EPOLL_CTL_ADD,listenfd,&ev); //注册epoll事件

    struct sockaddr_in serveraddr;
    bzero(&serveraddr,sizeof(serveraddr));
    char *local_addr = "127.0.0.1";
    inet_aton(local_addr,&(serveraddr.sin_addr));
    serveraddr.sin_port=htons(8888);
    bind(listenfd,(sockaddr*)&serveraddr,sizeof(serveraddr));
    listen(listenfd,LISTENQ);
    maxi=0;

    for(;;)
    {
        //等待epoll事件发生
        nfds = epoll_wait(epfd,events,20,500);

        //处理发生的所有事件
        for(int i = 0;i <nfds;i++)
        {
            if(events[i].data.fd == listenfd) //如果新监测到一个SOCKET用户连接到了绑定的socket端口,建立新连接
            {
                struct sockaddr_in clientaddr;
                socketlen_t clilen;
                connfd = accept(listenfd,(sockaddr *)&clientaddr,&clilen);
                char *str = inet_ntoa(clientaddr.sin_addr);
                cout <<"accept a connection from "<<str<<endll;
                
                ev.data.fd = connfd;
                ev.events = EPOLLIN|EPOLLET;
                epoll_ctl(epfd,EPOLL_CTL_ADD,connfd,&ev);

            }
            else if(events[i].events & EPOLLIN) //如果是以连接的用户,并且收到数据,那么进行读入
            {
                sockfd = events[i].data.fd;
                n = read(sockfd,line,100);
                line[n] = '\0';
                cout <<"read msg :"<<line<<endl;

                ev.data.fd = sockfd;
                ev.events = EPOLLOUT|EPOLLEN;
                epoll_ctl(epfd,EPOLL_CTL_MOD,sockfd,&ev);
            }
            else if(events[i].events&EPOLLOUT)
            {
                sockfd = events[i].data.fd;
                write(sockfd,line,n);

                ev.data.fd = sockfd;
                ev.events = EPOLLIN|EPOLLET;
                epoll_ctl(epfd,EPOLL_CTL_MOD,sockfd,&ev);
            }

        }
    }

    return 0;


}