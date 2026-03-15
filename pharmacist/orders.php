<?php
// ==========================================
// 1. الإعدادات الأساسية والاتصال بقاعدة البيانات
// ==========================================
include('../config/database.php');
session_start();
require_once('../includes/lang.php');

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
        $update_sql = "UPDATE `Order` SET Status = '$action' WHERE OrderID = $order_id";
        mysqli_query($conn, $update_sql);
        if ($action == 'Accepted') {
            mysqli_query($conn, "UPDATE Prescription SET IsVerified = 1 WHERE OrderID = $order_id");
        }
        if (isset($_GET['ajax'])) {
            echo json_encode(['success' => true]);
            exit();
        }
        header("Location: orders.php");
        exit();
    }
}

// ==========================================
// 3. جلب بيانات الطلبات (تدعم AJAX للفلتر الزجاجي)
// ==========================================
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'All';
$status_condition = ($filter_status !== 'All') ? "AND o.Status = '$filter_status'" : "";

$orders_query = "
    SELECT DISTINCT 
        o.OrderID, o.OrderDate, o.Status, o.TotalAmount, o.PaymentMethod, o.DeliveryAddress, o.PatientID,
        u.Fname, u.Lname, u.Phone,
        pr.ImagePath as PrescriptionImage
    FROM `Order` o
    JOIN User u ON o.PatientID = u.UserID
    JOIN OrderItems oi ON o.OrderID = oi.OrderID
    JOIN Medicine m ON oi.MedicineID = m.MedicineID
    LEFT JOIN Prescription pr ON o.OrderID = pr.OrderID
    WHERE m.PharmacistID = $pharmacist_id $status_condition
    ORDER BY FIELD(o.Status, 'Pending', 'Accepted', 'Delivered', 'Rejected'), o.OrderDate DESC
";
$orders_result = mysqli_query($conn, $orders_query);

// ==========================================
// 4. جلب تفاصيل الأدوية لكل طلب
// ==========================================
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
// 5. دالة مساعدة لترجمة وتلوين حالة الطلب
// ==========================================
function getStatusDisplay($status, $lang)
{
    switch ($status) {
        case 'Pending':
            return ['class' => 'bg-amber-500/10 text-amber-500',     'text' => $lang['filter_pending'], 'icon' => 'fa-solid fa-bell fa-shake'];
        case 'Accepted':
            return ['class' => 'bg-blue-500/10 text-blue-500',       'text' => $lang['filter_processing'], 'icon' => 'fa-solid fa-box-open'];
        case 'Delivered':
            return ['class' => 'bg-emerald-500/10 text-emerald-500', 'text' => $lang['filter_delivered'], 'icon' => 'fa-solid fa-check-double'];
        case 'Rejected':
            return ['class' => 'bg-rose-500/10 text-rose-500',       'text' => $lang['filter_rejected'], 'icon' => 'fa-solid fa-ban'];
        default:
            return ['class' => '', 'text' => $status, 'icon' => ''];
    }
}

// ==========================================
// 6. تجميع الطلبات حسب المريض
// ==========================================
$grouped_orders = [];
if ($orders_result && mysqli_num_rows($orders_result) > 0) {
    while ($order = mysqli_fetch_assoc($orders_result)) {
        $patient_id = $order['PatientID'];
        if (!isset($grouped_orders[$patient_id])) {
            $grouped_orders[$patient_id] = [
                'fname'   => $order['Fname'],
                'lname'   => $order['Lname'],
                'phone'   => $order['Phone'],
                'orders'  => []
            ];
        }
        $grouped_orders[$patient_id]['orders'][] = $order;
    }
}

// ==========================================
// 7. إنشاء HTML الجدول (يستخدم عند التحميل الأولي ومع AJAX)
// ==========================================
function renderTableRows($grouped_orders, $order_items_data, $lang)
{
    ob_start();
    if (!empty($grouped_orders)) {
        foreach ($grouped_orders as $patient_id => $patient_data):
            $orders      = $patient_data['orders'];
            $order_count = count($orders);
            $has_multiple = $order_count > 1;

            $first_order = $orders[0];
            $statusDisplay   = getStatusDisplay($first_order['Status'], $lang);

            $current_order_items = isset($order_items_data[$first_order['OrderID']]) ? $order_items_data[$first_order['OrderID']] : [];
            $has_controlled = false;
            foreach ($current_order_items as $ci) {
                if ($ci['IsControlled'] == 1) $has_controlled = true;
            }

            $first_json = htmlspecialchars(json_encode([
                'id' => $first_order['OrderID'],
                'date' => date('Y-m-d h:i A', strtotime($first_order['OrderDate'])),
                'status' => $first_order['Status'],
                'total' => $first_order['TotalAmount'],
                'patient' => $first_order['Fname'] . ' ' . $first_order['Lname'],
                'phone' => $first_order['Phone'],
                'address' => $first_order['DeliveryAddress'],
                'items' => $current_order_items,
                'prescription' => $first_order['PrescriptionImage'],
                'has_controlled' => $has_controlled
            ]));

            $uid = 'patient_' . $patient_id;
            // تجهيز نص البحث المخفي
            $searchableText = strtolower("ord-{$first_order['OrderID']} {$patient_data['fname']} {$patient_data['lname']} {$patient_data['phone']}");
?>

            <tr class="main-order-row border-b border-slate-200 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-all duration-200 <?php echo $has_multiple ? 'cursor-pointer group' : 'cursor-pointer'; ?>"
                data-searchable="<?php echo $searchableText; ?>"
                <?php if ($has_multiple): ?>onclick="togglePatientOrders('<?php echo $uid; ?>')" <?php else: ?>onclick="viewOrderDetails('<?php echo $first_json; ?>')" <?php endif; ?>>

                <td class="px-6 py-5 font-mono font-bold text-gray-900 dark:text-white">
                    <div class="flex items-center gap-2">
                        <?php if ($has_multiple): ?>
                            <div id="arrow_<?php echo $uid; ?>" class="w-6 h-6 flex items-center justify-center rounded-full bg-[#113f2b]/10 text-[#113f2b] dark:text-[#d2f34c] transition-transform duration-300">
                                <i class="fa-solid fa-chevron-left text-[10px]"></i>
                            </div>
                        <?php endif; ?>
                        <span>#ORD-<?php echo $first_order['OrderID']; ?></span>
                        <?php if ($has_multiple): ?>
                            <span class="text-[10px] font-black bg-[#113f2b] text-[#d2f34c] px-2 py-0.5 rounded-full shadow-sm">+<?php echo $order_count - 1; ?></span>
                        <?php endif; ?>
                    </div>
                </td>

                <td class="px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-gray-500 font-bold text-sm">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($patient_data['fname'] . ' ' . $patient_data['lname']); ?></div>
                            <div class="text-xs text-gray-500 dark:text-slate-400" dir="ltr"><?php echo htmlspecialchars($patient_data['phone']); ?></div>
                        </div>
                    </div>
                </td>

                <td class="px-6 py-5">
                    <div class="font-medium text-gray-700 dark:text-slate-300"><?php echo date('Y-m-d', strtotime($first_order['OrderDate'])); ?></div>
                    <div class="text-xs text-gray-500 dark:text-slate-500"><i class="fa-regular fa-clock"></i> <?php echo date('h:i A', strtotime($first_order['OrderDate'])); ?></div>
                </td>

                <td class="px-6 py-5">
                    <span class="px-3 py-1.5 rounded-full text-xs font-bold border flex items-center gap-1.5 w-fit <?php echo $statusDisplay['class']; ?> border-current border-opacity-20">
                        <i class="<?php echo $statusDisplay['icon']; ?>"></i> <?php echo $statusDisplay['text']; ?>
                    </span>
                </td>

                <td class="px-6 py-5 font-black text-[#113f2b] dark:text-[#d2f34c]" dir="ltr">
                    <?php echo number_format($first_order['TotalAmount'], 2); ?> <?php echo isset($lang['currency']) ? $lang['currency'] : '₪'; ?>
                </td>

                <td class="px-6 py-5 text-center">
                    <div class="flex items-center justify-center gap-3" onclick="event.stopPropagation()">
                        <button onclick="viewOrderDetails('<?php echo $first_json; ?>')" class="p-2 rounded-lg text-gray-400 hover:bg-gray-200 hover:text-gray-700 transition-colors" title="<?php echo $lang['details_btn']; ?>">
                            <i class="fa-solid fa-eye text-lg"></i>
                        </button>
                        <?php if ($first_order['Status'] == 'Pending'): ?>
                            <button onclick="confirmOrderStatus(<?php echo $first_order['OrderID']; ?>, 'Accepted')" class="p-2 rounded-lg text-[#113f2b] dark:text-[#d2f34c] hover:bg-[#113f2b]/10 transition-colors" title="<?php echo $lang['accept_prepare']; ?>">
                                <i class="fa-solid fa-check-circle text-lg"></i>
                            </button>
                            <button onclick="confirmOrderStatus(<?php echo $first_order['OrderID']; ?>, 'Rejected')" class="p-2 rounded-lg text-rose-500 hover:bg-rose-500/10 transition-colors" title="<?php echo $lang['filter_rejected']; ?>">
                                <i class="fa-solid fa-circle-xmark text-lg"></i>
                            </button>
                        <?php endif; ?>
                        <?php if ($first_order['Status'] == 'Accepted'): ?>
                            <button onclick="confirmOrderStatus(<?php echo $first_order['OrderID']; ?>, 'Delivered')" class="p-2 rounded-lg text-blue-500 hover:bg-blue-500/10 transition-colors" title="<?php echo $lang['confirm_delivery']; ?>">
                                <i class="fa-solid fa-motorcycle text-lg"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>

            <?php if ($has_multiple):
                foreach (array_slice($orders, 1) as $sub_order):
                    $subStatusDisplay   = getStatusDisplay($sub_order['Status'], $lang);
                    $sub_items = isset($order_items_data[$sub_order['OrderID']]) ? $order_items_data[$sub_order['OrderID']] : [];
                    $sub_has_controlled = false;
                    foreach ($sub_items as $si) {
                        if ($si['IsControlled'] == 1) $sub_has_controlled = true;
                    }

                    $sub_json = htmlspecialchars(json_encode([
                        'id' => $sub_order['OrderID'],
                        'date' => date('Y-m-d h:i A', strtotime($sub_order['OrderDate'])),
                        'status' => $sub_order['Status'],
                        'total' => $sub_order['TotalAmount'],
                        'patient' => $sub_order['Fname'] . ' ' . $sub_order['Lname'],
                        'phone' => $sub_order['Phone'],
                        'address' => $sub_order['DeliveryAddress'],
                        'items' => $sub_items,
                        'prescription' => $sub_order['PrescriptionImage'],
                        'has_controlled' => $sub_has_controlled
                    ]));

                    $subSearchableText = strtolower("ord-{$sub_order['OrderID']} {$sub_order['Fname']} {$sub_order['Lname']}");
            ?>
                    <tr class="sub-order-row hidden border-b border-dashed border-slate-200 dark:border-slate-700/30 bg-slate-50/50 dark:bg-slate-800/20 transition-all duration-200 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/50"
                        data-parent="<?php echo $uid; ?>"
                        data-searchable="<?php echo $subSearchableText; ?>"
                        onclick="viewOrderDetails('<?php echo $sub_json; ?>')">

                        <td class="px-6 py-4 font-mono font-bold text-gray-500 dark:text-slate-400">
                            <div class="flex items-center gap-2 ps-8">
                                <div class="w-1 h-8 rounded-full bg-[#113f2b]/30"></div>
                                <span class="text-sm">#ORD-<?php echo $sub_order['OrderID']; ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs text-gray-400 dark:text-slate-500 italic ps-12"><?php echo htmlspecialchars($sub_order['Fname'] . ' ' . $sub_order['Lname']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-600 dark:text-slate-400"><?php echo date('Y-m-d', strtotime($sub_order['OrderDate'])); ?></div>
                            <div class="text-xs text-gray-400 dark:text-slate-500"><?php echo date('h:i A', strtotime($sub_order['OrderDate'])); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-[10px] font-bold border flex items-center gap-1 w-fit <?php echo $subStatusDisplay['class']; ?> border-current border-opacity-20">
                                <i class="<?php echo $subStatusDisplay['icon']; ?>"></i> <?php echo $subStatusDisplay['text']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-600 dark:text-slate-400 text-sm" dir="ltr">
                            <?php echo number_format($sub_order['TotalAmount'], 2); ?> <?php echo isset($lang['currency']) ? $lang['currency'] : '₪'; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-3" onclick="event.stopPropagation()">
                                <button onclick="viewOrderDetails('<?php echo $sub_json; ?>')" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 transition-colors">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php endforeach;
    } else { ?>
        <tr>
            <td colspan="6" class="text-center py-20">
                <div class="flex flex-col items-center gap-4 text-slate-500">
                    <i class="fa-solid fa-box-open text-5xl text-gray-300"></i>
                    <span class="font-bold text-lg"><?php echo isset($lang['no_orders_desc']) ? $lang['no_orders_desc'] : $lang['no_data']; ?></span>
                </div>
            </td>
        </tr>
<?php }
    return ob_get_clean();
}

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    echo renderTableRows($grouped_orders, $order_items_data, $lang);
    exit();
}

include('../includes/header.php');
include('../includes/sidebar.php');
?>

<!-- استدعاء FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* ================================================== */
    /* == تصميم الفلتر Glass Radio Group (Uiverse) == */
    /* ================================================== */
    .glass-radio-group {
        display: flex;
        position: relative;
        background: rgba(255, 255, 255, 0.6);
        border-radius: 1rem;
        backdrop-filter: blur(12px);
        box-shadow: inset 1px 1px 4px rgba(255, 255, 255, 0.6), inset -1px -1px 6px rgba(0, 0, 0, 0.1), 0 4px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        width: fit-content;
    }

    .dark .glass-radio-group {
        background: rgba(0, 0, 0, 0.2);
        box-shadow: inset 1px 1px 4px rgba(255, 255, 255, 0.1), inset -1px -1px 6px rgba(0, 0, 0, 0.3), 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .glass-radio-group input {
        display: none;
    }

    .glass-radio-group label {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 100px;
        font-size: 13px;
        padding: 0.75rem 1.4rem;
        cursor: pointer;
        font-weight: 800;
        color: #64748b;
        position: relative;
        z-index: 2;
        transition: color 0.3s ease-in-out;
        gap: 8px;
    }

    .dark .glass-radio-group label {
        color: #94a3b8;
    }

    .glass-radio-group label:hover {
        color: #113f2b;
    }

    .dark .glass-radio-group label:hover {
        color: #fff;
    }

    .glass-radio-group input:checked+label {
        color: #fff !important;
    }

    .glass-glider {
        position: absolute;
        top: 0;
        bottom: 0;
        width: calc(100% / 5);
        border-radius: 1rem;
        z-index: 1;
        transition: transform 0.5s cubic-bezier(0.37, 1.95, 0.66, 0.56), background 0.4s ease-in-out, box-shadow 0.4s ease-in-out;
    }

    /* الألوان حسب الحالة */
    #rd-all:checked~.glass-glider {
        background: linear-gradient(135deg, #113f2b, #1a5c40);
        box-shadow: 0 0 18px rgba(17, 63, 43, 0.5);
    }

    #rd-pending:checked~.glass-glider {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 0 18px rgba(245, 158, 11, 0.5);
    }

    #rd-accepted:checked~.glass-glider {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        box-shadow: 0 0 18px rgba(59, 130, 246, 0.5);
    }

    #rd-delivered:checked~.glass-glider {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 0 18px rgba(16, 185, 129, 0.5);
    }

    #rd-rejected:checked~.glass-glider {
        background: linear-gradient(135deg, #f43f5e, #e11d48);
        box-shadow: 0 0 18px rgba(244, 63, 94, 0.5);
    }

    /* حركات السلايدر حسب اتجاه الصفحة */
    html[dir="rtl"] #rd-all:checked~.glass-glider {
        transform: translateX(0%);
    }

    html[dir="rtl"] #rd-pending:checked~.glass-glider {
        transform: translateX(-100%);
    }

    html[dir="rtl"] #rd-accepted:checked~.glass-glider {
        transform: translateX(-200%);
    }

    html[dir="rtl"] #rd-delivered:checked~.glass-glider {
        transform: translateX(-300%);
    }

    html[dir="rtl"] #rd-rejected:checked~.glass-glider {
        transform: translateX(-400%);
    }

    html[dir="ltr"] #rd-all:checked~.glass-glider {
        transform: translateX(0%);
    }

    html[dir="ltr"] #rd-pending:checked~.glass-glider {
        transform: translateX(100%);
    }

    html[dir="ltr"] #rd-accepted:checked~.glass-glider {
        transform: translateX(200%);
    }

    html[dir="ltr"] #rd-delivered:checked~.glass-glider {
        transform: translateX(300%);
    }

    html[dir="ltr"] #rd-rejected:checked~.glass-glider {
        transform: translateX(400%);
    }

    /* أنيميشن الجداول */
    #ordersTableBody {
        transition: opacity 0.2s ease;
    }

    #ordersTableBody.loading {
        opacity: 0.3;
        pointer-events: none;
    }

    .sub-order-row {
        transition: opacity 0.25s ease;
    }

    .sub-order-row.show {
        display: table-row !important;
    }
</style>

<main class="flex-1 p-8 bg-green-50 dark:bg-green-950 h-full overflow-y-auto transition-colors duration-300 relative">
    <?php include('../includes/topbar.php'); ?>

    <!-- ترويسة الصفحة والفلتر الزجاجي والبحث -->
    <div class="mb-10 flex flex-col xl:flex-row justify-between items-center gap-6">

        <!-- العنوان -->
        <div class="flex items-center gap-4 w-full xl:w-auto shrink-0">
            <div class="bg-[#113f2b] p-3 rounded-2xl shadow-lg">
                <i class="fa-solid fa-truck-fast text-[#d2f34c] text-2xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-black text-gray-900 dark:text-white"><?php echo isset($lang['orders']) ? $lang['orders'] : 'Orders'; ?></h1>
                <p class="text-sm text-gray-500 font-bold mt-1"><?php echo isset($lang['manage_orders']) ? $lang['manage_orders'] : 'Manage your orders'; ?></p>
            </div>
        </div>

        <!-- أدوات التحكم: مربع البحث + الفلتر الزجاجي -->
        <div class="flex flex-col md:flex-row items-center gap-4 w-full xl:w-auto">

            <!-- مربع البحث الآني (Zero-Lag) -->
            <div class="relative w-full md:w-64">
                <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="<?php echo isset($lang['search_product']) ? $lang['search_product'] : 'Search...'; ?>"
                    class="w-full py-2.5 px-4 <?php echo ($dir == 'rtl') ? 'pl-10' : 'pr-10'; ?> rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:outline-none focus:border-[#113f2b] dark:text-white shadow-sm focus:ring-2 focus:ring-[#113f2b]/20">
                <i class="fa-solid fa-magnifying-glass absolute <?php echo ($dir == 'rtl') ? 'left-3' : 'right-3'; ?> top-3 text-gray-400"></i>
            </div>

            <!-- الفلتر الزجاجي (Glass Filter) -->
            <div class="w-full md:w-auto overflow-x-auto custom-scrollbar pb-2 md:pb-0">
                <div class="glass-radio-group">
                    <input type="radio" name="order_filter" id="rd-all" onclick="filterOrders('All')" <?php echo $filter_status == 'All' ? 'checked' : ''; ?> />
                    <label for="rd-all"><i class="fa-solid fa-layer-group"></i> <?php echo isset($lang['filter_all']) ? $lang['filter_all'] : 'All'; ?></label>

                    <input type="radio" name="order_filter" id="rd-pending" onclick="filterOrders('Pending')" <?php echo $filter_status == 'Pending' ? 'checked' : ''; ?> />
                    <label for="rd-pending"><i class="fa-solid fa-bell"></i> <?php echo isset($lang['filter_pending']) ? $lang['filter_pending'] : 'Pending'; ?></label>

                    <input type="radio" name="order_filter" id="rd-accepted" onclick="filterOrders('Accepted')" <?php echo $filter_status == 'Accepted' ? 'checked' : ''; ?> />
                    <label for="rd-accepted"><i class="fa-solid fa-box-open"></i> <?php echo isset($lang['filter_processing']) ? $lang['filter_processing'] : 'Processing'; ?></label>

                    <input type="radio" name="order_filter" id="rd-delivered" onclick="filterOrders('Delivered')" <?php echo $filter_status == 'Delivered' ? 'checked' : ''; ?> />
                    <label for="rd-delivered"><i class="fa-solid fa-check-double"></i> <?php echo isset($lang['filter_delivered']) ? $lang['filter_delivered'] : 'Delivered'; ?></label>

                    <input type="radio" name="order_filter" id="rd-rejected" onclick="filterOrders('Rejected')" <?php echo $filter_status == 'Rejected' ? 'checked' : ''; ?> />
                    <label for="rd-rejected"><i class="fa-solid fa-ban"></i> <?php echo isset($lang['filter_rejected']) ? $lang['filter_rejected'] : 'Rejected'; ?></label>

                    <div class="glass-glider"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول الطلبات -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
            <thead class="bg-gray-50/50 dark:bg-slate-900/50 border-b border-gray-200 dark:border-slate-700">
                <tr class="text-gray-600 dark:text-gray-400">
                    <th class="px-6 py-4 font-bold"><?php echo isset($lang['order_id']) ? $lang['order_id'] : 'Order ID'; ?></th>
                    <th class="px-6 py-4 font-bold"><?php echo isset($lang['patient_name']) ? $lang['patient_name'] : 'Patient'; ?></th>
                    <th class="px-6 py-4 font-bold"><?php echo isset($lang['time']) ? $lang['time'] : 'Time'; ?></th>
                    <th class="px-6 py-4 font-bold"><?php echo isset($lang['status']) ? $lang['status'] : 'Status'; ?></th>
                    <th class="px-6 py-4 font-bold"><?php echo isset($lang['amount']) ? $lang['amount'] : 'Amount'; ?></th>
                    <th class="px-6 py-4 font-bold text-center"><?php echo isset($lang['actions']) ? $lang['actions'] : 'Actions'; ?></th>
                </tr>
            </thead>
            <tbody id="ordersTableBody" class="divide-y divide-gray-50 dark:divide-slate-700/50">
                <?php echo renderTableRows($grouped_orders, $order_items_data, $lang); ?>
            </tbody>
        </table>
    </div>
</main>

<!-- النافذة المنبثقة (Modal) للتفاصيل -->
<div id="orderModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden flex justify-center items-center transition-opacity p-4">
    <div class="bg-white dark:bg-slate-900 w-full md:max-w-4xl max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col transform transition-transform translate-y-full md:translate-y-0" id="modalContent">

        <!-- هيدر النافذة -->
        <div class="px-8 py-6 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center bg-gray-50 dark:bg-slate-800 shrink-0">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-[#113f2b] rounded-2xl text-[#d2f34c] shadow-md">
                    <i class="fa-solid fa-file-invoice-dollar text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2"><?php echo isset($lang['order_details']) ? $lang['order_details'] : 'Order Details'; ?> <span id="modalOrderId" class="text-[#113f2b] dark:text-[#d2f34c]" dir="ltr"></span></h2>
                    <p id="modalOrderDate" class="text-xs font-bold text-gray-400 mt-1"></p>
                </div>
            </div>
            <button onclick="closeOrderModal()" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-200 dark:bg-slate-700 text-gray-500 hover:text-rose-500 hover:bg-rose-50 transition-all">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- محتوى النافذة -->
        <div class="p-8 overflow-y-auto custom-scrollbar flex-1">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-2 space-y-6">
                    <h3 class="text-sm font-black text-gray-500 border-b border-gray-100 dark:border-slate-700 pb-2"><i class="fa-solid fa-pills"></i> <?php echo isset($lang['product']) ? $lang['product'] : 'Products'; ?></h3>
                    <div class="bg-gray-50 dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 overflow-hidden shadow-sm">
                        <table class="w-full text-sm <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                            <thead class="bg-white dark:bg-slate-900 border-b border-gray-100 dark:border-slate-700 text-gray-500">
                                <tr>
                                    <th class="py-3 px-4 font-bold"><?php echo isset($lang['product']) ? $lang['product'] : 'Product'; ?></th>
                                    <th class="py-3 px-4 font-bold text-center"><?php echo isset($lang['qty']) ? $lang['qty'] : 'Qty'; ?></th>
                                    <th class="py-3 px-4 font-bold <?php echo ($dir == 'rtl') ? 'text-left' : 'text-right'; ?>"><?php echo isset($lang['item_total']) ? $lang['item_total'] : 'Total'; ?></th>
                                </tr>
                            </thead>
                            <tbody id="modalItemsTable" class="divide-y divide-gray-100 dark:divide-slate-700/50 text-gray-800 dark:text-gray-200"></tbody>
                        </table>
                    </div>

                    <!-- الوصفة الطبية (إن وجدت) -->
                    <div id="prescriptionSection" class="hidden">
                        <div class="bg-rose-50 dark:bg-rose-900/20 rounded-2xl p-6 border border-rose-200 dark:border-rose-800 flex flex-col md:flex-row gap-6 items-center">
                            <a id="prescriptionImgLink" href="#" target="_blank" class="block relative rounded-xl border-4 border-white shadow-md overflow-hidden shrink-0">
                                <img id="prescriptionImg" src="" alt="Rx" class="w-32 h-32 object-cover">
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 hover:opacity-100 transition">
                                    <i class="fa-solid fa-magnifying-glass-plus text-white text-2xl"></i>
                                </div>
                            </a>
                            <div class="flex-1 space-y-3">
                                <h3 class="font-black text-rose-600 dark:text-rose-400 flex items-center gap-2"><i class="fa-solid fa-file-prescription"></i> <?php echo isset($lang['attached_rx']) ? $lang['attached_rx'] : 'Attached Prescription'; ?></h3>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="verifyPrescriptionCheck" class="w-5 h-5 text-rose-600 rounded">
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300"><?php echo isset($lang['rx_verify_check']) ? $lang['rx_verify_check'] : 'I verify this prescription'; ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- معلومات المريض -->
                <div class="space-y-6">
                    <div class="bg-gray-50 dark:bg-slate-800 rounded-2xl p-6 border border-gray-100 dark:border-slate-700 space-y-4 text-sm">
                        <h3 class="text-sm font-black text-gray-500 border-b border-gray-200 dark:border-slate-600 pb-2"><i class="fa-solid fa-address-card"></i> <?php echo isset($lang['customer_info']) ? $lang['customer_info'] : 'Customer Info'; ?></h3>
                        <div><span class="text-[10px] font-bold text-gray-400 block"><?php echo isset($lang['patient_name']) ? $lang['patient_name'] : 'Name'; ?></span><span id="modalPatientName" class="font-bold text-gray-800 dark:text-gray-200"></span></div>
                        <div><span class="text-[10px] font-bold text-gray-400 block"><?php echo isset($lang['phone']) ? $lang['phone'] : 'Phone'; ?></span><span id="modalPatientPhone" class="font-bold text-gray-800 dark:text-gray-200" dir="ltr"></span></div>
                        <div><span class="text-[10px] font-bold text-gray-400 block"><?php echo isset($lang['address']) ? $lang['address'] : 'Address'; ?></span><span id="modalPatientAddress" class="font-bold text-gray-700 dark:text-gray-300"></span></div>
                    </div>

                    <div class="bg-[#113f2b] rounded-2xl p-6 text-center shadow-lg relative overflow-hidden">
                        <i class="fa-solid fa-sack-dollar absolute -right-4 -bottom-4 text-6xl text-white opacity-10"></i>
                        <span class="text-[#d2f34c] text-xs font-bold block mb-1 relative z-10"><?php echo isset($lang['total_required']) ? $lang['total_required'] : 'Total to Pay'; ?></span>
                        <h2 id="modalTotalAmount" class="text-4xl font-black text-white relative z-10" dir="ltr"></h2>
                        <span class="bg-black/10 px-4 py-1 mt-2 inline-block rounded-xl text-[10px] font-bold text-white/80"><?php echo isset($lang['cod']) ? $lang['cod'] : 'COD'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 border-t border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-slate-800 flex justify-between items-center">
            <button onclick="closeOrderModal()" class="px-6 py-2.5 rounded-xl bg-white dark:bg-slate-700 text-gray-600 dark:text-gray-300 font-bold border border-gray-200 dark:border-slate-600 hover:bg-gray-100"><?php echo isset($lang['close']) ? $lang['close'] : 'Close'; ?></button>
            <div id="dynamicActionButtons" class="flex gap-2"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ==========================================
    // 💡 1. ترجمات JavaScript الأصلية
    // ==========================================
    const jsLang = {
        acceptTitle: "<?php echo isset($lang['swal_title']) ? addslashes($lang['swal_title']) : 'Are you sure?'; ?>",
        acceptText: "<?php echo isset($lang['status_processing']) ? addslashes($lang['status_processing']) : 'Start Processing?'; ?>",
        acceptBtn: "<?php echo isset($lang['accept_prepare']) ? addslashes($lang['accept_prepare']) : 'Accept'; ?>",
        rejectTitle: "<?php echo isset($lang['swal_title']) ? addslashes($lang['swal_title']) : 'Are you sure?'; ?>",
        rejectText: "<?php echo isset($lang['status_rejected']) ? addslashes($lang['status_rejected']) : 'Cancel Order?'; ?>",
        rejectBtn: "<?php echo isset($lang['filter_rejected']) ? addslashes($lang['filter_rejected']) : 'Reject'; ?>",
        deliverTitle: "<?php echo isset($lang['swal_title']) ? addslashes($lang['swal_title']) : 'Are you sure?'; ?>",
        deliverText: "<?php echo isset($lang['status_delivered']) ? addslashes($lang['status_delivered']) : 'Confirm Delivery?'; ?>",
        deliverBtn: "<?php echo isset($lang['confirm_delivery']) ? addslashes($lang['confirm_delivery']) : 'Deliver'; ?>",
        cancelBtn: "<?php echo isset($lang['swal_cancel']) ? addslashes($lang['swal_cancel']) : 'Cancel'; ?>",
        rxWarningTitle: "<?php echo isset($lang['rx_alert']) ? addslashes($lang['rx_alert']) : 'Warning'; ?>",
        rxWarningText: "<?php echo isset($lang['rx_verify_check']) ? addslashes($lang['rx_verify_check']) : 'Please verify prescription'; ?>",
        rxWarningBtn: "<?php echo isset($lang['ok_btn']) ? addslashes($lang['ok_btn']) : 'OK'; ?>",
        acceptPrepare: "<?php echo isset($lang['accept_prepare']) ? addslashes($lang['accept_prepare']) : 'Accept'; ?>",
        confirmDelivery: "<?php echo isset($lang['confirm_delivery']) ? addslashes($lang['confirm_delivery']) : 'Deliver'; ?>",
        rejectLabel: "<?php echo isset($lang['filter_rejected']) ? addslashes($lang['filter_rejected']) : 'Reject'; ?>"
    };

    // ==========================================
    // 💡 2. دالة البحث الآني (Zero-Lag Search)
    // ==========================================
    function searchTable() {
        const input = document.getElementById("searchInput").value.toLowerCase();
        const rows = document.querySelectorAll("tr[data-searchable]");

        rows.forEach(row => {
            const searchableText = row.getAttribute("data-searchable");
            if (searchableText.includes(input)) {
                row.classList.remove("hidden");
                if (row.classList.contains('sub-order-row') && openPatients.has(row.getAttribute('data-parent'))) {
                    row.classList.add("show");
                }
            } else {
                row.classList.add("hidden");
                row.classList.remove("show");
            }
        });
    }

    // ==========================================
    // 3. تجميع وعرض الطلبات (Group Toggling)
    // ==========================================
    const openPatients = new Set();

    function togglePatientOrders(uid) {
        const rows = document.querySelectorAll(`.sub-order-row[data-parent="${uid}"]`);
        const arrow = document.getElementById('arrow_' + uid);
        const isOpen = openPatients.has(uid);
        const currentSearch = document.getElementById("searchInput").value.toLowerCase();

        if (isOpen) {
            rows.forEach(r => {
                r.classList.remove('show');
                r.classList.add('hidden');
            });
            if (arrow) arrow.style.transform = '';
            openPatients.delete(uid);
        } else {
            rows.forEach(r => {
                const text = r.getAttribute('data-searchable');
                if (text.includes(currentSearch)) {
                    r.classList.remove('hidden');
                    r.classList.add('show');
                }
            });
            if (arrow) arrow.style.transform = 'rotate(-90deg)';
            openPatients.add(uid);
        }
    }

    // ==========================================
    // 4. فلتر الأزرار الزجاجية (AJAX)
    // ==========================================
    let currentFilter = '<?php echo $filter_status; ?>';

    function filterOrders(status) {
        if (status === currentFilter) return;
        currentFilter = status;
        history.pushState(null, '', '?status=' + status);

        const tbody = document.getElementById('ordersTableBody');
        tbody.classList.add('loading');
        openPatients.clear();

        fetch('orders.php?ajax=1&status=' + status)
            .then(res => res.text())
            .then(html => {
                tbody.innerHTML = html;
                tbody.classList.remove('loading');
                searchTable();
            });
    }

    // ==========================================
    // 5. أزرار التحكم والنافذة المنبثقة
    // ==========================================
    let currentOrderData = null;
    const currency = "<?php echo isset($lang['currency']) ? $lang['currency'] : '₪'; ?>";

    function confirmOrderStatus(orderId, action) {
        let t = '',
            txt = '',
            b = '',
            c = '#113f2b',
            i = 'question';
        if (action === 'Accepted') {
            t = jsLang.acceptTitle;
            txt = jsLang.acceptText;
            b = jsLang.acceptBtn;
        } else if (action === 'Rejected') {
            t = jsLang.rejectTitle;
            txt = jsLang.rejectText;
            b = jsLang.rejectBtn;
            c = '#f43f5e';
            i = 'warning';
        } else if (action === 'Delivered') {
            t = jsLang.deliverTitle;
            txt = jsLang.deliverText;
            b = jsLang.deliverBtn;
        }

        Swal.fire({
            title: t,
            text: txt,
            icon: i,
            showCancelButton: true,
            confirmButtonColor: c,
            cancelButtonColor: '#94a3b8',
            confirmButtonText: b,
            cancelButtonText: jsLang.cancelBtn,
            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
        }).then((r) => {
            if (r.isConfirmed) {
                fetch(`orders.php?ajax=1&action=${action}&order_id=${orderId}`)
                    .then(() => {
                        closeOrderModal();
                        filterOrders(currentFilter);
                        currentFilter = '';
                        filterOrders(document.querySelector('input[name="order_filter"]:checked').value);
                    });
            }
        });
    }

    function viewOrderDetails(jsonString) {
        const order = JSON.parse(jsonString);
        currentOrderData = order;

        document.getElementById('modalOrderId').innerText = `#ORD-${order.id}`;
        document.getElementById('modalOrderDate').innerText = order.date;
        document.getElementById('modalPatientName').innerText = order.patient;
        document.getElementById('modalPatientPhone').innerText = order.phone || 'N/A';
        document.getElementById('modalPatientAddress').innerText = order.address;
        document.getElementById('modalTotalAmount').innerText = parseFloat(order.total).toFixed(2) + ' ' + currency;

        const tbody = document.getElementById('modalItemsTable');
        tbody.innerHTML = '';
        order.items.forEach(item => {
            const rx = item.IsControlled == 1 ? '<span class="mx-2 bg-rose-100 text-rose-700 text-[10px] px-2 py-0.5 rounded font-bold">Rx</span>' : '';
            tbody.innerHTML += `
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-800 transition border-b border-gray-50 dark:border-slate-700">
                    <td class="py-3 px-4 font-bold text-gray-800 dark:text-gray-200">${item.Name}${rx}</td>
                    <td class="py-3 px-4 text-center font-bold text-gray-500"><span class="bg-gray-100 dark:bg-slate-700 px-3 py-1 rounded">x${item.Quantity}</span></td>
                    <td class="py-3 px-4 font-black text-[#113f2b] dark:text-[#d2f34c] <?php echo ($dir == 'rtl') ? 'text-left' : 'text-right'; ?>" dir="ltr">${parseFloat(item.Quantity * item.SoldPrice).toFixed(2)} ${currency}</td>
                </tr>`;
        });

        const rxSec = document.getElementById('prescriptionSection');
        const vCh = document.getElementById('verifyPrescriptionCheck');
        if (order.has_controlled) {
            rxSec.classList.remove('hidden');
            vCh.checked = false;
            document.getElementById('prescriptionImg').src = order.prescription ? `../uploads/${order.prescription}` : 'https://placehold.co/400x400/ffe4e6/be123c?text=No+Rx';
            document.getElementById('prescriptionImgLink').href = order.prescription ? `../uploads/${order.prescription}` : '#';
        } else {
            rxSec.classList.add('hidden');
            vCh.checked = true;
        }

        const btnContainer = document.getElementById('dynamicActionButtons');
        btnContainer.innerHTML = '';
        if (order.status === 'Pending') {
            btnContainer.innerHTML = `
                <button onclick="confirmOrderStatus(${order.id}, 'Rejected')" class="px-6 py-2.5 rounded-xl bg-rose-50 text-rose-600 font-bold hover:bg-rose-100 transition"><i class="fa-solid fa-ban"></i> ${jsLang.rejectLabel}</button>
                <button onclick="attemptAcceptOrder()" class="bg-[#113f2b] hover:bg-[#0a271a] text-[#d2f34c] px-8 py-2.5 rounded-xl font-bold flex items-center gap-2 transition"><i class="fa-solid fa-check"></i> ${jsLang.acceptPrepare}</button>
            `;
        } else if (order.status === 'Accepted') {
            btnContainer.innerHTML = `
                <button onclick="confirmOrderStatus(${order.id}, 'Delivered')" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-xl font-bold flex items-center gap-2 transition"><i class="fa-solid fa-motorcycle"></i> ${jsLang.confirmDelivery}</button>
            `;
        }

        const m = document.getElementById('orderModal');
        const c = document.getElementById('modalContent');
        m.classList.remove('hidden');
        setTimeout(() => c.classList.remove('translate-y-full'), 10);
    }

    function closeOrderModal() {
        const c = document.getElementById('modalContent');
        c.classList.add('translate-y-full');
        setTimeout(() => document.getElementById('orderModal').classList.add('hidden'), 300);
    }

    function attemptAcceptOrder() {
        if (currentOrderData.has_controlled && !document.getElementById('verifyPrescriptionCheck').checked) {
            Swal.fire({
                icon: 'error',
                title: jsLang.rxWarningTitle,
                text: jsLang.rxWarningText,
                confirmButtonText: jsLang.rxWarningBtn,
                confirmButtonColor: '#f43f5e',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
            });
            return;
        }
        confirmOrderStatus(currentOrderData.id, 'Accepted');
    }
</script>

<?php include('../includes/footer.php'); ?>