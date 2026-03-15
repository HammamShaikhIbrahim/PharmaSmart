<?php
// ==========================================
// 1. الإعدادات الأساسية والاتصال بقاعدة البيانات
// ==========================================
include('../config/database.php');
session_start();
require_once('../includes/lang.php');

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../auth/login.php");
    exit();
}

$pharmacist_id = $_SESSION['user_id'];

// ==========================================
// 2. معالجة تحديث حالة الطلب (Actions)
// ==========================================
if (isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $action = $_GET['action'];

    $valid_actions = ['Accepted', 'Rejected', 'Delivered'];

    if (in_array($action, $valid_actions)) {
        mysqli_query($conn, "UPDATE `Order` SET Status = '$action' WHERE OrderID = $order_id");

        if ($action == 'Accepted') {
            mysqli_query($conn, "UPDATE Prescription SET IsVerified = 1 WHERE OrderID = $order_id");
        }

        header("Location: orders.php");
        exit();
    }
}

// ==========================================
// 3. معالجة البحث والفلترة 
// ==========================================
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'All';
$search_query  = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// حساب الأعداد لكل فلتر
$counts_sql = "SELECT o.Status, COUNT(DISTINCT o.OrderID) as count 
                 FROM `Order` o 
                 JOIN OrderItems oi ON o.OrderID = oi.OrderID 
                 JOIN Medicine m ON oi.MedicineID = m.MedicineID 
                 WHERE m.PharmacistID = $pharmacist_id 
                 GROUP BY o.Status";
$counts_result = mysqli_query($conn, $counts_sql);

$status_counts = ['Pending' => 0, 'Accepted' => 0, 'Delivered' => 0, 'Rejected' => 0];
$total_all = 0;
while ($row = mysqli_fetch_assoc($counts_result)) {
    $status_counts[$row['Status']] = $row['count'];
    $total_all += $row['count'];
}
$status_counts['All'] = $total_all;

// شروط الاستعلام
$status_condition = ($filter_status !== 'All') ? "AND o.Status = '$filter_status'" : "";
$search_condition = "";

if (!empty($search_query)) {
    $search_condition = " AND (o.OrderID = '$search_query' OR u.Fname LIKE '%$search_query%' OR u.Lname LIKE '%$search_query%')";
}

// جلب الطلبات
$orders_query = "
    SELECT DISTINCT 
        o.OrderID, o.OrderDate, o.Status, o.TotalAmount, o.PaymentMethod, o.DeliveryAddress,
        u.Fname, u.Lname, u.Phone,
        pr.ImagePath as PrescriptionImage
    FROM `Order` o
    JOIN User u ON o.PatientID = u.UserID
    JOIN OrderItems oi ON o.OrderID = oi.OrderID
    JOIN Medicine m ON oi.MedicineID = m.MedicineID
    LEFT JOIN Prescription pr ON o.OrderID = pr.OrderID
    WHERE m.PharmacistID = $pharmacist_id $status_condition $search_condition
    ORDER BY 
        FIELD(o.Status, 'Pending', 'Accepted', 'Delivered', 'Rejected'), 
        o.OrderDate DESC
";
$orders_result = mysqli_query($conn, $orders_query);

// جلب تفاصيل الأدوية
$order_items_data = [];
$items_query = "
    SELECT oi.OrderID, m.Name, oi.Quantity, oi.SoldPrice, m.IsControlled
    FROM OrderItems oi
    JOIN Medicine m ON oi.MedicineID = m.MedicineID
    WHERE m.PharmacistID = $pharmacist_id
";
$items_result = mysqli_query($conn, $items_query);
while ($item = mysqli_fetch_assoc($items_result)) {
    $order_items_data[$item['OrderID']][] = $item;
}

// ==========================================
// 🚀 4. هندسة الـ AJAX (رسم الجدول برمجياً)
// ==========================================
ob_start(); // نبدأ بتسجيل الـ HTML في الذاكرة
if (mysqli_num_rows($orders_result) > 0) {
    while ($order = mysqli_fetch_assoc($orders_result)) {

        $statusColor = 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300 border-gray-200 dark:border-slate-600';
        $statusIcon = 'circle';

        if ($order['Status'] == 'Pending') {
            $statusColor = 'bg-[#fef08a]/60 text-[#9a3412] dark:bg-[#78350f]/30 dark:text-[#fde68a] border-[#fef08a]';
            $statusIcon = 'clock-3';
        } elseif ($order['Status'] == 'Accepted') {
            $statusColor = 'bg-[#dbeafe]/80 text-[#1e40af] dark:bg-[#1e3a8a]/30 dark:text-[#bfdbfe] border-[#dbeafe]';
            $statusIcon = 'package-open';
        } elseif ($order['Status'] == 'Delivered') {
            $statusColor = 'bg-[#dcfce7]/80 text-[#065f46] dark:bg-[#064e3b]/30 dark:text-[#a7f3d0] border-[#dcfce7]';
            $statusIcon = 'check-circle';
        } elseif ($order['Status'] == 'Rejected') {
            $statusColor = 'bg-[#ffe4e6]/80 text-[#9f1239] dark:bg-[#881337]/30 dark:text-[#fecdd3] border-[#ffe4e6]';
            $statusIcon = 'x-circle';
        }

        $current_items = $order_items_data[$order['OrderID']] ?? [];
        $has_controlled = array_reduce($current_items, fn($carry, $item) => $carry || $item['IsControlled'] == 1, false);

        $order_json = htmlspecialchars(json_encode([
            'id' => $order['OrderID'],
            'date' => date('d M Y, h:i A', strtotime($order['OrderDate'])),
            'status' => $order['Status'],
            'total' => $order['TotalAmount'],
            'patient' => $order['Fname'] . ' ' . $order['Lname'],
            'phone' => $order['Phone'],
            'address' => $order['DeliveryAddress'],
            'items' => $current_items,
            'prescription' => $order['PrescriptionImage'],
            'has_controlled' => $has_controlled
        ]));

        $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($order['Fname'] . ' ' . $order['Lname']) . '&background=e2e8f0&color=475569&rounded=true';
        $statusTextLang = isset($lang['status_' . strtolower($order['Status'])]) ? $lang['status_' . strtolower($order['Status'])] : $order['Status'];
        $dirClass = ($dir == 'rtl') ? 'dir="ltr"' : '';
?>
        <!-- 💡 تمت إزالة hover:scale-[1.01] للحفاظ على الحجم ثابتاً كما في الداشبورد -->
        <tr class="capsule-row bg-white dark:bg-slate-800 shadow-[0_2px_10px_rgba(0,0,0,0.02)] dark:shadow-none border border-gray-100 dark:border-slate-700 transition-colors duration-300 hover:bg-[#F2FBF5] dark:hover:bg-[#044E29]/20 group cursor-pointer" onclick="viewOrderDetails('<?php echo $order_json; ?>')">
            <td class="px-6 py-5 whitespace-nowrap">
                <div class="font-black text-gray-800 dark:text-white mb-1" dir="ltr">#ORD-<?php echo $order['OrderID']; ?></div>
                <div class="text-xs text-gray-500 font-bold flex items-center gap-1"><i data-lucide="clock" class="w-3.5 h-3.5"></i> <?php echo date('h:i A', strtotime($order['OrderDate'])); ?></div>
            </td>
            <td class="px-6 py-5 whitespace-nowrap">
                <div class="flex items-center gap-3 mb-1">
                    <img src="<?php echo $avatarUrl; ?>" class="w-8 h-8 rounded-full border-2 border-white dark:border-slate-700 shadow-sm">
                    <span class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($order['Fname'] . ' ' . $order['Lname']); ?></span>
                </div>
                <div class="text-xs text-gray-500 font-bold flex items-center gap-1.5">
                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                    <span dir="ltr"><?php echo htmlspecialchars($order['Phone'] ?? 'لا يوجد رقم'); ?></span>
                </div>
            </td>
            <td class="px-6 py-5">
                <div class="flex items-center gap-2.5 text-gray-600 dark:text-gray-300">
                    <div class="p-1.5 bg-gray-100 dark:bg-slate-700 rounded-lg text-gray-400 shrink-0"><i data-lucide="map-pin" class="w-4 h-4"></i></div>
                    <span class="leading-relaxed font-medium line-clamp-2"><?php echo htmlspecialchars($order['DeliveryAddress'] ?? 'الاستلام من الصيدلية'); ?></span>
                </div>
            </td>
            <td class="px-6 py-5 text-center whitespace-nowrap">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 font-black text-xs"><?php echo count($current_items); ?></span>
            </td>
            <td class="px-6 py-5 whitespace-nowrap">
                <div class="font-black text-[#0A7A48] dark:text-[#4ADE80] text-base mb-1" dir="ltr"><?php echo number_format($order['TotalAmount'], 2); ?> ₪</div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider"><?php echo $order['PaymentMethod'] == 'COD' ? 'الدفع عند الاستلام' : 'بطاقة ائتمان'; ?></div>
            </td>
            <td class="px-6 py-5 text-center whitespace-nowrap">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-[13px] font-bold border shadow-sm <?php echo $statusColor; ?>">
                    <?php echo $order['Status']; ?>
                    <i data-lucide="<?php echo $statusIcon; ?>" class="w-4 h-4"></i>
                </div>
            </td>
            <td class="px-6 py-5 text-center">
                <button class="p-2 bg-gray-50 dark:bg-slate-700 rounded-lg text-gray-500 hover:bg-gray-200 hover:text-gray-800 dark:hover:bg-slate-600 dark:hover:text-white transition-colors" onclick="event.stopPropagation(); viewOrderDetails('<?php echo $order_json; ?>')">
                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                </button>
            </td>
        </tr>
    <?php
    }
} else {
    ?>
    <tr>
        <td colspan="7" class="py-20 text-center text-gray-400">
            <i data-lucide="search-x" class="w-16 h-16 mx-auto mb-4 opacity-30"></i>
            <h2 class="text-xl font-bold">لا يوجد طلبات مطابقة للبحث أو الفلتر</h2>
        </td>
    </tr>
<?php
}
$rows_html = ob_get_clean();

// AJAX Response
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'html' => $rows_html,
        'counts' => $status_counts
    ]);
    exit();
}

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<!-- أكواد CSS المخصصة -->
<style>
    /* 💡 إعادة أحجام الفلتر لتكون طبيعية ومطابقة لفلتر الخريطة */
    .glass-radio-group {
        display: flex;
        position: relative;
        background-color: #ffffff;
        border-radius: 1rem;
        padding: 4px;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05), 0 2px 8px rgba(0, 0, 0, 0.05);
        width: fit-content;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .dark .glass-radio-group {
        background-color: #0f172a;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4), 0 2px 8px rgba(0, 0, 0, 0.1);
        border-color: #1e293b;
    }

    .glass-radio-group input {
        display: none;
    }

    .glass-radio-group label {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 110px;
        font-size: 14px;
        padding: 0.6rem 1.2rem;
        cursor: pointer;
        font-weight: 800;
        position: relative;
        z-index: 2;
        transition: all 0.3s ease-in-out;
        border-radius: 0.8rem;
        color: #64748b;
        white-space: nowrap;
        gap: 6px;
    }

    .dark .glass-radio-group label {
        color: #94a3b8;
    }

    label[for="filter-All"]:hover {
        color: #0A7A48;
    }

    label[for="filter-Pending"]:hover {
        color: #d97706;
    }

    label[for="filter-Accepted"]:hover {
        color: #2563eb;
    }

    label[for="filter-Delivered"]:hover {
        color: #059669;
    }

    .glass-radio-group input:checked+label {
        color: #ffffff !important;
        text-shadow: none !important;
    }

    .status-count {
        display: none;
        background: rgba(255, 255, 255, 0.25);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 900;
        backdrop-filter: blur(4px);
    }

    .glass-radio-group input:checked+label .status-count {
        display: inline-flex;
    }

    .glass-glider {
        position: absolute;
        top: 6px;
        bottom: 6px;
        width: calc((100% - 12px) / 4);
        border-radius: 1rem;
        z-index: 1;
        /* 💡 تم تغيير الـ cubic-bezier ليكون انزلاقاً ناعماً يابساً بدون ارتداد (No Bounce) */
        transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1), background 0.4s ease, box-shadow 0.4s ease;
    }

    #filter-All:checked~.glass-glider {
        transform: translateX(0%);
        background: #0A7A48;
        box-shadow: 0 4px 10px rgba(10, 122, 72, 0.3);
    }

    #filter-Pending:checked~.glass-glider {
        transform: translateX(100%);
        background: #fef08a;
        box-shadow: 0 4px 10px rgba(254, 240, 138, 0.4);
    }

    #filter-Accepted:checked~.glass-glider {
        transform: translateX(200%);
        background: #dbeafe;
        box-shadow: 0 4px 10px rgba(219, 234, 254, 0.4);
    }

    #filter-Delivered:checked~.glass-glider {
        transform: translateX(300%);
        background: #dcfce7;
        box-shadow: 0 4px 10px rgba(220, 252, 231, 0.4);
    }

    #filter-Pending:checked+label {
        color: #9a3412 !important;
    }

    #filter-Pending:checked+label .status-count {
        background: rgba(154, 52, 18, 0.15);
        color: #9a3412;
    }

    #filter-Accepted:checked+label {
        color: #1e40af !important;
    }

    #filter-Accepted:checked+label .status-count {
        background: rgba(30, 64, 175, 0.15);
        color: #1e40af;
    }

    #filter-Delivered:checked+label {
        color: #065f46 !important;
    }

    #filter-Delivered:checked+label .status-count {
        background: rgba(6, 95, 70, 0.15);
        color: #065f46;
    }

    html[dir="rtl"] #filter-Pending:checked~.glass-glider {
        transform: translateX(-100%);
    }

    html[dir="rtl"] #filter-Accepted:checked~.glass-glider {
        transform: translateX(-200%);
    }

    html[dir="rtl"] #filter-Delivered:checked~.glass-glider {
        transform: translateX(-300%);
    }

    .capsule-table {
        border-collapse: separate;
        border-spacing: 0 12px;
    }

    html[dir="rtl"] .capsule-row td:first-child {
        border-top-right-radius: 16px;
        border-bottom-right-radius: 16px;
    }

    html[dir="rtl"] .capsule-row td:last-child {
        border-top-left-radius: 16px;
        border-bottom-left-radius: 16px;
    }

    html[dir="ltr"] .capsule-row td:first-child {
        border-top-left-radius: 16px;
        border-bottom-left-radius: 16px;
    }

    html[dir="ltr"] .capsule-row td:last-child {
        border-top-right-radius: 16px;
        border-bottom-right-radius: 16px;
    }

    .modal-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .modal-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(10, 122, 72, 0.3);
        border-radius: 10px;
    }

    .dark .modal-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(74, 222, 128, 0.3);
    }
</style>

<main class="flex-1 p-8 bg-[#F2FBF5] dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300 relative">
    <?php include('../includes/topbar.php'); ?>

    <!-- الترويسة والفلاتر -->
    <div class="mb-8 flex flex-col xl:flex-row justify-between items-start xl:items-center gap-6">

        <!-- 💡 العنوان والأيقونة متطابقة تماماً مع الداشبورد -->
        <div class="flex items-center gap-4 shrink-0">
            <div class="p-2.5">
                <i data-lucide="shopping-bag" class="text-[#0A7A48] dark:text-[#4ADE80] w-7 h-7"></i>
            </div>
            <h1 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo isset($lang['orders']) ? $lang['orders'] : 'الطلبات'; ?></h1>
        </div>

        <div class="flex flex-col lg:flex-row items-center gap-4 w-full xl:w-auto">
            <!-- الفلتر الزجاجي (داخل حاوية سكرول نظيفة للشاشات الصغيرة) -->
            <div class="w-full xl:w-auto overflow-x-auto custom-scrollbar pb-2 -mb-2">
                <div class="glass-radio-group shrink-0 min-w-max">
                    <?php
                    $tabs = ['All' => 'جميع الطلبات', 'Pending' => 'قيد الانتظار', 'Accepted' => 'جاري التجهيز', 'Delivered' => 'مكتملة'];
                    foreach ($tabs as $key => $title):
                        $isChecked = ($filter_status == $key) ? 'checked' : '';
                    ?>
                        <input type="radio" name="order-filter" id="filter-<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo $isChecked; ?>
                            onchange="fetchData(this.value, document.getElementById('searchInput').value)" />
                        <label for="filter-<?php echo $key; ?>">
                            <?php echo $title; ?>
                            <span class="status-count" id="count-<?php echo $key; ?>"><?php echo $status_counts[$key]; ?></span>
                        </label>
                    <?php endforeach; ?>
                    <div class="glass-glider"></div>
                </div>
            </div>

            <!-- شريط البحث السريع -->
            <div class="w-full lg:w-72 flex shrink-0">
                <div class="relative group w-full">
                    <input type="text" id="searchInput" placeholder="ابحث برقم الطلب أو المريض..." value="<?php echo htmlspecialchars($search_query); ?>"
                        oninput="fetchData(document.querySelector('input[name=\'order-filter\']:checked').value, this.value)"
                        class="w-full p-3.5 rounded-2xl border border-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-white shadow-sm focus:ring-2 focus:ring-[#0A7A48] focus:border-[#0A7A48] outline-none transition-all text-sm h-full">
                    <i data-lucide="search" class="top-3.5 text-gray-400 group-focus-within:text-[#0A7A48] transition-colors <?php echo ($dir == 'rtl') ? 'absolute left-3' : 'absolute right-3'; ?> w-5 h-5"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول الطلبات -->
    <div id="ordersTableContainer" class="overflow-x-auto pb-10 min-h-[400px] transition-opacity duration-300 ease-in-out">
        <table class="w-full text-sm capsule-table">
            <thead class="text-gray-400 dark:text-gray-500 font-bold <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                <tr>
                    <th class="px-6 py-2 whitespace-nowrap">رقم الطلب</th>
                    <th class="px-6 py-2 whitespace-nowrap">العميل / الاتصال</th>
                    <th class="px-6 py-2 min-w-[200px]">العنوان</th>
                    <th class="px-6 py-2 whitespace-nowrap text-center">الأصناف</th>
                    <th class="px-6 py-2 whitespace-nowrap">الإجمالي</th>
                    <th class="px-6 py-2 whitespace-nowrap text-center">الحالة</th>
                    <th class="px-6 py-2 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody id="ordersTableBody" class="divide-y divide-gray-50 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                <?php echo $rows_html; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- البطاقة المنبثقة للطلب (المودال) -->
<div id="orderModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-[100] hidden flex justify-center items-center transition-opacity p-4">
    <div class="bg-gray-50 dark:bg-slate-900 w-full max-w-5xl max-h-[90vh] rounded-[2rem] shadow-2xl overflow-hidden flex flex-col md:flex-row transform transition-all border border-gray-200 dark:border-slate-700">

        <!-- الجانب الأيمن: الفاتورة والمنتجات -->
        <div class="flex-1 flex flex-col bg-white dark:bg-slate-800 relative z-10 w-full md:w-1/2 md:max-w-[50%]">
            <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 dark:text-white flex items-center gap-2">
                        ملخص الطلب <span id="modalOrderId" class="text-[#0A7A48] dark:text-[#4ADE80]"></span>
                    </h2>
                    <p id="modalOrderDate" class="text-xs text-gray-500 font-bold mt-1" dir="ltr"></p>
                </div>
                <button onclick="closeOrderModal()" class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-rose-100 text-gray-500 hover:text-rose-600 dark:bg-slate-700 dark:hover:bg-rose-900/30 rounded-full transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto modal-scroll flex-1 space-y-6">
                <div class="bg-[#F2FBF5] dark:bg-[#044E29]/20 p-4 rounded-2xl border border-[#0A7A48]/10 dark:border-[#4ADE80]/10">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center shadow-sm">
                            <i data-lucide="user" class="w-5 h-5 text-[#0A7A48] dark:text-[#4ADE80]"></i>
                        </div>
                        <div>
                            <h3 id="modalPatientName" class="font-bold text-gray-800 dark:text-white text-sm"></h3>
                            <p id="modalPatientPhone" class="text-xs text-gray-500 font-bold" dir="ltr"></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2 pt-3 border-t border-[#0A7A48]/10 dark:border-slate-700/50">
                        <i data-lucide="map-pin" class="w-4 h-4 text-gray-400 mt-0.5 shrink-0"></i>
                        <p id="modalPatientAddress" class="text-sm font-bold text-gray-600 dark:text-gray-300 leading-relaxed"></p>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-black text-gray-400 dark:text-gray-500 mb-3 uppercase tracking-wider">المشتريات</h3>
                    <div class="space-y-3" id="modalItemsList"></div>
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 dark:border-slate-700 bg-white dark:bg-slate-800">
                <div class="flex justify-between items-end mb-4">
                    <span class="text-sm font-bold text-gray-500">الإجمالي المطلوب:</span>
                    <h2 id="modalTotalAmount" class="text-3xl font-black text-gray-900 dark:text-white" dir="ltr"></h2>
                </div>
                <div id="dynamicActionButtons" class="flex gap-2"></div>
            </div>
        </div>

        <!-- الجانب الأيسر: الوصفة الطبية -->
        <div id="prescriptionSection" class="hidden md:flex flex-1 flex-col bg-gray-50 dark:bg-slate-900 border-r border-gray-200 dark:border-slate-700 w-full md:w-1/2 md:max-w-[50%]">
            <div class="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center gap-3">
                <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg text-amber-600 dark:text-amber-400"><i data-lucide="file-check-2" class="w-5 h-5"></i></div>
                <div>
                    <h3 class="font-black text-gray-800 dark:text-white">الوصفة الطبية (Rx)</h3>
                    <p class="text-xs text-amber-600 dark:text-amber-500 font-bold">الطلب يحتوي على أدوية مراقبة</p>
                </div>
            </div>

            <div class="flex-1 p-6 flex flex-col items-center justify-center overflow-y-auto modal-scroll">
                <a id="prescriptionImgLink" href="#" target="_blank" class="block w-full max-w-sm relative group rounded-2xl overflow-hidden border-4 border-white dark:border-slate-800 shadow-xl mb-6">
                    <img id="prescriptionImg" src="" alt="Prescription" class="w-full h-auto object-cover transition-transform duration-500 group-hover:scale-110">
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                        <i data-lucide="zoom-in" class="text-white w-10 h-10"></i>
                    </div>
                </a>

                <div class="w-full max-w-sm bg-white dark:bg-slate-800 p-4 rounded-2xl border border-rose-100 dark:border-rose-900/30 shadow-sm">
                    <div class="flex items-center gap-3 mb-2">
                        <input type="checkbox" id="verifyPrescriptionCheck" class="w-5 h-5 text-[#0A7A48] rounded border-gray-300 focus:ring-[#0A7A48]">
                        <label for="verifyPrescriptionCheck" class="text-sm font-bold text-gray-800 dark:text-gray-200 cursor-pointer select-none">
                            أقر بأني راجعت الوصفة الطبية، وصحتها، ومطابقتها للأدوية المطلوبة.
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        lucide.createIcons();
    });

    // AJAX Fetch
    let timeoutId;
    async function fetchData(status, searchQuery) {
        const container = document.getElementById('ordersTableContainer');
        const tbody = document.getElementById('ordersTableBody');

        container.style.opacity = '0.3';
        container.style.pointerEvents = 'none';

        const newUrl = `?status=${status}&search=${encodeURIComponent(searchQuery)}`;
        window.history.pushState({
            path: newUrl
        }, '', newUrl);

        clearTimeout(timeoutId);
        timeoutId = setTimeout(async () => {
            try {
                const response = await fetch(`${newUrl}&ajax=1`);
                const data = await response.json();

                tbody.innerHTML = data.html;

                for (const [key, value] of Object.entries(data.counts)) {
                    const badge = document.getElementById(`count-${key}`);
                    if (badge) badge.innerText = value;
                }

                lucide.createIcons();

            } catch (error) {
                console.error("Error fetching orders:", error);
            } finally {
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            }
        }, 300);
    }


    let currentOrderData = null;

    function viewOrderDetails(jsonString) {
        const order = JSON.parse(jsonString);
        currentOrderData = order;

        document.getElementById('modalOrderId').innerText = `#${order.id}`;
        document.getElementById('modalOrderDate').innerText = order.date;
        document.getElementById('modalPatientName').innerText = order.patient;
        document.getElementById('modalPatientPhone').innerText = order.phone || 'لا يوجد رقم';
        document.getElementById('modalPatientAddress').innerText = order.address || 'استلام من الصيدلية';
        document.getElementById('modalTotalAmount').innerText = parseFloat(order.total).toFixed(2) + ' ₪';

        const itemsList = document.getElementById('modalItemsList');
        itemsList.innerHTML = '';
        order.items.forEach(item => {
            const rxBadge = item.IsControlled == 1 ? '<span class="ml-2 bg-amber-100 text-amber-700 text-[10px] px-1.5 py-0.5 rounded font-black border border-amber-200">Rx</span>' : '';
            const itemTotal = parseFloat(item.Quantity * item.SoldPrice).toFixed(2);

            itemsList.innerHTML += `
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-700/30 rounded-xl border border-gray-100 dark:border-slate-700">
                    <div>
                        <div class="font-bold text-gray-800 dark:text-white text-sm mb-1">${item.Name} ${rxBadge}</div>
                        <div class="text-xs text-gray-500 font-bold">الكمية: ${item.Quantity} × ${item.SoldPrice} ₪</div>
                    </div>
                    <div class="font-black text-[#0A7A48] dark:text-[#4ADE80]" dir="ltr">${itemTotal} ₪</div>
                </div>
            `;
        });

        const prescriptionSection = document.getElementById('prescriptionSection');
        const verifyCheckbox = document.getElementById('verifyPrescriptionCheck');

        if (order.has_controlled) {
            prescriptionSection.classList.remove('hidden');
            verifyCheckbox.checked = false;
            if (order.prescription) {
                const imgUrl = `../uploads/${order.prescription}`;
                document.getElementById('prescriptionImg').src = imgUrl;
                document.getElementById('prescriptionImgLink').href = imgUrl;
            }
        } else {
            prescriptionSection.classList.add('hidden');
            verifyCheckbox.checked = true;
        }

        const actionsContainer = document.getElementById('dynamicActionButtons');
        if (order.status === 'Pending') {
            actionsContainer.innerHTML = `
                <button onclick="confirmOrderStatus(${order.id}, 'Rejected')" class="w-1/3 bg-gray-100 hover:bg-rose-100 text-gray-600 hover:text-rose-600 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-rose-900/30 py-3 rounded-xl font-bold transition">رفض</button>
                <button onclick="attemptAcceptOrder()" class="w-2/3 bg-[#0A7A48] hover:bg-[#044E29] text-white py-3 rounded-xl font-bold transition shadow-lg shadow-green-900/20 flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-5 h-5"></i> قبول وتجهيز
                </button>
            `;
        } else if (order.status === 'Accepted') {
            actionsContainer.innerHTML = `
                <button onclick="confirmOrderStatus(${order.id}, 'Delivered')" class="w-full bg-[#0A7A48] hover:bg-[#044E29] text-white py-3 rounded-xl font-bold transition shadow-lg shadow-green-900/20 flex items-center justify-center gap-2">
                    <i data-lucide="truck" class="w-5 h-5"></i> تم تسليم الطلب بنجاح
                </button>
            `;
        } else {
            actionsContainer.innerHTML = `<div class="w-full py-3 text-center text-sm font-bold text-gray-400 bg-gray-50 dark:bg-slate-700 rounded-xl border border-gray-100 dark:border-slate-600">تم اتخاذ إجراء مسبقاً</div>`;
        }

        lucide.createIcons();
        document.getElementById('orderModal').classList.remove('hidden');
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.add('hidden');
    }

    function attemptAcceptOrder() {
        if (currentOrderData.has_controlled && !document.getElementById('verifyPrescriptionCheck').checked) {
            Swal.fire({
                icon: 'warning',
                title: 'تنبيه أمني',
                text: 'يجب مراجعة الوصفة الطبية وتأكيد الإقرار المهني قبل قبول الطلب.',
                confirmButtonColor: '#f43f5e',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
            });
            return;
        }
        confirmOrderStatus(currentOrderData.id, 'Accepted');
    }

    function confirmOrderStatus(orderId, action) {
        let title, text, btnText, btnColor;
        if (action === 'Accepted') {
            title = 'قبول الطلب؟';
            text = 'سيتم إشعار المريض بأنك تقوم بتجهيز الطلب حالياً.';
            btnText = 'نعم، أقبل الطلب';
            btnColor = '#0A7A48';
        } else if (action === 'Rejected') {
            title = 'رفض الطلب؟';
            text = 'هل أنت متأكد من رغبتك في إلغاء هذا الطلب؟';
            btnText = 'نعم، ارفض';
            btnColor = '#f43f5e';
        } else if (action === 'Delivered') {
            title = 'تأكيد التسليم؟';
            text = 'هل تم تسليم الطلب للعميل؟';
            btnText = 'نعم، تم التسليم';
            btnColor = '#0A7A48';
        }

        Swal.fire({
            title: title,
            text: text,
            icon: (action === 'Rejected' ? 'warning' : 'question'),
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#6b7280',
            confirmButtonText: btnText,
            cancelButtonText: 'إلغاء',
            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
        }).then((res) => {
            if (res.isConfirmed) window.location.href = `orders.php?action=${action}&order_id=${orderId}`;
        });
    }
</script>

<?php include('../includes/footer.php'); ?>