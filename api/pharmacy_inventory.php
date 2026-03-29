<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

$p_id = isset($_GET['pharmacy_id']) ? intval($_GET['pharmacy_id']) : 0;

$query = "SELECT ps.StockID, sm.Name, sm.ScientificName, sm.Image, ps.Price, ps.Stock, c.NameAR as CategoryName
          FROM PharmacyStock ps
          JOIN SystemMedicine sm ON ps.SystemMedID = sm.SystemMedID
          LEFT JOIN Category c ON sm.CategoryID = c.CategoryID
          WHERE ps.PharmacistID = $p_id AND ps.Stock > 0";

$result = mysqli_query($conn, $query);
$items = [];
while($row = mysqli_fetch_assoc($result)) { $items[] = $row; }

echo json_encode(["status" => "success", "items" => $items]);
?>