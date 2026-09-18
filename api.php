<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Database credentials
$host = "localhost"; // Since PHP runs on the same cPanel server
$db   = "uphsledu_onlinepayment";    // Change this to your DB name
$user = "uphsledu_dragpay";    // Change this to your DB username
$pass = "@dragonpay#";        // Change this to your DB password

// Connect to MySQL
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "DB Connection Failed"]);
    exit();
}

// If "action" is set in request, choose query
$action = isset($_GET['action']) ? $_GET['action'] : '';
$enrlid = isset($_GET['enrlid']) ? $_GET['enrlid'] : '';

if ($action === "getUsers") {
    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT locno, gtrno FROM tblgtrn WHERE is_valid=1 AND locno=?");
    $stmt->bind_param("s", $enrlid);
    $stmt->execute();
    $result = $stmt->get_result();
//echo $sql;
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode(["status" => "success", "data" => $users]);
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}

$conn->close();
?>
