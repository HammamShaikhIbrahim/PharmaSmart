<?php
// ========================================================================
// إعدادات المتغيرات الأساسية
// ========================================================================

// 1. بنجيب السنة الحالية عشان تنطبع تلقائيا تحت عند حقوق النشر
$year = date('Y');

// 2. بنشوف مين اللي فاتح النظام حاليا (هل هو أدمن ولا صيدلاني؟)
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 0;

// 3. بنحدد اللون الأساسي للفوتر بناء على المستخدم:
// إذا كان الأدمن (رقم 1) بياخذ اللون الأزرق، وغير هيك بياخذ اللون الأخضر
$accentColor = ($role_id == 1) ? '#048AC1' : '#0A7A48';
?>

</div> <!-- انتبه: هاي التسكيرة مهمة جدا لأنها بتسكر حاوية جاية من ملف الهيدر فوق -->

<!-- ======================================================================== -->
<!-- بداية كود الفوتر -->
<!-- ======================================================================== -->
<script>
    // لا تنفذ الكود اللي تحت إلا لما الصفحة كلها تخلص تحميل عشان ما يصير أخطاء
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. بنمسك العنصر الرئيسي في الصفحة اللي اسمه main
        const mainElement = document.querySelector('main');
        
        // 2. بناخذ اللون اللي حددناه فوق بالبي اتش بي 
        const roleColor = "<?php echo $accentColor; ?>";
        
        // 3. إذا لقينا عنصر الـ main في الصفحة، بنبلش نشتغل
        if (mainElement) {
            
            // بنعطي الـ main خصائص الفليكس عشان نقدر ندفش الفوتر لآخر الشاشة تحت دائما
            mainElement.classList.add('flex', 'flex-col');

            // 4. بنبني شكل الفوتر ونخزنه جوا متغير
            const footerHTML = `
                <style>
                    /* -------------------------------------------
                       تنسيق الصندوق الكبير تبع الفوتر 
                    ------------------------------------------- */
                    .premium-footer {
                        margin-top: auto; /* بيدفش الفوتر لأسفل الشاشة دائما */
                        margin-left: -2rem; /* بنلغي المسافات الجانبية */
                        margin-right: -2rem;
                        margin-bottom: -2rem;
                        background-color: transparent; /* الفوتر شفاف */
                        position: relative;
                        z-index: 50; /* بنرفعه فوق باقي العناصر */
                    }

                    /* -------------------------------------------
                       تنسيق الخط الفاصل اللي فوق الفوتر (اللي بيلمع)
                    ------------------------------------------- */
                    .fading-border {
                        height: 2px;
                        width: 100%;
                        background: linear-gradient(90deg, transparent 0%, ${roleColor} 50%, transparent 100%);
                        opacity: 0.3;
                        transition: opacity 0.3s ease;
                    }
                    /* لما المستخدم يشغل الوضع الليلي، بنخفف لمعة الخط أكثر */
                    .dark .fading-border { opacity: 0.15; }

                    /* -------------------------------------------
                       تنسيق المساحة اللي جواتها محتوى الفوتر (الكلام والأزرار)
                    ------------------------------------------- */
                    .footer-content {
                        padding: 2rem 3rem;
                        display: flex;
                        align-items: center;
                        justify-content: space-between; /* توزيع العناصر بالتساوي */
                        gap: 1.5rem;
                    }

                    /* للشاشات الصغيرة (موبايل) رتب العناصر فوق بعض */
                    @media (max-width: 768px) {
                        .footer-content {
                            flex-direction: column;
                            text-align: center;
                            padding: 2rem 1rem;
                        }
                    }

                    /* -------------------------------------------
                       تنسيق أزرار جيت هب ولينكد إن
                    ------------------------------------------- */
                    .social-icons-group {
                        display: flex;
                        gap: 12px;
                        direction: ltr; /* بنجبر الأزرار تترتب من اليسار لليمين دائما */
                    }
                    
                    /* شكل الزر الواحد (دائرة رمادية) */
                    .social-btn {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        background-color: #f1f5f9;
                        color: #64748b;
                        transition: all 0.3s;
                        border: 1px solid transparent;
                    }
                    
                    /* شكل الدائرة بالوضع الليلي */
                    .dark .social-btn {
                        background-color: #1e293b; 
                        color: #94a3b8;
                        border-color: #334155;
                    }

                    /* تأثير زر جيت هب */
                    .social-btn.github:hover {
                        background-color: #333333;
                        color: #ffffff;
                        transform: translateY(-3px) scale(1.05);
                    }
                    
                    /* تأثير زر لينكد إن */
                    .social-btn.linkedin:hover {
                        background-color: #0A66C2;
                        color: #ffffff;
                        transform: translateY(-3px) scale(1.05);
                    }

                    /* -------------------------------------------
                       تنسيق زر تحميل تطبيق الموبايل
                    ------------------------------------------- */
                    .app-download-btn {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        background-color: ${roleColor};
                        padding: 10px 24px;
                        border-radius: 9999px;
                        color: #ffffff !important;
                        transition: all 0.3s ease;
                        box-shadow: 0 4px 14px ${roleColor}40;
                    }
                    
                    .app-download-btn:hover {
                        transform: translateY(-2px);
                        filter: brightness(1.1);
                    }
                </style>

                <!-- ======================================================
                     بناء هيكل الفوتر الفعلي (المحتوى اللي بيشوفه المستخدم)
                     ====================================================== -->
            <div class="premium-footer mt-10">
                
                <!-- هاد هو الخط الفاصل اللي بيلمع اللي نسقناه فوق -->
                <div class="fading-border"></div>

                <!-- الصندوق اللي بيحتوي على الـ 3 أقسام (يمين، وسط، يسار) -->
                <div class="footer-content">
                    
                    <!-- القسم الأول (يمين الصفحة): اسم النظام ورقم الإصدار -->
                    <div class="flex-1 flex flex-col items-center md:items-start justify-center order-2 md:order-1">
                        <div class="flex items-center gap-2">
                            <!-- اسم النظام (مربوط بملف اللغة) -->
                            <span class="font-black text-xl tracking-widest uppercase text-gray-800 dark:text-white">
                                <?php echo $lang['footer_brand'] ?? 'PharmaSmart'; ?>
                            </span>
                            <!-- المربع الصغير اللي مكتوب فيه رقم الإصدار (بياخذ لون المستخدم) -->
                            <span class="px-2 py-0.5 rounded text-[10px] font-black text-white shadow-sm" style="background-color: ${roleColor};">
                                v1.0
                            </span>
                        </div>
                    </div>

                    <!-- القسم الثاني (وسط الصفحة): الأيقونات وتحتها حقوق النشر -->
                    <div class="flex-1 flex flex-col justify-center items-center order-1 md:order-2 mb-4 md:mb-0">
                        
                        <!-- صندوق الأيقونات -->
                        <div class="social-icons-group">
                            <a href="https://github.com" target="_blank" class="social-btn github" title="GitHub">
                                <svg viewBox="0 0 496 512" width="18" height="18" fill="currentColor"><path d="M165.9 397.4c0 2-2.3 3.6-5.2 3.6-3.3.3-5.6-1.3-5.6-3.6 0-2 2.3-3.6 5.2-3.6 3-.3 5.6 1.3 5.6 3.6zm-31.1-4.5c-.7 2 1.3 4.3 4.3 4.9 2.6 1 5.6 0 6.2-2s-1.3-4.3-4.3-5.2c-2.6-.7-5.5.3-6.2 2.3zm44.2-1.7c-2.9.7-4.9 2.6-4.6 4.9.3 2 2.9 3.3 5.9 2.6 2.9-.7 4.9-2.6 4.6-4.6-.3-1.9-3-3.2-5.9-2.9zM244.8 8C106.1 8 0 113.3 0 252c0 110.9 69.8 205.8 169.5 239.2 12.8 2.3 17.3-5.6 17.3-12.1 0-6.2-.3-40.4-.3-61.4 0 0-70 15-84.7-29.8 0 0-11.4-29.1-27.8-36.6 0 0-22.9-15.7 1.6-15.4 0 0 24.9 2 38.6 25.8 21.9 38.6 58.6 27.5 72.9 20.9 2.3-16 8.8-27.1 16-33.7-55.9-6.2-112.3-14.3-112.3-110.5 0-27.5 7.6-41.3 23.6-58.9-2.6-6.5-11.1-33.3 2.6-67.9 20.9-6.5 69 27 69 27 20-5.6 41.5-8.5 62.8-8.5s42.8 2.9 62.8 8.5c0 0 48.1-33.6 69-27 13.7 34.7 5.2 61.4 2.6 67.9 16 17.7 25.8 31.5 25.8 58.9 0 96.5-58.9 104.2-114.8 110.5 9.2 7.9 17 22.9 17 46.4 0 33.7-.3 75.4-.3 83.6 0 6.5 4.6 14.4 17.3 12.1C428.2 457.8 496 362.9 496 252 496 113.3 383.5 8 244.8 8zM97.2 352.9c-1.3 1-1 3.3.7 5.2 1.6 1.6 3.9 2.3 5.2 1 1.3-1 1-3.3-.7-5.2-1.6-1.6-3.9-2.3-5.2-1zm-10.8-8.1c-.7 1.3.3 2.9 2.3 3.9 1.6 1 3.6.7 4.3-.7.7-1.3-.3-2.9-2.3-3.9-2-.6-3.6-.3-4.3.7zm32.4 35.6c-1.6 1.3-1 4.3 1.3 6.2 2.3 2.3 5.2 2.6 6.5 1 1.3-1.3.7-4.3-1.3-6.2-2.2-2.3-5.2-2.6-6.5-1zm-11.4-14.7c-1.6 1-1.6 3.6 0 5.9 1.6 2.3 4.3 3.3 5.6 2.3 1.6-1.3 1.6-3.9 0-6.2-1.4-2.3-4-3.3-5.6-2z"/></svg>
                            </a>
                            <a href="https://linkedin.com" target="_blank" class="social-btn linkedin" title="LinkedIn">
                                <svg viewBox="0 0 448 512" width="18" height="18" fill="currentColor"><path d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z"/></svg>
                            </a>
                        </div>
                        
                        <!-- حقوق النشر (مربوطة بملف اللغة) -->
                        <div class="text-xs font-bold text-gray-500 dark:text-gray-400 tracking-wide mt-3 text-center">
                            &copy; <?php echo $year; ?> <?php echo $lang['footer_copyright'] ?? 'جميع الحقوق محفوظة.'; ?>
                        </div>
                    </div>

                    <!-- القسم الثالث (يسار الصفحة): زر تحميل تطبيق الموبايل -->
                    <div class="flex-1 flex justify-center md:justify-end items-center order-3">
                        <a href="#" class="app-download-btn">
                            <!-- أيقونة التلفون -->
                            <i data-lucide="smartphone" class="w-6 h-6"></i>
                            <div class="flex flex-col text-start rtl:text-right">
                                <!-- النص الصغير اللي فوق (مربوط باللغة) -->
                                <span class="text-[9px] font-bold uppercase tracking-widest mb-0.5 opacity-90">
                                    <?php echo $lang['available_on'] ?? 'حمل تطبيقنا الآن'; ?>
                                </span>
                                <!-- النص العريض اللي تحت (مربوط باللغة) -->
                                <span class="text-sm font-black leading-none">
                                    <?php echo $lang['download_app'] ?? 'تطبيق الموبايل'; ?>
                                </span>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
        `;
        
        // 5. بنلزق الفوتر بآخر عنصر الـ main بالصفحة
        mainElement.insertAdjacentHTML('beforeend', footerHTML);
    }
    
    // 6. هاد السطر عشان يفعل أيقونة التلفون
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

// ========================================================================

// إعدادات الإشعارات المنبثقة

// ========================================================================
const Toast = Swal.mixin({
    toast: true,
    position: document.dir === 'rtl' ? 'top-start' : 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937',
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});
</script>
</body>
</html>