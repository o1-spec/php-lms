<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'library_db';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function executeQuery($conn, $query, $params = [], $types = '') {
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt->execute()) {
        return $stmt;
    } else {
        die("Execute failed: " . $stmt->error);
    }
}

function getRow($conn, $query, $params = [], $types = '') {
    $stmt = executeQuery($conn, $query, $params, $types);
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getRows($conn, $query, $params = [], $types = '') {
    $stmt = executeQuery($conn, $query, $params, $types);
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
