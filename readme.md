Project: 
Project is php Applications serving as REST API, to be deploy in containerized environement.

Objectives:
- Each application can comunicate to each other
- Have a monitoring capability
- All code is made as simple as posible, for learning purpose

Dependencies:
- PHP
- Mysql/MariaDB
- RabbitMQ
- Kafka (php-rdkafka)
- Zipkin (on progress..)

Deployemnt:
1. svcons: 
- running kafka or rabbitmq consumer or websocket server
- testing consumer Queue or WebSocket server
2. tperson
- simple api which act as master data
3. tmeet
- simple api which act as transactional data
4. tattend
- simple api which act as detail data of transactional tmeet