FROM nginx:1.10

ADD local-dev.vhost.conf /etc/nginx/conf.d/default.conf
