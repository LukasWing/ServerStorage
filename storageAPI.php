<?php
include "config.php";
function serverVal($db, $debug, $sql){
    if(!$result = $db->query($sql) and $debug){
        echo $db->error."Error from serverVal(). Sql was: ".$sql;
        return NULL;
        
    }else{
        if($assoc = $result->fetch_assoc()){
            if($debug and array_values($assoc)[1]){
                echo "Error multiple values from serverVal. Sql was: ".$sql;
                return NULL;
            }else {
                return array_values($assoc)[0];
            }
        }
    }
}

// inserts or update
function post($db, $page, $key, $value){
    $sql = "SELECT value FROM KeyValueStore WHERE page='$page' AND theKey='$key'";
    $result = $db -> query($sql);
    if($result -> num_rows == 0){
        $sql = "INSERT INTO KeyValueStore (page, theKey, value)
                VALUES ('$page','$key','$value')";
        if(!$db -> query($sql)) {
            echo "Error on insert";
            http_response_code(400); 
            exit(1);
        }
    } else {
        $sql = "UPDATE KeyValueStore SET value='$value' WHERE page='$page' AND theKey='$key'";
        if(!$db -> query($sql)) {
            echo "Error on update";
            http_response_code(400); 
            exit(1);
        }
    }
}

function get($db, $page, $key){
    $sql = "SELECT value FROM KeyValueStore WHERE page='$page' AND theKey='$key'";
    $keySql = "SELECT theKey FROM KeyValueStore WHERE page='$page' AND theKey='$key'";
    $value = serverVal($db, false, $sql);
    $dbKey = serverVal($db, false, $keySql);
    if($value && $key == $dbKey) return $value;
    else{
        echo "Error getting value";
        http_response_code(404); 
        exit(1);
    }
}

function deleteValue($db, $page, $key){
    $sql = "DELETE FROM KeyValueStore WHERE page='$page' AND theKey='$key'";
    if(!$db -> query($sql) || $db->affected_rows == 0){
        echo "Error on deletion:".$db->error;
        http_response_code(404);
        exit(1);
    }
}
function deletePage($db, $page){
    $sql = "DELETE FROM KeyValueStore WHERE page='$page'";
    if(!$db -> query($sql)){
        echo "Error on clearing:".$db->error;
        http_response_code(400);
        exit(1);
    }
}

$headers = apache_request_headers();
$token = intval($headers['Authorization']);

$data = json_decode(file_get_contents('php://input'), true);
$page = $data["page"] ? $db->real_escape_string($data["page"]) : $db->real_escape_string($_GET["page"]);
if(notPermitted($token) || !isset($page)){
  http_response_code(400); 
  exit(1);
}

$method = $_SERVER["REQUEST_METHOD"];
switch ($method) {
    case 'POST':
        $value = $db->real_escape_string($data["value"]);
        $key = $db->real_escape_string($data["key"]);
        if (isset($key, $value)) post($db, $page, $key, $value);
        else {
            http_response_code(400); 
            exit(1);
        }
        echo json_encode($data);
        http_response_code(201);
        exit(0);
        break;
        
    case 'GET':
        $key = $db->real_escape_string($_GET["key"]);
        if (isset($key)) {
            $resource = [
                "key"  => $key,
                "value" => get($db, $page, $key),
                "page" => $page,
            ];
            echo json_encode($resource);
            http_response_code(200);
            exit(0);
        } else {
            echo "Error getting key and page";
            http_response_code(400); 
            exit(1);
        }
        break;

    case 'DELETE':
        if (isset($key)) {
            $key = $db->real_escape_string($data["key"]);
            deleteValue($db, $page, $key);
            http_response_code(204);
            exit(0);
        } else if (isset($page)) {
            deletePage($db, $page);
            http_response_code(204); 
            exit(0);
        } else {
            echo "Error getting key and page";
            http_response_code(400); 
            exit(1);
        }
        break;
    
    default:
        echo "Unsupported method";
        http_response_code(400); 
        exit(1);
        break;
}