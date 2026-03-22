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

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Cairo', 'sans-serif'] }
                }
            }
        }
    </script>
</head>

<body class="bg-white dark:bg-slate-950 text-gray-800 dark:text-gray-200 transition-colors duration-500 overflow-hidden h-screen flex">

    <!-- ========================================== -->
    <!-- 1. القسم التعريفي (1/3 - الهوية الطبية) -->
    <!-- ========================================== -->
    <div class="hidden lg:flex lg:w-1/3 bg-gradient-to-br from-emerald-600 to-teal-800 flex-col justify-between p-12 relative overflow-hidden h-full shadow-2xl z-10">
        
        <!-- الأشكال الطبية ثلاثية الأبعاد (من تصميمك الأصلي محصورة هنا) -->
        <div class="absolute inset-0 pointer-events-none z-0">
            <!-- شكل الكبسولة -->
            <div class="absolute top-10 -left-10 w-32 h-64 rounded-full transform rotate-[35deg] bg-gradient-to-b from-emerald-400 to-teal-500 shadow-[inset_15px_15px_30px_rgba(255,255,255,0.4),inset_-10px_-10px_30px_rgba(0,0,0,0.2),10px_20px_40px_rgba(0,0,0,0.3)] opacity-80"></div>
            <!-- شكل القرص -->
            <div class="absolute bottom-20 -right-10 w-48 h-48 rounded-full transform -rotate-[15deg] bg-gradient-to-tr from-green-300 to-emerald-500 shadow-[inset_-10px_-10px_30px_rgba(0,0,0,0.15),inset_15px_15px_30px_rgba(255,255,255,0.5),0_20px_40px_rgba(0,0,0,0.3)] opacity-90">
                <div class="absolute top-1/2 left-4 right-4 h-1 bg-white/30 rounded-full transform -translate-y-1/2"></div>
            </div>
            <!-- علامة الزائد الطبية -->
            <div class="absolute top-1/2 left-1/2 w-24 h-24 transform -translate-x-1/2 -translate-y-1/2 rotate-[15deg] opacity-20">
                <div class="absolute inset-x-8 inset-y-0 rounded-xl bg-white"></div>
                <div class="absolute inset-y-8 inset-x-0 rounded-xl bg-white"></div>
            </div>
        </div>

        <div class="relative z-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl mb-8 text-white shadow-inner border border-white/30">
                <i data-lucide="shield-plus" class="w-8 h-8"></i>
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight mb-4 leading-tight">PharmaSmart</h1>
            <p class="text-emerald-50 font-bold text-base leading-relaxed opacity-90">
                <?php echo $lang['login_subtitle']; ?>.<br>
                بوابتك الرقمية الموثوقة لإدارة صيدليتك ومخزونك الدوائي باحترافية.
            </p>
        </div>

        <div class="relative z-10">
            <p class="text-emerald-200/70 text-xs font-bold">&copy; <?php echo date('Y'); ?> PharmaSmart. All rights reserved.</p>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. قسم الفورم (2/3 - أبيض ونظيف) -->
    <!-- ========================================== -->
    <div class="w-full lg:w-2/3 flex flex-col relative h-full bg-gray-50 dark:bg-slate-900 overflow-y-auto">
        
        <!-- أزرار التحكم باللغة والثيم -->
        <div class="absolute top-6 rtl:left-6 ltr:right-6 flex items-center gap-3 z-50">
            <button id="theme-toggle" type="button" class="p-2.5 rounded-xl bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-all border border-gray-200 dark:border-slate-700 shadow-sm">
                <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5"></i>
                <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5"></i>
            </button>
            <a href="?lang=<?php echo $lang['switch_lang_code']; ?>" class="px-4 py-2.5 rounded-xl bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-all border border-gray-200 dark:border-slate-700 shadow-sm flex items-center gap-2 font-bold text-sm">
                <i data-lucide="globe" class="w-4 h-4"></i>
                <span><?php echo $lang['switch_lang_text']; ?></span>
            </a>
        </div>

        <!-- الفورم -->
        <div class="flex-1 flex items-center justify-center p-8 w-full">
            <div class="w-full max-w-md bg-white dark:bg-slate-800 p-8 sm:p-10 rounded-[2rem] shadow-xl border border-gray-100 dark:border-slate-700/50">
                
                <!-- لوجو يظهر فقط في الجوال -->
                <div class="lg:hidden inline-flex items-center justify-center w-14 h-14 bg-emerald-600 rounded-2xl mb-6 text-white shadow-lg">
                    <i data-lucide="shield-plus" class="w-7 h-7"></i>
                </div>

                <div class="mb-8 text-<?php echo ($dir == 'rtl') ? 'right' : 'left'; ?>">
                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2"><?php echo $lang['login_title']; ?></h2>
                    <p class="text-gray-500 dark:text-gray-400 font-bold text-sm">أدخل بريدك الإلكتروني وكلمة المرور للدخول.</p>
                </div>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2"><?php echo $lang['email']; ?></label>
                        <div class="relative group">
                            <input type="email" name="email" required placeholder="name@pharma.com" dir="ltr"
                                class="w-full h-12 rtl:pl-4 rtl:pr-11 ltr:pr-4 ltr:pl-11 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 focus:border-emerald-500 dark:focus:border-emerald-500 rounded-xl text-sm text-gray-900 dark:text-white outline-none transition-all font-bold">
                            <i data-lucide="mail" class="absolute top-0 bottom-0 my-auto rtl:right-4 ltr:left-4 text-gray-400 group-focus-within:text-emerald-500 w-5 h-5 transition-colors"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2"><?php echo $lang['password']; ?></label>
                        <div class="relative group">
                            <input type="password" name="password" required placeholder="••••••••" dir="ltr"
                                class="w-full h-12 rtl:pl-4 rtl:pr-11 ltr:pr-4 ltr:pl-11 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-600 focus:border-emerald-500 dark:focus:border-emerald-500 rounded-xl text-sm text-gray-900 dark:text-white outline-none transition-all font-bold">
                            <i data-lucide="lock" class="absolute top-0 bottom-0 my-auto rtl:right-4 ltr:left-4 text-gray-400 group-focus-within:text-emerald-500 w-5 h-5 transition-colors"></i>
                        </div>
                    </div>

                    <button type="submit" name="login" class="w-full h-12 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-all font-black text-base mt-6 shadow-md hover:shadow-lg flex justify-center items-center gap-2">
                        <?php echo $lang['btn_login']; ?>
                        <i data-lucide="<?php echo ($dir == 'rtl') ? 'arrow-left' : 'arrow-right'; ?>" class="w-5 h-5"></i>
                    </button>
                </form>

                <div class="mt-8 text-center text-sm font-bold text-gray-500 dark:text-gray-400">
                    <?php echo $lang['new_account']; ?>
                    <a href="register.php" class="text-emerald-600 dark:text-emerald-400 font-black hover:underline mx-1"><?php echo $lang['register_link']; ?></a>
                </div>

            </div>
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
                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1f2937'
                });
            });
        </script>
    <?php endif; ?>

    <script>
        lucide.createIcons();

        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');

        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            themeToggleLightIcon.classList.remove('hidden');
            document.documentElement.classList.add('dark');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
            document.documentElement.classList.remove('dark');
        }

        var themeToggleBtn = document.getElementById('theme-toggle');
        themeToggleBtn.addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');
            themeToggleLightIcon.classList.toggle('hidden');
            themeToggleDarkIcon.classList.toggle('hidden');

            if (document.documentElement.classList.contains('dark')) {
                localStorage.setItem('color-theme', 'dark');
                document.cookie = "theme=dark; path=/";
            } else {
                localStorage.setItem('color-theme', 'light');
                document.cookie = "theme=light; path=/";
            }
        });
    </script>
</body>
</html>