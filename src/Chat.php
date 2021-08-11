<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    private $con;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "server started";
        $this->connect();
    }

    private function connect() {
        $this->con = new \PDO("mysql:dbname=chat;host=localhost", "root", "");
        $this->con->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
    }

    public function onOpen(ConnectionInterface $conn) {
        $connectionId = $conn->resourceId;
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $paramsArray);

        $userQuery = $this->con->prepare("SELECT * FROM users WHERE email = :em");
        $userQuery->bindParam(":em", $paramsArray["email"]);
        $userQuery->execute();

        $userData = $userQuery->fetch(\PDO::FETCH_ASSOC);

        $conn->send(json_encode(array(
            "type" => "CONNECTION_ESTABLISHED",
            "connId" => $connectionId,
            "userId" => $userData["id"]
        )));

        foreach ($this->clients as $client) {
            $client->send(json_encode(array(
                "type" => "NEW_USER_CONNECTED",
                "name" => $userData["name"],
                "connId" => $connectionId,
                "userId" => $userData["id"]
            )));
        }

        $conn->userId = $userData["id"];

        $this->clients->attach($conn);

        $isOnline = true;
        $updateQuery = $this->con->prepare("UPDATE users SET chat_token = :token, conn_id = :con, is_online = :isOnline  WHERE email = :em");
        $updateQuery->bindParam(":token", $paramsArray["token"]);
        $updateQuery->bindParam(":con", $connectionId);
        $updateQuery->bindParam(":isOnline", $isOnline, \PDO::PARAM_INT);
        $updateQuery->bindParam(":em", $paramsArray["email"]);
        $updateQuery->execute();
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $msgData = json_decode($msg);

        foreach ($this->clients as $client) {
            if($client->userId == $msgData->toUserId || $client->userId == $msgData->fromUserId) {
                echo $client->userId;
                $client->send(json_encode(array(
                    "type" => "NEW_MESSAGE",
                    "fromConnectionId" => $from->resourceId,
                    "fromUserId" => $msgData->fromUserId,
                    "toConnectionId" => $msgData->toConnectionId,
                    "toUserId" => $msgData->toUserId,
                    "body" => $msgData->body
                )));
            }
        }

        $insertQuery = $this->con->prepare("INSERT INTO messages (`from_id`, `to_id`, `body`) VALUES (:from_id, :to_id, :body)");
        $insertQuery->bindParam(":from_id", $msgData->fromUserId);
        $insertQuery->bindParam(":to_id", $msgData->toUserId);
        $insertQuery->bindParam(":body", $msgData->body);

        $insertQuery->execute();
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);

        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $paramsArray);

        $closeQuery = $this->con->prepare("UPDATE users SET chat_token = :token, conn_id = :con, is_online = :isOnline  WHERE email = :em");
        $closeQuery->bindValue(':token', null, \PDO::PARAM_NULL);
        $closeQuery->bindValue(':con', null, \PDO::PARAM_NULL);
        $closeQuery->bindValue(':isOnline', false, \PDO::PARAM_INT);
        $closeQuery->bindValue(':token', null, \PDO::PARAM_NULL);
        $closeQuery->bindParam(":em", $paramsArray["email"]);
        $closeQuery->execute();

        foreach ($this->clients as $client) {
            $client->send(json_encode(array(
                "type" => "USER_DISCONNECTED",
                "connId" => $conn->resourceId,
                "userId" => $conn->userId
            )));
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}