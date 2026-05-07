<?php
// ==========================================
// جلب بيانات الملف الشخصي للمريض | Fetch Patient Profile Data API
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// ==========================================
// جلب البيانات من جداول المستخدم والمريض | Fetch Data from User and Patient Tables
// ==========================================
if ($user_id > 0) {
    $query = "SELECT u.Fname, u.Lname, u.Email, u.Phone,
                     p.DOB, p.Address, p.MedicalHistory
              FROM User u
              JOIN Patient p ON u.UserID = p.PatientID
              WHERE u.UserID = $user_id AND u.RoleID = 3";

    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        echo json_encode([
            "status" => "success",
            "data" => $user
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "المستخدم غير موجود"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "معرف المستخدم مفقود"]);
}
?>