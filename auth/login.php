<?php
include('../config/database.php');
session_start();
require_once('../includes/lang.php');

$error = "";

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT u.UserID, u.RoleID, u.Password, p.IsApproved
              FROM User u
              LEFT JOIN Pharmacist p ON u.UserID = p.PharmacistID
              WHERE u.Email = '$email'";

    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if ($password === $user['Password']) {
            if ($user['RoleID'] == 3) {
                $error = $lang['err_patient'];
            } elseif ($user['RoleID'] == 2 && $user['IsApproved'] == 0) {
                $error = $lang['err_pending'];
            } else {
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['role_id'] = $user['RoleID'];

                if ($user['RoleID'] == 1) header("Location: ../admin/dashboard.php");
                else header("Location: ../pharmacist/dashboard.php");
                exit();
            }
        } else {
            $error = $lang['err_pass'];
        }
    } else {
        $error = $lang['err_email'];
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" dir="<?php echo $dir; ?>" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark') ? 'dark' : ''; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaSmart - <?php echo $lang['login_title']; ?></title>

    <script src="https://kit.fontawesome.com/804071b851.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>

    <script>
        if (localStorage.getItem('color-theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        }
    </script>

    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* تأثيرات الإضاءة والظلال تاعات الأشكال*/
        .glass-panel {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.1), inset 0 0 0 1px rgba(255, 255, 255, 0.2);
        }

        .dark .glass-panel {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4), inset 0 0 0 1px rgba(255, 255, 255, 0.05);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-teal-50 to-emerald-200 dark:from-slate-900 dark:to-teal-950 flex items-center justify-center relative transition-colors duration-500">

    <!-- ==========================================
            الأشكال الطبية ثلاثية الأبعاد
    ========================================== -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">

        <!-- شكل الكبسولة -->
        <div class="absolute top-10 left-20 w-32 h-64 rounded-full transform rotate-[35deg]
                    bg-gradient-to-b from-emerald-300 to-teal-500 dark:from-emerald-600 dark:to-teal-800
                    shadow-[inset_15px_15px_30px_rgba(255,255,255,0.7),inset_-10px_-10px_30px_rgba(0,0,0,0.2),10px_20px_40px_rgba(20,184,166,0.3)]">
        </div>

        <!-- شكل القرص-->
        <div class="absolute top-1/4 right-20 w-48 h-48 rounded-full transform -rotate-[15deg]
                    bg-gradient-to-tr from-green-200 to-emerald-400 dark:from-green-700 dark:to-emerald-600
                    shadow-[inset_-10px_-10px_30px_rgba(0,0,0,0.15),inset_15px_15px_30px_rgba(255,255,255,0.8),0_20px_40px_rgba(16,185,129,0.2)]">
            <div class="absolute top-1/2 left-4 right-4 h-1 bg-white/40 dark:bg-black/10 rounded-full transform -translate-y-1/2 shadow-[inset_0_1px_2px_rgba(0,0,0,0.1)]"></div>
        </div>

        <!-- شكل الزائد -->
        <div class="absolute bottom-20 left-1/4 w-32 h-32 transform rotate-[15deg] opacity-80">
            <div class="absolute inset-x-10 inset-y-0 rounded-2xl bg-gradient-to-br from-teal-300 to-cyan-500 dark:from-teal-600 dark:to-cyan-800 shadow-[inset_5px_5px_15px_rgba(255,255,255,0.6),inset_-5px_-5px_15px_rgba(0,0,0,0.2)]"></div>
            <div class="absolute inset-y-10 inset-x-0 rounded-2xl bg-gradient-to-br from-teal-300 to-cyan-500 dark:from-teal-600 dark:to-cyan-800 shadow-[inset_5px_5px_15px_rgba(255,255,255,0.6),inset_-5px_-5px_15px_rgba(0,0,0,0.2)]"></div>
        </div>
        <!-- كبسولة صغيرة -->
        <div class="absolute bottom-1/3 right-1/3 w-20 h-40 rounded-full transform -rotate-[40deg] blur-md
                    bg-gradient-to-r from-emerald-400 to-green-300 dark:from-emerald-700 dark:to-green-800
                    shadow-[inset_5px_5px_15px_rgba(255,255,255,0.5)]">
        </div>

    </div>

    <!-- ==========================================
            ازرار التحكم في الثيم واللغة
    ========================================== -->
    <div class="absolute top-6 right-6 flex items-center gap-3 z-50">

        <!-- زر الوضع الليلي/النهاري -->
        <button id="theme-toggle" type="button" class="glass-panel p-3 rounded-2xl text-gray-700 dark:text-white transition-all duration-300 hover:bg-white/40 dark:hover:bg-slate-800/70 hover:shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:-translate-y-1 focus:outline-none flex items-center justify-center group">
            <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5 text-amber-400 transition-transform duration-500 group-hover:rotate-90"></i>
            <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5 text-amber-400 transition-transform duration-500 group-hover:-rotate-12"></i>
        </button>

        <!-- زر تغيير اللغة -->
        <a href="?lang=<?php echo $lang['switch_lang_code']; ?>"
            class="glass-panel text-gray-800 dark:text-white font-bold px-5 py-3 rounded-2xl transition-all duration-300 hover:bg-white/40 dark:hover:bg-slate-800/70 hover:shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:-translate-y-1 flex items-center gap-2 text-sm group">
            <i data-lucide="globe" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 transition-transform duration-500 group-hover:rotate-180"></i>
            <span class="group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors"><?php echo $lang['switch_lang_text']; ?></span>
        </a>

    </div>

    <!-- ==========================================
            صندوق تسجيل الدخول 
    ========================================== -->
    <div class="glass-panel p-10 md:p-12 rounded-[2.5rem] w-full max-w-md z-10 transition-all duration-300 mx-4">

        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white/30 dark:bg-slate-800/50 backdrop-blur-md rounded-3xl mb-5 text-emerald-600 dark:text-emerald-400 shadow-inner border border-white/40 dark:border-white/10">
                <i data-lucide="shield-plus" class="w-10 h-10"></i>
            </div>
            <h2 class="text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-2">PharmaSmart</h2>
            <p class="text-sm font-bold text-gray-600 dark:text-gray-300 opacity-80"><?php echo $lang['login_subtitle']; ?></p>
        </div>

        <form method="POST" class="space-y-6 text-<?php echo ($dir == 'rtl') ? 'right' : 'left'; ?>">

            <div>
                <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-2 ml-1"><?php echo $lang['email']; ?></label>
                <div class="relative">
                    <input type="email" name="email" required placeholder="Email" dir="ltr"
                        class="w-full rtl:pl-4 rtl:pr-14 ltr:pr-4 ltr:pl-14 py-4 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm border border-white/50 dark:border-slate-700 focus:bg-white dark:focus:bg-slate-800 focus:border-emerald-500 rounded-2xl text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 outline-none transition-all shadow-inner">
                    <i data-lucide="mail" class="absolute rtl:right-5 ltr:left-5 top-4 text-emerald-600 dark:text-emerald-400 w-5 h-5 pointer-events-none"></i>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-2 ml-1"><?php echo $lang['password']; ?></label>
                <div class="relative">
                    <input type="password" name="password" required placeholder="••••••••" dir="ltr"
                        class="w-full rtl:pl-4 rtl:pr-14 ltr:pr-4 ltr:pl-14 py-4 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm border border-white/50 dark:border-slate-700 focus:bg-white dark:focus:bg-slate-800 focus:border-emerald-500 rounded-2xl text-sm text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 outline-none transition-all shadow-inner">
                    <i data-lucide="lock" class="absolute rtl:right-5 ltr:left-5 top-4 text-emerald-600 dark:text-emerald-400 w-5 h-5 pointer-events-none"></i>
                </div>
            </div>

            <button type="submit" name="login" class="w-full bg-emerald-600 text-white py-4 rounded-2xl hover:bg-emerald-700 transition-all font-bold text-lg mt-8 shadow-[0_10px_20px_rgba(16,185,129,0.3)] active:scale-[0.98] flex justify-center items-center gap-2 border border-emerald-500/50">
                <?php echo $lang['btn_login']; ?>
                <i data-lucide="<?php echo ($dir == 'rtl') ? 'arrow-left' : 'arrow-right'; ?>" class="w-5 h-5"></i>
            </button>

        </form>

        <div class="mt-8 text-center pt-6 border-t border-white/30 dark:border-slate-700/50">
            <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
                <?php echo $lang['new_account']; ?>
                <a href="register.php" class="text-emerald-700 dark:text-emerald-400 font-black hover:underline mx-1"><?php echo $lang['register_link']; ?></a>
            </p>
        </div>
    </div>

    <!-- رسائل الأخطاء -->
    <?php if ($error != ""): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: '<?php echo $lang['err_title']; ?>',
                    text: '<?php echo $error; ?>',
                    confirmButtonText: '<?php echo $lang['ok_btn']; ?>',
                    confirmButtonColor: '#10b981',
                    allowOutsideClick: false,
                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : 'rgba(255,255,255,0.9)',
                    backdrop: 'rgba(0,0,0,0.4)',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937',
                    customClass: {
                        popup: 'backdrop-blur-xl border border-white/20'
                    }
                });
            });
        </script>
    <?php endif; ?>

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
    </script>


</body>

</html>