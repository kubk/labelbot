FROM mysql:5.7.22

COPY init.sh /docker-entrypoint-initdb.d
COPY mysqld.cnf /etc/mysql/mysql.conf.d

RUN usermod -u 1000 www-data

