<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../config/database.php';

$med_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($med_id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID دواء غير صحيح"]);
    exit();
}

$response = [
    "status" => "success",
    "details" => null,
    "pharmacies" => []
];

// 1. جلب بيانات الدواء الأساسية
$medSql = "SELECT sm.*, c.NameAR as CategoryName 
           FROM SystemMedicine sm 
           LEFT JOIN Category c ON sm.CategoryID = c.CategoryID 
           WHERE sm.SystemMedID = $med_id";
$medRes = mysqli_query($conn, $medSql);

if ($medRes && mysqli_num_rows($medRes) > 0) {
    $response['details'] = mysqli_fetch_assoc($medRes);
} else {
    echo json_encode(["status" => "error", "message" => "الدواء غير موجود"]);
    exit();
}

// 2. جلب جميع الصيدليات التي تملك هذا الدواء في مخزونها
$stockSql = "SELECT ps.Price, ps.Stock, ph.PharmacyName, ph.Location, ph.Latitude, ph.Longitude, ph.PharmacistID 
             FROM PharmacyStock ps
             JOIN Pharmacist ph ON ps.PharmacistID = ph.PharmacistID
             WHERE ps.SystemMedID = $med_id AND ps.Stock > 0 AND ps.ExpiryDate >= CURDATE()
             ORDER BY ps.Price ASC";
$stockRes = mysqli_query($conn, $stockSql);

if ($stockRes) {
    while ($row = mysqli_fetch_assoc($stockRes)) {
        $row['Price'] = number_format((float)$row['Price'], 2, '.', '');
        $response['pharmacies'][] = $row;
    }
}

echo json_encode($response);
?>