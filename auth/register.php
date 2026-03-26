<?php
include('../config/database.php');
session_start();
require_once('../includes/lang.php');

$message = "";
$error = "";


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
        $error = $lang['email_exists_error'];
    } elseif (empty($lat) || empty($lng)) {
        $error = $lang['location_error'];
    } else {
        $sqlUser = "INSERT INTO User (Fname, Lname, Email, Password, Phone, RoleID)
                    VALUES ('$fname', '$lname', '$email', '$password', '$phone', 2)";

        if (mysqli_query($conn, $sqlUser)) {
            $userId = mysqli_insert_id($conn);

            $sqlPhar = "INSERT INTO Pharmacist (PharmacistID, PharmacyName, LicenseNumber, Location, Latitude, Longitude, WorkingHours, Logo, IsApproved)
                        VALUES ($userId, '$pName', '$license', '$location', $lat, $lng, '$workingHours', '$logo', 0)";

            if (mysqli_query($conn, $sqlPhar)) {
                $message = $lang['registration_success'];
            } else {
                $error = $lang['registration_error'] . " " . mysqli_error($conn);
            }
        } else {
            $error = $lang['user_creation_error'] . " " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" dir="<?php echo $dir; ?>" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark') ? 'dark' : ''; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ✅ **الإصلاح الأول:** تم حذف علامة ">" الزائدة من السطر التالي -->
    <title><?php echo $lang['register_title']; ?></title>
    <script src="https://kit.fontawesome.com/804071b851.js" crossorigin="anonymous"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>

    <style>
        /* ✅ **الإصلاح الثاني (أ):** تم تعديل هذه القاعدة لمنع التمرير الافتراضي */
        body,
        html {
            height: 100%;
            overflow: hidden; /* يمنع ظهور شريط التمرير الافتراضي للصفحة كلها */
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* ✅ **الإصلاح الثاني (ب):** تم إضافة كود شريط التمرير المخصص */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 10px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background-color: #475569;
        }
        /* نهاية كود شريط التمرير */

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

<body class="bg-gradient-to-br from-teal-50 to-emerald-200 dark:from-slate-900 dark:to-teal-950 relative transition-colors duration-500">

    <!-- ... (الكود المتبقي من الخلفية والأزرار يبقى كما هو) ... -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute top-10 left-20 w-32 h-64 rounded-full transform rotate-[35deg] bg-gradient-to-b from-emerald-300 to-teal-500 dark:from-emerald-600 dark:to-teal-800 shadow-[inset_15px_15px_30px_rgba(255,255,255,0.7),inset_-10px_-10px_30px_rgba(0,0,0,0.2),10px_20px_40px_rgba(20,184,166,0.3)]"></div>
        <div class="absolute top-1/4 right-20 w-48 h-48 rounded-full transform -rotate-[15deg] bg-gradient-to-tr from-green-200 to-emerald-400 dark:from-green-700 dark:to-emerald-600 shadow-[inset_-10px_-10px_30px_rgba(0,0,0,0.15),inset_15px_15px_30px_rgba(255,255,255,0.8),0_20px_40px_rgba(16,185,129,0.2)]"><div class="absolute top-1/2 left-4 right-4 h-1 bg-white/40 dark:bg-black/10 rounded-full transform -translate-y-1/2 shadow-[inset_0_1px_2px_rgba(0,0,0,0.1)]"></div></div>
        <div class="absolute bottom-20 left-1/4 w-32 h-32 transform rotate-[15deg] opacity-80"><div class="absolute inset-x-10 inset-y-0 rounded-2xl bg-gradient-to-br from-teal-300 to-cyan-500 dark:from-teal-600 dark:to-cyan-800 shadow-[inset_5px_5px_15px_rgba(255,255,255,0.6),inset_-5px_-5px_15px_rgba(0,0,0,0.2)]"></div><div class="absolute inset-y-10 inset-x-0 rounded-2xl bg-gradient-to-br from-teal-300 to-cyan-500 dark:from-teal-600 dark:to-cyan-800 shadow-[inset_5px_5px_15px_rgba(255,255,255,0.6),inset_-5px_-5px_15px_rgba(0,0,0,0.2)]"></div></div>
        <div class="absolute bottom-1/3 right-1/3 w-20 h-40 rounded-full transform -rotate-[40deg] blur-md bg-gradient-to-r from-emerald-400 to-green-300 dark:from-emerald-700 dark:to-green-800 shadow-[inset_5px_5px_15px_rgba(255,255,255,0.5)]"></div>
    </div>
    <div class="absolute top-6 right-6 flex items-center gap-3 z-50">
        <button id="theme-toggle" type="button" class="glass-panel p-3 rounded-2xl text-gray-700 dark:text-white transition-all duration-300 hover:bg-white/40 dark:hover:bg-slate-800/70 hover:shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:-translate-y-1 focus:outline-none flex items-center justify-center group"><i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5 text-amber-400 transition-transform duration-500 group-hover:rotate-90"></i><i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5 text-amber-400 transition-transform duration-500 group-hover:-rotate-12"></i></button>
        <a href="?lang=<?php echo $lang['switch_lang_code']; ?>" class="glass-panel text-gray-800 dark:text-white font-bold px-5 py-3 rounded-2xl transition-all duration-300 hover:bg-white/40 dark:hover:bg-slate-800/70 hover:shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:-translate-y-1 flex items-center gap-2 text-sm group"><i data-lucide="globe" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 transition-transform duration-500 group-hover:rotate-180"></i><span class="group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors"><?php echo $lang['switch_lang_text']; ?></span></a>
    </div>

    <!-- ✅ **الإصلاح الثاني (ج):** تم إضافة حاوية قابلة للتمرير مع الحفاظ على التصميم الأصلي -->
    <main class="h-full w-full overflow-y-auto flex items-center justify-center p-4">
        <div class="relative z-10 flex items-center justify-center w-full my-auto">
            <div class="glass-panel p-8 md:p-12 rounded-[2.5rem] w-full max-w-4xl transition-all duration-300">
                <div class="text-center mb-10">
                    <h2 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-2"><?php echo $lang['register_title']; ?></h2>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300 opacity-90"><?php echo $lang['register_subtitle']; ?></p>
                </div>
                <!-- ... (بقية الفورم تبقى كما هي بدون تغيير) ... -->
                <form method="POST" enctype="multipart/form-data" class="space-y-8">
                    <div class="bg-white/30 dark:bg-slate-800/40 p-6 md:p-8 rounded-3xl border border-white/50 dark:border-slate-700/50 shadow-sm">
                        <h3 class="text-lg font-black text-gray-900 dark:text-white mb-5 flex items-center gap-2"><i class="fa-solid fa-user-doctor text-emerald-600 dark:text-emerald-400"></i><?php echo $lang['personal_info']; ?></h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div><label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 rtl:ml-1 ltr:mr-1"><?php echo $lang['first_name']; ?></label> <input type="text" name="fname" required class="glass-input w-full p-3.5 rounded-xl"></div>
                            <div><label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 rtl:ml-1 ltr:mr-1"><?php echo $lang['last_name']; ?></label><input type="text" name="lname" required class="glass-input w-full p-3.5 rounded-xl"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div class="md:col-span-1"><label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 rtl:ml-1 ltr:mr-1"><?php echo $lang['phone']; ?></label><input type="text" name="phone" required class="glass-input w-full p-3.5 rounded-xl text-left" dir="ltr" placeholder="05XXXXXXXX"></div>
                            <div class="md:col-span-1"><label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 rtl:ml-1 ltr:mr-1"><?php echo $lang['email']; ?></label><input type="email" name="email" required class="glass-input w-full p-3.5 rounded-xl text-left" dir="ltr" placeholder="name@pharma.com"></div>
                            <div class="md:col-span-1"><label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 rtl:ml-1 ltr:mr-1"><?php echo $lang['password']; ?></label><input type="password" name="password" required class="glass-input w-full p-3.5 rounded-xl text-left" dir="ltr" placeholder="••••••••"></div>
                        </div>
                    </div>
                    <div class="bg-white/30 dark:bg-slate-800/40 p-6 md:p-8 rounded-3xl border border-white/50 dark:border-slate-700/50 shadow-sm">
                        <h3 class="text-lg font-black text-gray-900 dark:text-white mb-5 flex items-center gap-2"><i class="fa-solid fa-staff-snake text-emerald-600 dark:text-emerald-400"></i> <?php echo $lang['pharmacy_info']; ?></h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div><label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 rtl:ml-1 ltr:mr-1"><?php echo $lang['pharmacy_name']; ?></label><input type="text" name="pName" required class="glass-input w-full p-3.5 rounded-xl"></div>
                            <div><label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 rtl:ml-1 ltr:mr-1"><?php echo $lang['license_num']; ?></label><input type="text" name="license" required class="glass-input w-full p-3.5 rounded-xl text-left" dir="ltr"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div><label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 rtl:ml-1 ltr:mr-1"><?php echo $lang['address']; ?></label><input type="text" name="location" required class="glass-input w-full p-3.5 rounded-xl placeholder-gray-500"></div>
                            <div><label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 rtl:ml-1 ltr:mr-1"><?php echo $lang['working_hours']; ?></label><input type="text" name="workingHours" required class="glass-input w-full p-3.5 rounded-xl placeholder-gray-500"></div>
                        </div>
                        <div class="md:col-span-2"><label class="block text-xs font-bold text-gray-800 dark:text-gray-200 mb-1.5 rtl:ml-1 ltr:mr-1"><?php echo $lang['pharmacy_logo']; ?></label><label class="flex items-center justify-between w-full p-3 rounded-xl bg-white/5 border border-white/20 dark:border-white/10 backdrop-blur-md transition cursor-pointer group hover:bg-white/10"><span id="logo-file-name" class="text-gray-600 dark:text-gray-400 text-sm truncate max-w-[70%]"><?php echo $lang['choose_logo']; ?></span><span class="px-4 py-1.5 text-xs font-bold rounded-lg bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-400/30 transition duration-300 group-hover:bg-emerald-500/30 group-hover:shadow-[0_0_10px_rgba(16,185,129,0.4)]"><?php echo $lang['upload']; ?> <i data-lucide="upload" class="inline w-3 h-3 rtl:mr-1 ltr:ml-1"></i></span><input type="file" name="logo" accept="image/*" class="hidden" onchange="updateLogoName(this)"></label></div>
                    </div>
                    <div class="bg-emerald-500/20 dark:bg-teal-900/30 p-6 md:p-8 rounded-3xl border border-emerald-500/30 dark:border-teal-500/30 shadow-sm">
                        <h3 class="text-lg font-black text-emerald-900 dark:text-emerald-400 mb-2 flex items-center gap-2"><i data-lucide="map-pin" class="text-emerald-700 dark:text-emerald-400"></i> <?php echo $lang['location_picker']; ?></h3>
                        <p class="text-xs text-emerald-800 dark:text-emerald-500/90 font-bold mb-5"><?php echo $lang['location_description']; ?></p>
                        <div class="relative w-full h-[300px] rounded-2xl overflow-hidden border-2 border-white/60 dark:border-slate-700/60 shadow-inner z-0"><div id="pickerMap" class="absolute inset-0"></div></div>
                        <div class="relative w-full h-0 overflow-hidden"><input type="text" name="lat" id="latInput" required style="opacity: 0; position: absolute;"><input type="text" name="lng" id="lngInput" required style="opacity: 0; position: absolute;"></div>
                    </div>
                    <button type="submit" name="register" class="w-full bg-emerald-600 text-white py-4 rounded-2xl hover:bg-emerald-700 transition-all font-black text-lg mt-8 shadow-[0_10px_20px_rgba(16,185,129,0.3)] active:scale-[0.98] border border-emerald-500/50"><?php echo $lang['register_button']; ?></button>
                </form>
                <div class="mt-8 text-center text-sm text-gray-800 dark:text-gray-300 font-bold border-t border-white/40 dark:border-slate-700/50 pt-6"><?php echo $lang['already_have_account']; ?> <a href="login.php" class="text-emerald-700 dark:text-emerald-400 hover:underline mx-1 font-black"><?php echo $lang['login_link']; ?></a></div>
            </div>
        </div>
    </main>

    <!-- ... (الكود المتبقي من JavaScript يبقى كما هو بدون تغيير) ... -->
    <script>
        lucide.createIcons();
        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        if (localStorage.getItem('color-theme') === 'light' || (!('color-theme' in localStorage))) {
            themeToggleDarkIcon.classList.remove('hidden');
        } else {
            themeToggleLightIcon.classList.remove('hidden');
        }
        function updateThemeIcons() {
            var isDark = document.documentElement.classList.contains('dark');
            var sunIcon = document.getElementById('theme-toggle-light-icon');
            var moonIcon = document.getElementById('theme-toggle-dark-icon');
            if (isDark) {
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
            } else {
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
            }
        }
        updateThemeIcons();
        var themeToggleBtn = document.getElementById('theme-toggle');
        themeToggleBtn.addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');
            if (document.documentElement.classList.contains('dark')) {
                localStorage.setItem('color-theme', 'dark');
                document.cookie = "theme=dark; path=/";
            } else {
                localStorage.setItem('color-theme', 'light');
                document.cookie = "theme=light; path=/";
            }
            updateThemeIcons();
            if (typeof map !== 'undefined') {
                var newTileUrl = document.documentElement.classList.contains('dark') ?
                    'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
                    'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
                L.tileLayer(newTileUrl, {
                    maxZoom: 19
                }).addTo(map);
            }
        });
        var map = L.map('pickerMap').setView([31.90, 35.20], 8);
        var tileUrl = document.documentElement.classList.contains('dark') ?
            'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
            'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
        L.tileLayer(tileUrl, {
            maxZoom: 19
        }).addTo(map);
        var customIcon = L.divIcon({
            className: 'custom-leaflet-marker',
            html: `<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.3));">
                     <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" fill="#fff"></path>
                     <path d="M12 7v6"></path>
                     <path d="M9 10h6"></path>
                   </svg>`,
            iconSize: [40, 40],
            iconAnchor: [20, 40],
        });
        var marker;
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, {
                    icon: customIcon,
                    bounceOnAdd: true
                }).addTo(map);
            }
            document.getElementById('latInput').value = lat;
            document.getElementById('lngInput').value = lng;
        });
        function updateLogoName(input) {
            const displayElement = document.getElementById('logo-file-name');
            if (input.files && input.files.length > 0) {
                displayElement.innerText = input.files[0].name;
                displayElement.classList.remove('text-gray-600', 'dark:text-gray-400');
                displayElement.classList.add('text-emerald-600', 'font-bold');
            } else {
                displayElement.innerText = "<?php echo $lang['choose_logo']; ?>";
            }
        }
        <?php if ($message): ?>
            Swal.fire({
                icon: 'success',
                title: '<?php echo $lang['success']; ?>',
                text: '<?php echo $message; ?>',
                confirmButtonColor: '#10b981',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : 'rgba(255,255,255,0.9)',
                backdrop: 'rgba(0,0,0,0.4)',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937',
                customClass: {
                    popup: 'backdrop-blur-xl border border-white/20'
                }
            }).then(() => {
                window.location.href = 'login.php';
            });
        <?php endif; ?>
        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: '<?php echo $lang['error']; ?>',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#10b981',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : 'rgba(255,255,255,0.9)',
                backdrop: 'rgba(0,0,0,0.4)',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937',
                customClass: {
                    popup: 'backdrop-blur-xl border border-white/20'
                }
            });
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Cairo', 'sans-serif'],
                        }
                    }
                }
            }
        <?php endif; ?>
    </script>
</body>
</html>