<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

$response =[
    'status' => 'success',
    'categories' => [],
    'pharmacies' =>[],
    'showcase_medicines' =>[]
];

try {
    // 1. جلب التصنيفات
    $catResult = mysqli_query($conn, "SELECT CategoryID, NameAR FROM Category");
    while ($row = mysqli_fetch_assoc($catResult)) {
        $response['categories'][] = $row;
    }

    // 2. جلب الصيدليات (مع اسم المالك ورقم التواصل من جدول User)
    $pharQuery = "
        SELECT p.PharmacistID, p.PharmacyName, p.Location, p.Latitude, p.Longitude, p.Logo,
               u.Fname, u.Lname, u.Phone
        FROM Pharmacist p
        JOIN User u ON p.PharmacistID = u.UserID
        WHERE p.IsApproved = 1
    ";
    $pharResult = mysqli_query($conn, $pharQuery);
    while ($row = mysqli_fetch_assoc($pharResult)) {
        $response['pharmacies'][] = $row;
    }

    // 3. جلب منتجات عشوائية للسلايدر
    $showcaseResult = mysqli_query($conn, "
        SELECT sm.SystemMedID, sm.Name, sm.Image, sm.ScientificName, MIN(ps.Price) as Price, c.NameAR as CategoryName
        FROM SystemMedicine sm
        JOIN PharmacyStock ps ON sm.SystemMedID = ps.SystemMedID
        JOIN Category c ON sm.CategoryID = c.CategoryID
        WHERE ps.Stock > 0
        GROUP BY sm.SystemMedID
        ORDER BY RAND() LIMIT 10");
    
    while ($row = mysqli_fetch_assoc($showcaseResult)) {
        $response['showcase_medicines'][] = $row;
    }

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>