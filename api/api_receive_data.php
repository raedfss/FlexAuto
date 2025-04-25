<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

require_once '../includes/db.php';  // تأكد أن db.php موجود أو عدله حسب موقعك

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['device_id']) && isset($data['vin']) && isset($data['status'])) {
    $device_id = htmlspecialchars($data['device_id']);
    $vin = htmlspecialchars($data['vin']);
    $status = htmlspecialchars($data['status']);
    $timestamp = date("Y-m-d H:i:s");

    $stmt = $pdo->prepare("INSERT INTO device_data (device_id, vin, status, timestamp) VALUES (?, ?, ?, ?)");
    $success = $stmt->execute([$device_id, $vin, $status, $timestamp]);

    echo json_encode(["success" => $success, "message" => $success ? "✅ Data saved successfully." : "❌ Failed to insert data."]);
} else {
    echo json_encode(["success" => false, "message" => "⚠️ Missing required fields: device_id, vin, status."]);
}
?>
