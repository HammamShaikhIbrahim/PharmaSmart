<?php
// ========================================================================
// إعدادات المتغيرات الأساسية (اللون والدور)
// ========================================================================
$year = date('Y');
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 0;
// لون أزرق للأدمن، أخضر للصيدلي
$accentColor = ($role_id == 1) ? '#048AC1' : '#0A7A48';
?>

</div> <!-- إغلاق حاوية الـ main القادمة من الهيدر -->

<!-- ======================================================================== -->
<!-- بداية كود الفوتر البسيط (Minimalist Signature Footer) -->
<!-- ======================================================================== -->
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const mainElement = document.querySelector('main');
        const roleColor = "<?php echo $accentColor; ?>";

        if (mainElement) {
            
            // تصميم نظيف وبسيط يركز على الأسماء واسم المشروع فقط
            const footerHTML = `
                <style>
                    /* الحاوية الأساسية للفوتر */
                    .signature-footer {
                        margin-top: 4rem; 
                        margin-left: -2rem; 
                        margin-right: -2rem;
                        margin-bottom: -2rem;
                        background-color: #ffffff;
                        border-top: 1px solid #f1f5f9;
                        padding: 2rem 1rem 1.5rem 1rem;
                        position: relative;
                        overflow: hidden; /* لمنع خروج العلامة المائية */
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        transition: background-color 0.3s ease, border-color 0.3s ease;
                        z-index: 10;
                    }

                    .dark .signature-footer {
                        background-color: #0f172a;
                        border-top: 1px solid #1e293b;
                    }

                    /*  الشعار كعلامة مائية شفافة في الخلفية */
                    .footer-watermark {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        width: 250px; /* حجم الشعار */
                        height: auto;
                        opacity: 0.04; /* شفافية منخفضة جداً ليكون أنيقاً */
                        pointer-events: none; /* لمنع التفاعل معه */
                        z-index: 0;
                    }

                    .dark .footer-watermark {
                        opacity: 0.02; /* شفافية أقل في الوضع الليلي */
                    }

                    /* المحتوى الفعلي فوق العلامة المائية */
                    .footer-content-wrapper {
                        position: relative;
                        z-index: 1;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 1rem; /*  تقليل المسافة بين اسم المشروع والأسماء */
                    }

                    /* اسم المشروع */
                    .brand-name {
                        font-size: 2rem; /*  تصغير حجم الخط قليلاً ليتناسب مع النحافة الجديدة */
                        font-weight: 900;
                        letter-spacing: 0.05em;
                        color: ${roleColor}; /* يأخذ لون الدور (أزرق/أخضر) */
                        margin: 0;
                        line-height: 1;
                    }

                    /* حاوية أسماء المطورين */
                    .team-names {
                        display: flex;
                        flex-wrap: wrap;
                        align-items: center;
                        justify-content: center;
                        gap: 1rem;
                        color: #64748b;
                        font-weight: 700;
                        font-size: 1rem; /*  تصغير خط الأسماء درجة واحدة */
                    }

                    .dark .team-names {
                        color: #94a3b8;
                    }

                    /* النقطة الفاصلة بين الأسماء (تختفي في الموبايل) */
                    .name-separator {
                        color: ${roleColor};
                        opacity: 0.6;
                        margin: 0 0.5rem;
                    }

                    @media (max-width: 640px) {
                        .team-names {
                            flex-direction: column;
                            gap: 0.5rem;
                        }
                        .name-separator {
                            display: none;
                        }
                    }
                </style>

                <footer class="signature-footer">
                   
                    
                    <div class="footer-content-wrapper">
                        <h2 class="brand-name">PharmaSmart</h2>
                        
                        <div class="team-names" dir="ltr">
                            <span>Hammam Ibrahim</span>
                            <span class="name-separator"></span>
                            <span>Obada Majdobi</span>
                            <span class="name-separator"></span>
                            <span>Qais Daraghmeh</span>
                        </div>
                    </div>
                </footer>
            `;

            mainElement.insertAdjacentHTML('beforeend', footerHTML);
        }
    });

// ========================================================================
// إعدادات الإشعارات المنبثقة (SweetAlert)
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