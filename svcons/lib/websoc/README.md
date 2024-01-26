## Server usage
``` php
// this handler will forward each message to all clients (except the sender)
$server = new Server('ws://127.0.0.1:8080');
$server->onMessage(function ($sender, $message, $server) {
    foreach ($server->getClients() as $client) {
        if ($client !== $sender) {
            $client->send($message);
        }
    }
});
$server->run();
```

## Client usage
``` php
// this handler will echo each message to standard output
$client = new Client('ws://127.0.0.1:8080');
$client->onMessage(function ($message, $client) {
    echo $message . "\r\n";
});
$client->connect();
```

## Usage in HTML
``` js
var sock = new WebSocket('ws://127.0.0.1:8080/');
sock.send("TEST");
```