<?php
// ==========================================
// تسجيل حساب مريض جديد | Patient Registration API
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"));

// ==========================================
// التحقق من المدخلات الأساسية | Validate Basic Inputs
// ==========================================
if (
    !empty($data->fname) &&
    !empty($data->lname) &&
    !empty($data->email) &&
    !empty($data->password)
) {
    $fname = mysqli_real_escape_string($conn, $data->fname);
    $lname = mysqli_real_escape_string($conn, $data->lname);
    $email = mysqli_real_escape_string($conn, $data->email);
    $phone = isset($data->phone) ? mysqli_real_escape_string($conn, $data->phone) : '';

    $password = password_hash($data->password, PASSWORD_DEFAULT);

    $address = isset($data->address) ? mysqli_real_escape_string($conn, $data->address) : '';
    $medicalHistory = isset($data->medicalHistory) ? mysqli_real_escape_string($conn, $data->medicalHistory) : '';
    $dob = isset($data->dob) && !empty($data->dob) ? "'" . mysqli_real_escape_string($conn, $data->dob) . "'" : "NULL";

    $lat = isset($data->lat) && !empty($data->lat) ? (float)$data->lat : "NULL";
    $lng = isset($data->lng) && !empty($data->lng) ? (float)$data->lng : "NULL";

    // ==========================================
    // التأكد من عدم تكرار الإيميل | Check Email Exists
    // ==========================================
    $check_email = mysqli_query($conn, "SELECT UserID FROM User WHERE Email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        echo json_encode(["status" => "error", "message" => "عذراً، هذا البريد الإلكتروني مسجل مسبقاً!"]);
        exit();
    }

    // ==========================================
    // بدء الحفظ في الجداول (Transaction) | Start DB Transaction
    // ==========================================
    mysqli_begin_transaction($conn);

    try {
        // إدخال في جدول المستخدمين | Insert User
        $queryUser = "INSERT INTO User (Fname, Lname, Email, Password, Phone, RoleID)
                      VALUES ('$fname', '$lname', '$email', '$password', '$phone', 3)";
        mysqli_query($conn, $queryUser);

        $userId = mysqli_insert_id($conn);

        // إدخال في جدول المرضى | Insert Patient
        $queryPatient = "INSERT INTO Patient (PatientID, Address, Latitude, Longitude, DOB, MedicalHistory)
                         VALUES ($userId, '$address', $lat, $lng, $dob, '$medicalHistory')";
        mysqli_query($conn, $queryPatient);

        // تأكيد الحفظ | Commit
        mysqli_commit($conn);

        echo json_encode(["status" => "success", "message" => "تم إنشاء حسابك بنجاح!"]);
    } catch (Exception $e) {
        // تراجع عند الخطأ | Rollback
        mysqli_rollback($conn);
        echo json_encode(["status" => "error", "message" => "حدث خطأ في السيرفر أثناء التسجيل: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "الرجاء تعبئة جميع الحقول الأساسية!"]);
}
