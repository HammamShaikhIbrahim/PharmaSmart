<?php
include('../config/database.php');
session_start();
require_once('../includes/lang.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../auth/login.php");
    exit();
}

$pharmacist_id = $_SESSION['user_id'];

// ==========================================
// 1. الإحصائيات السريعة (Quick Stats)
// ==========================================
$salesQuery = "SELECT SUM(oi.Quantity * oi.SoldPrice) as TotalSales FROM OrderItems oi JOIN Medicine m ON oi.MedicineID = m.MedicineID JOIN `Order` o ON oi.OrderID = o.OrderID WHERE m.PharmacistID = $pharmacist_id AND DATE(o.OrderDate) = CURDATE() AND o.Status != 'Rejected'";
$salesResult = mysqli_fetch_assoc(mysqli_query($conn, $salesQuery));
$todaysSales = $salesResult['TotalSales'] ? number_format($salesResult['TotalSales'], 2) : "0.00";

$ordersQuery = "SELECT COUNT(DISTINCT o.OrderID) as PendingCount FROM `Order` o JOIN OrderItems oi ON o.OrderID = oi.OrderID JOIN Medicine m ON oi.MedicineID = m.MedicineID WHERE m.PharmacistID = $pharmacist_id AND o.Status = 'Pending'";
$pendingOrders = mysqli_fetch_assoc(mysqli_query($conn, $ordersQuery))['PendingCount'];

$lowStockQuery = "SELECT COUNT(*) as LowStockCount FROM Medicine WHERE PharmacistID = $pharmacist_id AND Stock <= MinimumStock";
$lowStockCount = mysqli_fetch_assoc(mysqli_query($conn, $lowStockQuery))['LowStockCount'];

$expiryQuery = "SELECT COUNT(*) as ExpiringCount FROM Medicine WHERE PharmacistID = $pharmacist_id AND ExpiryDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
$expiringCount = mysqli_fetch_assoc(mysqli_query($conn, $expiryQuery))['ExpiringCount'];

// ==========================================
// 2. جلب الطلبات الأخيرة (الجدول العريض)
// ==========================================
$recentOrdersQ = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.Status, o.DeliveryAddress, o.PaymentMethod, u.Fname, u.Lname, u.Phone, COUNT(oi.MedicineID) as ItemsCount 
                  FROM `Order` o 
                  JOIN User u ON o.PatientID = u.UserID 
                  JOIN OrderItems oi ON o.OrderID = oi.OrderID 
                  JOIN Medicine m ON oi.MedicineID = m.MedicineID 
                  WHERE m.PharmacistID = $pharmacist_id 
                  GROUP BY o.OrderID 
                  ORDER BY o.OrderDate DESC LIMIT 5";
$recentOrdersResult = mysqli_query($conn, $recentOrdersQ);

// ==========================================
// 🚀 3. جلب قائمة الطلبات المعلقة للقائمة المنسدلة (الكرت المضغوط)
// ==========================================
$pendingListQ = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, u.Fname, u.Lname 
                 FROM `Order` o 
                 JOIN User u ON o.PatientID = u.UserID 
                 JOIN OrderItems oi ON o.OrderID = oi.OrderID 
                 JOIN Medicine m ON oi.MedicineID = m.MedicineID 
                 WHERE m.PharmacistID = $pharmacist_id AND o.Status = 'Pending' 
                 GROUP BY o.OrderID 
                 ORDER BY o.OrderDate ASC"; // الأقدم أولاً لأنه أحق بالموافقة
$pendingListResult = mysqli_query($conn, $pendingListQ);

// ==========================================
// 4. قوائم التنبيهات (نواقص + صلاحية)
// ==========================================
$lowStockListQ = "SELECT Name, Stock, MinimumStock FROM Medicine WHERE PharmacistID = $pharmacist_id AND Stock <= MinimumStock ORDER BY Stock ASC LIMIT 5";
$lowStockListResult = mysqli_query($conn, $lowStockListQ);

$expiringListQ = "SELECT Name, ExpiryDate, DATEDIFF(ExpiryDate, CURDATE()) as DaysLeft 
                  FROM Medicine 
                  WHERE PharmacistID = $pharmacist_id 
                  AND ExpiryDate <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                  ORDER BY ExpiryDate ASC LIMIT 5";
$expiringListResult = mysqli_query($conn, $expiringListQ);

include('../includes/header.php');
include('../includes/sidebar.php');
?>
<main class="flex-1 p-8 bg-[#F2FBF5] dark:bg-slate-900 h-full overflow-y-auto transition-colors duration-300">
    <?php include('../includes/topbar.php'); ?>

    <!-- ==========================================
         رأس الصفحة (نقلنا التاريخ هنا ليكون بادج أنيق)
    =========================================== -->
    <div class="mb-8 flex justify-between items-center">

        <div class="flex items-center gap-4">
            <div class="p-2.5 ">
                <i data-lucide="layout-dashboard" class="text-[#0A7A48] dark:text-[#4ADE80] w-7 h-7"></i>
            </div>
            <h1 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $lang['dashboard']; ?></h1>
        </div>

        <!-- 🚀 بادج التاريخ (باللون الملاحظ لثيم الصيدلية) -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 px-5 py-3 rounded-2xl shadow-sm flex items-center gap-3 transition-colors ">
            <i data-lucide="calendar-days" class="text-[#0A7A48] dark:text-[#4ADE80] w-5 h-5"></i>
            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 tracking-wide" dir="ltr"><?php echo date('d M, Y'); ?></span>
        </div>

    </div>

    <!-- ==========================================
         1. شريط الإحصائيات (كروت موزعة بشكل 2 فقط)
    =========================================== -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

        <!-- كرت مبيعات اليوم -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-gray-200 dark:border-slate-700 shadow-sm flex items-center justify-between transition-all hover:shadow-md hover:border-[#0A7A48] dark:hover:border-[#0A7A48]">
            <div class="flex items-center gap-4">
                <div class="p-4 bg-[#E6F7ED] dark:bg-[#044E29]/40 rounded-2xl border border-transparent dark:border-[#0A7A48]/30 shrink-0">
                    <i data-lucide="banknote" class="w-8 h-8 text-[#0A7A48] dark:text-[#4ADE80]"></i>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-bold mb-1"><?php echo $lang['todays_sales']; ?></p>
                    <h3 class="text-3xl font-black text-gray-800 dark:text-white" dir="ltr"><?php echo $todaysSales; ?> ₪</h3>
                </div>
            </div>
        </div>
        <!-- 🚀 كرت الطلبات قيد الانتظار (مضغوط وقابل للفتح كقائمة منسدلة) -->
        <div class="relative">
            <!-- الزر الذي يفتح القائمة -->
            <button onclick="togglePendingOrders()" class="w-full bg-white dark:bg-slate-800 rounded-3xl p-6 border border-gray-200 dark:border-slate-700 shadow-sm flex items-center justify-between transition-all hover:shadow-md hover:border-amber-500 focus:outline-none text-right group">
                <div class="flex items-center gap-4">
                    <!-- 💡 تغيير اللون إلى Amber ليتطابق مع صفحة الطلبات -->
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-transparent dark:border-amber-500/30 shrink-0 transition-colors group-hover:bg-amber-100 dark:group-hover:bg-amber-900/40">
                        <i data-lucide="clock" class="w-8 h-8 text-amber-500"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 text-sm font-bold mb-1"><?php echo $lang['pending_orders']; ?></p>
                        <h3 class="text-3xl font-black text-gray-800 dark:text-white"><?php echo $pendingOrders; ?></h3>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <?php if ($pendingOrders > 0): ?>
                        <!-- 💡 توحيد اللون الكهرماني (Amber) للطلبات المعلقة -->
                        <span class="bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800 px-2.5 py-1 rounded-full text-xs font-bold flex items-center gap-1.5 shadow-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            جديد: <?php echo $pendingOrders; ?>
                        </span>
                    <?php endif; ?>
                    <div class="p-2 bg-gray-50 dark:bg-slate-700 rounded-xl transition-colors group-hover:bg-gray-100 dark:group-hover:bg-slate-600">
                        <i data-lucide="chevron-down" id="pendingChevron" class="w-5 h-5 text-gray-500 transition-transform duration-300"></i>
                    </div>
                </div>
            </button>

            <!-- القائمة المنسدلة للطلبات المعلقة -->
            <div id="pendingOrdersList" class="absolute top-[calc(100%+0.5rem)] w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-3xl shadow-xl z-20 overflow-hidden origin-top scale-y-0 opacity-0 transition-all duration-300 pointer-events-none">
                <div class="p-4 bg-amber-50/50 dark:bg-amber-900/10 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center">
                    <span class="text-sm font-black text-amber-600 dark:text-amber-400">تحتاج موافقتك!</span>
                    <!-- 💡 توجيه الصيدلاني لصفحة الطلبات مفلترة على "قيد الانتظار" مباشرة -->
                    <a href="orders.php?status=Pending" class="text-xs font-bold text-gray-500 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">عرض الكل</a>
                </div>
                <div class="max-h-[300px] overflow-y-auto custom-scrollbar p-3 space-y-2">
                    <?php if (mysqli_num_rows($pendingListResult) > 0): ?>
                        <?php while ($p_order = mysqli_fetch_assoc($pendingListResult)): ?>
                            <a href="orders.php?status=Pending" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-slate-700/30 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-2xl transition-colors group">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center shadow-sm border border-gray-200 dark:border-slate-600 group-hover:border-amber-200 dark:group-hover:border-amber-700 transition-colors">
                                        <i data-lucide="user" class="w-4 h-4 text-gray-400 group-hover:text-amber-500 transition-colors"></i>
                                    </div>
                                    <div>
                                        <div class="font-black text-sm text-gray-800 dark:text-white mb-0.5" dir="ltr">#ORD-<?php echo $p_order['OrderID']; ?></div>
                                        <div class="text-[11px] font-bold text-gray-500"><?php echo htmlspecialchars($p_order['Fname'] . ' ' . $p_order['Lname']); ?></div>
                                    </div>
                                </div>
                                <div class="font-black text-amber-600 dark:text-amber-400 bg-white dark:bg-slate-800 px-3 py-1.5 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700" dir="ltr">
                                    <?php echo number_format($p_order['TotalAmount'], 2); ?> ₪
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-8 text-center flex flex-col items-center gap-3">
                            <div class="p-4 bg-[#E6F7ED] dark:bg-[#044E29]/30 rounded-full">
                                <i data-lucide="check-double" class="w-8 h-8 text-[#0A7A48] dark:text-[#4ADE80]"></i>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 font-bold">لا توجد طلبات قيد الانتظار حالياً.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         2. شاشة العمليات: جدول أحدث الطلبات
    =========================================== -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden flex flex-col mb-8">
        <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center bg-gray-50/50 dark:bg-slate-800/50">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/40 rounded-lg">
                    <i data-lucide="shopping-bag" class="text-blue-600 dark:text-blue-400 w-5 h-5"></i>
                </div>
                <h3 class="text-lg font-black text-gray-800 dark:text-white"><?php echo $lang['recent_orders']; ?></h3>
            </div>
            <a href="orders.php" class="text-sm bg-[#E6F7ED] dark:bg-[#044E29]/40 text-[#0A7A48] dark:text-[#4ADE80] px-4 py-2 rounded-xl hover:bg-[#0A7A48] hover:text-white dark:hover:bg-[#4ADE80] dark:hover:text-[#012314] font-bold transition-colors">
                <?php echo $lang['view_all']; ?>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-transparent border-b border-gray-100 dark:border-slate-700/50">
                    <tr class="text-gray-500 dark:text-gray-400 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                        <th class="p-5 font-bold">الطلب / الوقت</th>
                        <th class="p-5 font-bold">المريض / الاتصال</th>
                        <th class="p-5 font-bold">مكان التوصيل</th>
                        <th class="p-5 font-bold text-center">الأصناف</th>
                        <th class="p-5 font-bold">الإجمالي / الدفع</th>
                        <th class="p-5 font-bold text-center">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50 <?php echo ($dir == 'rtl') ? 'text-right' : 'text-left'; ?>">
                    <?php if (mysqli_num_rows($recentOrdersResult) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($recentOrdersResult)):
                            // 💡 مطابقة تامة لـ UI/UX الخاص بصفحة orders.php
                            $statusColor = 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-gray-300';
                            $statusIcon = 'circle';

                            if ($order['Status'] == 'Pending') {
                                $statusColor = 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:border-amber-800 dark:text-amber-400';
                                $statusIcon = 'clock';
                            }
                            if ($order['Status'] == 'Accepted') {
                                $statusColor = 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/40 dark:border-blue-800 dark:text-blue-400';
                                $statusIcon = 'package';
                            }
                            if ($order['Status'] == 'Delivered') {
                                $statusColor = 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:border-emerald-800 dark:text-emerald-400';
                                $statusIcon = 'check-circle';
                            }
                            if ($order['Status'] == 'Rejected') {
                                $statusColor = 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/40 dark:border-rose-800 dark:text-rose-400';
                                $statusIcon = 'x-circle';
                            }
                        ?>
                            <tr class="hover:bg-[#F2FBF5] dark:hover:bg-[#044E29]/20 transition-colors duration-200 group">
                                <td class="p-5">
                                    <div class="font-black text-gray-800 dark:text-white mb-1" dir="ltr">#ORD-<?php echo $order['OrderID']; ?></div>
                                    <div class="text-xs text-gray-500 font-bold flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> <?php echo date('h:i A', strtotime($order['OrderDate'])); ?></div>
                                </td>
                                <td class="p-5">
                                    <div class="font-bold text-gray-800 dark:text-white mb-1"><?php echo htmlspecialchars($order['Fname'] . ' ' . $order['Lname']); ?></div>
                                    <div class="text-xs text-gray-500 font-bold flex items-center gap-1" dir="ltr"><i data-lucide="phone" class="w-3 h-3"></i> <?php echo htmlspecialchars($order['Phone'] ?? 'لا يوجد رقم'); ?></div>
                                </td>
                                <td class="p-5">
                                    <div class="flex items-start gap-2 text-gray-600 dark:text-gray-300">
                                        <div class="p-1.5 bg-gray-100 dark:bg-slate-700 rounded text-gray-400 mt-0.5 shrink-0"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i></div>
                                        <span class="max-w-[200px] leading-relaxed font-medium"><?php echo htmlspecialchars($order['DeliveryAddress'] ?? 'الاستلام من الصيدلية'); ?></span>
                                    </div>
                                </td>
                                <td class="p-5 text-center"><span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 font-black text-xs"><?php echo $order['ItemsCount']; ?></span></td>
                                <td class="p-5">
                                    <div class="font-black text-[#0A7A48] dark:text-[#4ADE80] text-base mb-1" dir="ltr"><?php echo number_format($order['TotalAmount'], 2); ?> ₪</div>
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider"><?php echo $order['PaymentMethod'] == 'COD' ? 'الدفع عند الاستلام' : 'بطاقة ائتمان'; ?></div>
                                </td>
                                <td class="p-5 text-center">
                                    <!-- 💡 تمت إضافة كلاس border هنا لكي تتفاعل حدود البادج مع الألوان التي برمجناها -->
                                    <span class="border <?php echo $statusColor; ?> px-3 py-2 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 shadow-sm min-w-[100px]">
                                        <i data-lucide="<?php echo $statusIcon; ?>" class="w-3.5 h-3.5 <?php echo ($order['Status'] == 'Accepted') ? 'animate-spin' : ''; ?>"></i>
                                        <?php echo $order['Status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-16 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400"><i data-lucide="inbox" class="w-12 h-12 mb-3 opacity-50"></i>
                                    <p class="font-bold">لا يوجد طلبات حديثة حالياً.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==========================================
         3. مركز التنبيهات: النواقص والصلاحية
    =========================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- نواقص المخزون -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-rose-100 dark:border-rose-900/20 flex flex-col overflow-hidden relative">
            <div class="absolute top-0 right-0 w-2 h-full bg-rose-500"></div>
            <div class="p-5 border-b border-gray-50 dark:border-slate-700/50 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-rose-100 dark:bg-rose-900/40 rounded-lg text-rose-600 dark:text-rose-400"><i data-lucide="package-minus" class="w-5 h-5"></i></div>
                    <h3 class="text-base font-black text-gray-800 dark:text-white"><?php echo $lang['low_stock_alert']; ?></h3>
                </div>
                <?php if ($lowStockCount > 0): ?><span class="bg-rose-50 text-rose-600 border border-rose-200 dark:bg-rose-900/30 dark:border-rose-800/50 dark:text-rose-400 px-3 py-1 rounded-full text-xs font-bold"><?php echo $lowStockCount; ?> عناصر</span><?php endif; ?>
            </div>
            <div class="p-2 flex-1">
                <?php if (mysqli_num_rows($lowStockListResult) > 0): ?>
                    <ul class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        <?php while ($item = mysqli_fetch_assoc($lowStockListResult)): ?>
                            <li class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-slate-700/30 rounded-xl transition-colors">
                                <span class="font-bold text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($item['Name']); ?></span>
                                <div class="flex items-center gap-2"><span class="text-xs font-bold text-gray-400">الكمية:</span><span class="bg-rose-500 text-white px-2 py-0.5 rounded text-xs font-black min-w-[24px] text-center" dir="ltr"><?php echo $item['Stock']; ?></span></div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center p-8 text-[#0A7A48] dark:text-[#4ADE80]"><i data-lucide="check-circle" class="w-8 h-8 mb-2 opacity-80"></i>
                        <p class="font-bold text-sm">المخزون ممتاز</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- تواريخ الصلاحية -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-orange-100 dark:border-orange-900/20 flex flex-col overflow-hidden relative">
            <div class="absolute top-0 right-0 w-2 h-full bg-orange-400"></div>
            <div class="p-5 border-b border-gray-50 dark:border-slate-700/50 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-orange-100 dark:bg-orange-900/40 rounded-lg text-orange-600 dark:text-orange-400"><i data-lucide="calendar-off" class="w-5 h-5"></i></div>
                    <h3 class="text-base font-black text-gray-800 dark:text-white">تنبيهات الصلاحية</h3>
                </div>
                <?php if ($expiringCount > 0): ?><span class="bg-orange-50 text-orange-600 border border-orange-200 dark:bg-orange-900/30 dark:border-orange-800/50 dark:text-orange-400 px-3 py-1 rounded-full text-xs font-bold"><?php echo $expiringCount; ?> عناصر</span><?php endif; ?>
            </div>
            <div class="p-2 flex-1">
                <?php if (mysqli_num_rows($expiringListResult) > 0): ?>
                    <ul class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        <?php while ($exp = mysqli_fetch_assoc($expiringListResult)):
                            $isExpired = ($exp['DaysLeft'] < 0);
                            $textColor = $isExpired ? 'text-rose-500' : 'text-orange-500';
                            $badgeText = $isExpired ? "منتهي!" : "باقي " . $exp['DaysLeft'] . " يوم";
                        ?>
                            <li class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-slate-700/30 rounded-xl transition-colors">
                                <span class="font-bold text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($exp['Name']); ?></span>
                                <div class="flex items-center gap-3"><span class="text-xs font-bold text-gray-400" dir="ltr"><?php echo $exp['ExpiryDate']; ?></span><span class="bg-gray-100 dark:bg-slate-700 <?php echo $textColor; ?> px-2 py-0.5 rounded text-xs font-black min-w-[60px] text-center border border-gray-200 dark:border-slate-600"><?php echo $badgeText; ?></span></div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center p-8 text-[#0A7A48] dark:text-[#4ADE80]"><i data-lucide="check-circle" class="w-8 h-8 mb-2 opacity-80"></i>
                        <p class="font-bold text-sm">جميع الأدوية صالحة تماماً</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        lucide.createIcons();
    });
    document.addEventListener("DOMContentLoaded", function() {
        lucide.createIcons();
    });

    // 💡 دالة لفتح/إغلاق كرت الطلبات المعلقة
    function togglePendingOrders() {
        const list = document.getElementById('pendingOrdersList');
        const chevron = document.getElementById('pendingChevron');

        if (list.classList.contains('scale-y-0')) {
            // فتح
            list.classList.remove('scale-y-0', 'opacity-0', 'pointer-events-none');
            list.classList.add('scale-y-100', 'opacity-100');
            chevron.style.transform = 'rotate(180deg)';
        } else {
            // إغلاق
            list.classList.remove('scale-y-100', 'opacity-100');
            list.classList.add('scale-y-0', 'opacity-0', 'pointer-events-none');
            chevron.style.transform = 'rotate(0deg)';
        }
    }

    // 💡 إغلاق القائمة تلقائياً عند الضغط في أي مكان فارغ بالشاشة
    document.addEventListener('click', function(event) {
        const list = document.getElementById('pendingOrdersList');
        if (!list) return;

        const button = list.previousElementSibling;
        if (!button.contains(event.target) && !list.contains(event.target)) {
            list.classList.remove('scale-y-100', 'opacity-100');
            list.classList.add('scale-y-0', 'opacity-0', 'pointer-events-none');
            document.getElementById('pendingChevron').style.transform = 'rotate(0deg)';
        }
    });
</script>

<?php include('../includes/footer.php'); ?>