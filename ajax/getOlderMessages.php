<?php
require_once("../config.php");

if(isset($_GET["to"]) && isset($_GET["from"])) {
    $results = array();

    $msgQuery = DB::connect()->prepare("SELECT * FROM messages WHERE from_id = :user1 AND to_id = :user2 OR from_id = :user2 AND to_id = :user1");
    $msgQuery->bindParam(":user1", $_GET["to"]);
    $msgQuery->bindParam(":user2", $_GET["from"]);
    $msgQuery->execute();

    while($row = $msgQuery->fetch(PDO::FETCH_ASSOC)) {
        $results[] = $row;
    }

    echo json_encode($results);
}
else {
    http_response_code(400);
    die();
}
?>