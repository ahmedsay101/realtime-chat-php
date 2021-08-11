<?php
require_once("../config.php");

$results = array();
$usersQuery = DB::connect()->prepare("SELECT * FROM users WHERE is_online = :isOn");
$usersQuery->bindValue(':isOn', true, PDO::PARAM_INT);
$usersQuery->execute();

while($row = $usersQuery->fetch(PDO::FETCH_ASSOC)) {
    $results[] = $row;
}

echo json_encode($results);
?>