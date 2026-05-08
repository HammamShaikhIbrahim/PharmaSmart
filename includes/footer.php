<?php
// تحديد اللون المناسب بناءً على دور المستخدم (أزرق للمدير، أخضر للصيدلي)
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 0;
$accentColor = ($role_id == 1) ? '#048AC1' : '#0A7A48';
$year = date('Y');
?>

</div> <!-- إغلاق الحاوية الرئيسية (Flex Container) التي تم فتحها في header.php -->

<!-- ========================================== -->
<!-- 🚀 سكربت إضافة الفوتر البصري بطريقة آمنة 100% -->
<!-- ========================================== -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. البحث عن حاوية الـ main في الصفحة الحالية
        const mainElement = document.querySelector('main');
        
        // 2. إذا وجدها، يقوم بزرع الفوتر في نهايتها من الداخل
        if (mainElement) {
            const footerHTML = `
                <div class="mt-auto pt-10 pb-4 w-full relative z-10 opacity-70 hover:opacity-100 transition-opacity duration-300">
                    <div class="border-t border-gray-200/60 dark:border-slate-700/60 pt-6 flex flex-col md:flex-row items-center justify-between gap-4 px-2">
                        
                        <!-- حقوق النشر -->
                        <div class="text-sm font-bold text-gray-500 dark:text-gray-400">
                            &copy; <?php echo $year; ?> 
                            <span style="color: <?php echo $accentColor; ?>" class="font-black tracking-wide">PharmaSmart</span>. 
                            جميع الحقوق محفوظة.
                        </div>

                        <!-- لمسة جمالية (صنع بحب) -->
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-500 dark:text-gray-400 bg-white/50 dark:bg-slate-800/50 backdrop-blur-md px-4 py-2 rounded-full border border-gray-200/50 dark:border-slate-700/50 shadow-sm transition-all hover:shadow-md hover:-translate-y-0.5">
                            <span>صُنع بـ</span>
                            <svg class="w-3.5 h-3.5 text-rose-500 fill-rose-500 animate-pulse" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                            </svg>
                            <span>لتطوير الرعاية الصحية</span>
                            <span class="mx-1 opacity-30">|</span>
                            <span style="color: <?php echo $accentColor; ?>" class="font-black tracking-wider">v1.0</span>
                        </div>

                    </div>
                </div>
            `;
            // إضافة الكود لنهاية الـ main بدون المساس بمحتواه
            mainElement.insertAdjacentHTML('beforeend', footerHTML);
        }
        
        // 3. تفعيل أيقونات النظام في حال لم يتم تفعيلها
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

</body>
</html>