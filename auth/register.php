<?php
// ==========================================
// 1. استدعاء ملف الاتصال بقاعدة البيانات
// ==========================================
include('../config/database.php');
session_start();

$message = ""; 
$error = "";  

// ==========================================
// 2. معالجة طلب التسجيل 
// ==========================================
if (isset($_POST['register'])) {
    $fname = mysqli_real_escape_string($conn, $_POST['fname']);
    $lname = mysqli_real_escape_string($conn, $_POST['lname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']); 

    $pName = mysqli_real_escape_string($conn, $_POST['pName']);
    $license = mysqli_real_escape_string($conn, $_POST['license']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $workingHours = mysqli_real_escape_string($conn, $_POST['workingHours']);

    $lat = (float)$_POST['lat'];
    $lng = (float)$_POST['lng'];

    $logo = "default.png"; 
    if (!empty($_FILES['logo']['name'])) {
        $logo = time() . "_" . $_FILES['logo']['name']; 
        move_uploaded_file($_FILES['logo']['tmp_name'], "../uploads/" . $logo);
    }

    $checkEmail = mysqli_query($conn, "SELECT UserID FROM User WHERE Email = '$email'");
    
    if (mysqli_num_rows($checkEmail) > 0) {
        $error = "عذراً، هذا البريد الإلكتروني مسجل مسبقاً!";
    } elseif (empty($lat) || empty($lng)) {
        $error = "يرجى تحديد موقع الصيدلية بدقة على الخريطة.";
    } else {
        $sqlUser = "INSERT INTO User (Fname, Lname, Email, Password, Phone, RoleID)
                    VALUES ('$fname', '$lname', '$email', '$password', '$phone', 2)";
        
        if (mysqli_query($conn, $sqlUser)) {
            $userId = mysqli_insert_id($conn);
            
            $sqlPhar = "INSERT INTO Pharmacist (PharmacistID, PharmacyName, LicenseNumber, Location, Latitude, Longitude, WorkingHours, Logo, IsApproved)
                        VALUES ($userId, '$pName', '$license', '$location', $lat, $lng, '$workingHours', '$logo', 0)";
            
            if (mysqli_query($conn, $sqlPhar)) {
                $message = "تم إرسال طلب انضمامك بنجاح! يرجى انتظار تفعيل حسابك من الإدارة.";
            } else {
                $error = "حدث خطأ أثناء حفظ بيانات الصيدلية: " . mysqli_error($conn);
            }
        } else {
            $error = "حدث خطأ أثناء إنشاء حساب المستخدم: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل صيدلية جديدة - PharmaSmart</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
       
        tailwind.config = { darkMode: 'class' }
    </script>

    <style>
        body, html {
            min-height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden; 
        }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1), inset 0 0 0 1px rgba(255, 255, 255, 0.4);
        }
        .dark .glass-panel {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), inset 0 0 0 1px rgba(255, 255, 255, 0.05);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            color: #1f2937;
            transition: all 0.3s ease;
        }
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: #10b981;
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }
        .dark .glass-input {
            background: rgba(30, 41, 59, 0.6);
            border-color: rgba(255, 255, 255, 0.15);
            color: #f8fafc;
        }
        .dark .glass-input:focus {
            background: rgba(30, 41, 59, 0.95);
            border-color: #10b981;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-teal-50 to-emerald-200 dark:from-slate-900 dark:to-teal-950 relative transition-colors duration-500 py-10 min-h-screen">

    <!-- ==========================================
         الأشكال موزعة على كامل مساحة الشاشة (Fixed Background)
    ========================================== -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        
        <!-- 1. الصليب الطبي (أعلى اليسار) -->
        <div class="absolute top-16 left-12 w-32 h-32 transform rotate-12 opacity-90">
            <div class="absolute inset-x-8 inset-y-0 rounded-xl bg-gradient-to-br from-green-300 to-teal-400 dark:from-green-600 dark:to-teal-700 shadow-[inset_3px_3px_10px_rgba(255,255,255,0.7),inset_-3px_-3px_10px_rgba(0,0,0,0.2)]"></div>
            <div class="absolute inset-y-8 inset-x-0 rounded-xl bg-gradient-to-br from-green-300 to-teal-400 dark:from-green-600 dark:to-teal-700 shadow-[inset_3px_3px_10px_rgba(255,255,255,0.7),inset_-3px_-3px_10px_rgba(0,0,0,0.2)]"></div>
        </div>

        <!-- 2. كرة زجاجية عملاقة (أعلى اليمين، تخرج من الشاشة) -->
        <div class="absolute -top-20 -right-20 w-[400px] h-[400px] rounded-full 
                    bg-gradient-to-tr from-emerald-300/80 to-teal-500/50 dark:from-emerald-600/60 dark:to-teal-800/50
                    backdrop-blur-sm
                    shadow-[inset_15px_15px_40px_rgba(255,255,255,0.7),inset_-10px_-10px_30px_rgba(0,0,0,0.2),0_20px_40px_rgba(20,184,166,0.3)]">
        </div>

        <!-- 3. كبسولة دواء طويلة مائلة (منتصف اليسار) -->
        <div class="absolute top-1/3 -left-10 w-40 h-96 rounded-[100px] transform rotate-[30deg]
                    bg-gradient-to-b from-teal-200/90 to-emerald-500/60 dark:from-teal-700/70 dark:to-emerald-900/60
                    backdrop-blur-md border border-white/40 dark:border-white/10
                    shadow-[inset_10px_10px_25px_rgba(255,255,255,0.8),inset_-5px_-5px_20px_rgba(16,185,129,0.3),0_15px_30px_rgba(16,185,129,0.2)]">
        </div>

        <!-- 4. حلقة علمية (Torus) (منتصف اليمين) -->
        <div class="absolute top-[40%] right-10 w-64 h-64 
                    border-[50px] border-emerald-400/50 dark:border-emerald-700/50 
                    rounded-full transform -rotate-[15deg] scale-y-75 backdrop-blur-sm
                    shadow-[inset_10px_10px_20px_rgba(255,255,255,0.6),inset_-10px_-10px_20px_rgba(0,0,0,0.2),0_20px_40px_rgba(20,184,166,0.2)]">
        </div>

        <!-- 5. قطرة سيروم / دورق (أسفل اليسار) -->
        <div class="absolute bottom-10 left-[15%] w-[250px] h-[300px] 
                    bg-gradient-to-b from-teal-200/50 to-emerald-400/30 dark:from-teal-600/40 dark:to-emerald-900/30
                    rounded-[50%_50%_50%_50%/60%_60%_40%_40%] transform -rotate-[10deg] backdrop-blur-xl border border-white/60 dark:border-white/10
                    shadow-[inset_15px_15px_40px_rgba(255,255,255,0.8),inset_-10px_-10px_20px_rgba(16,185,129,0.3),0_20px_50px_rgba(20,184,166,0.2)]">
        </div>

        <!-- 6. أنبوب ملتوٍ (متعرج) (أسفل اليمين يخرج من الشاشة) -->
        <div class="absolute -bottom-32 -right-10 w-[500px] h-[400px] 
                    border-[80px] border-t-transparent border-l-transparent border-emerald-300/60 dark:border-emerald-600/40 
                    rounded-[200px] transform rotate-[45deg] backdrop-blur-md
                    shadow-[inset_20px_20px_40px_rgba(255,255,255,0.7),0_30px_60px_rgba(16,185,129,0.3)]">
        </div>

        <!-- توهج ضوئي خلفي في المنتصف لربط الألوان -->
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[100vw] h-[100vh] bg-emerald-400/10 dark:bg-teal-900/30 rounded-full blur-[150px] z-[-1]"></div>
    </div>

    <!-- ==========================================
         نموذج التسجيل الزجاجي (يصعد فوق الأشكال أثناء السكرول)
    ========================================== -->
    <div class="relative z-10 flex items-center justify-center w-full px-4">
        
        <div class="glass-panel p-8 md:p-12 rounded-[2.5rem] w-full max-w-4xl transition-all duration-300 my-auto">
            
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white/40 dark:bg-slate-800/60 backdrop-blur-md rounded-2xl mb-4 text-emerald-600 dark:text-emerald-400 shadow-inner border border-white/50 dark:border-white/10">
                    <i data-lucide="store" class="w-8 h-8"></i>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-2">انضمام لشبكة PharmaSmart</h2>
                <p class="text-sm font-bold text-gray-700 dark:text-gray-300 opacity-90">قم بملء بياناتك بدقة ليتم مراجعتها من قبل الإدارة</p>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-8">
                
                <!-- القسم الأول: البيانات الشخصية -->
                <div class="bg-white/30 dark:bg-slate-800/40 p-6 md:p-8 rounded-3xl border border-white/50 dark:border-slate-700/50 shadow-sm">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white mb-5 flex items-center gap-2">
                        <i data-lucide="user" class="text-emerald-600 dark:text-emerald-400"></i> البيانات الشخصية
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 ml-1">الاسم الأول</label>
                            <input type="text" name="fname" required class="glass-input w-full p-3.5 rounded-xl">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 ml-1">اسم العائلة</label>
                            <input type="text" name="lname" required class="glass-input w-full p-3.5 rounded-xl">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 ml-1">رقم الهاتف</label>
                            <input type="text" name="phone" required class="glass-input w-full p-3.5 rounded-xl text-left" dir="ltr" placeholder="05XXXXXXXX">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 ml-1">البريد الإلكتروني</label>
                            <input type="email" name="email" required class="glass-input w-full p-3.5 rounded-xl text-left" dir="ltr" placeholder="name@domain.com">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 ml-1">كلمة المرور</label>
                            <input type="password" name="password" required class="glass-input w-full p-3.5 rounded-xl text-left" dir="ltr" placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <!-- القسم الثاني: بيانات الصيدلية -->
                <div class="bg-white/30 dark:bg-slate-800/40 p-6 md:p-8 rounded-3xl border border-white/50 dark:border-slate-700/50 shadow-sm">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white mb-5 flex items-center gap-2">
                        <i data-lucide="building" class="text-emerald-600 dark:text-emerald-400"></i> بيانات الصيدلية
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 ml-1">اسم الصيدلية الرسمي</label>
                            <input type="text" name="pName" required class="glass-input w-full p-3.5 rounded-xl">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 ml-1">رقم الترخيص من وزارة الصحة</label>
                            <input type="text" name="license" required class="glass-input w-full p-3.5 rounded-xl text-left" dir="ltr">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 ml-1">العنوان الوصفي (المدينة - الشارع)</label>
                            <input type="text" name="location" required class="glass-input w-full p-3.5 rounded-xl placeholder-gray-500" placeholder="مثال: رام الله - شارع الإرسال">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 ml-1">ساعات الدوام</label>
                            <input type="text" name="workingHours" required class="glass-input w-full p-3.5 rounded-xl placeholder-gray-500" placeholder="مثال: 8 صباحاً - 10 مساءً">
                        </div>
                    </div>
                  
<div>
<label class="block text-xs font-bold text-gray-300 mb-2 ml-1">
شعار الصيدلية 
</label>

<label class="flex items-center justify-between w-full p-3 rounded-xl 
bg-white/5 border border-white/10 backdrop-blur-md 
 
transition cursor-pointer">

<span class="text-gray-400 text-sm">
اختر شعار الصيدلية
</span>

<span class="px-3 py-1.5 text-xs font-semibold rounded-lg 
bg-emerald-500/20 text-emerald-400 border border-emerald-400/30
transition duration-300
hover:bg-emerald-500/30
hover:shadow-[0_0_10px_rgba(16,185,129,0.9)]">
Upload
</span>

<input type="file" name="logo" accept="image/*" class="hidden">

</label>
</div>


                </div>

                <!-- القسم الثالث: الخريطة -->
                <div class="bg-emerald-500/20 dark:bg-teal-900/30 p-6 md:p-8 rounded-3xl border border-emerald-500/30 dark:border-teal-500/30 shadow-sm">
                    <h3 class="text-lg font-black text-emerald-900 dark:text-emerald-400 mb-2 flex items-center gap-2">
                        <i data-lucide="map-pin" class="text-emerald-700 dark:text-emerald-400"></i> الموقع الجغرافي (GPS)
                    </h3>
                    <p class="text-xs text-emerald-800 dark:text-emerald-500/90 font-bold mb-5">يرجى الضغط على الخريطة لتحديد موقع الصيدلية بدقة، سيساعد هذا المرضى في العثور عليك عبر التطبيق.</p>
                    
                    <div class="relative w-full h-[300px] rounded-2xl overflow-hidden border-2 border-white/60 dark:border-slate-700/60 shadow-inner z-0">
                        <div id="pickerMap" class="absolute inset-0"></div>
                    </div>
                    
                    <div class="relative w-full h-0 overflow-hidden">
                        <input type="text" name="lat" id="latInput" required style="opacity: 0; position: absolute;">
                        <input type="text" name="lng" id="lngInput" required style="opacity: 0; position: absolute;">
                    </div>
                </div>

                <!-- زر الإرسال -->
                <button type="submit" name="register" class="w-full bg-emerald-600 text-white py-4 rounded-2xl hover:bg-emerald-700 transition-all font-black text-lg mt-8 shadow-[0_10px_20px_rgba(16,185,129,0.3)] active:scale-[0.98] border border-emerald-500/50">
                    إرسال طلب الانضمام
                </button>
            </form>

            <div class="mt-8 text-center text-sm text-gray-800 dark:text-gray-300 font-bold border-t border-white/40 dark:border-slate-700/50 pt-6">
                لديك حساب بالفعل؟ <a href="login.php" class="text-emerald-700 dark:text-emerald-400 hover:underline mx-1 font-black">العودة لتسجيل الدخول</a>
            </div>
        </div>
    </div>

    <!-- السكربتات -->
    <script>
        lucide.createIcons();

        var map = L.map('pickerMap').setView([31.90, 35.20], 8);
        
        var tileUrl = document.documentElement.classList.contains('dark') 
            ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' 
            : 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';

        L.tileLayer(tileUrl, { maxZoom: 19 }).addTo(map);

        var marker;

        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.circleMarker(e.latlng, {
                    radius: 8,
                    fillColor: "#10b981",
                    color: "#ffffff",
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 1
                }).addTo(map);
            }

            document.getElementById('latInput').value = lat;
            document.getElementById('lngInput').value = lng;
        });

        <?php if($message): ?>
            Swal.fire({
                icon: 'success',
                title: 'اكتمل الطلب!',
                text: '<?php echo $message; ?>',
                confirmButtonColor: '#10b981',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : 'rgba(255,255,255,0.9)',
                backdrop: 'rgba(0,0,0,0.4)',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937',
                customClass: { popup: 'backdrop-blur-xl border border-white/20' }
            }).then(() => { window.location.href = 'login.php'; });
        <?php endif; ?>

        <?php if($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#10b981',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : 'rgba(255,255,255,0.9)',
                backdrop: 'rgba(0,0,0,0.4)',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937',
                customClass: { popup: 'backdrop-blur-xl border border-white/20' }
            });
        <?php endif; ?>
    </script>
</body>
</html>