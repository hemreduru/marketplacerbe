<?php

return [
    // Login page
    'sign_in' => 'Giriş Yap',
    'sign_up' => 'Kayıt Ol',
    'sign_out' => 'Çıkış Yap',
    'login_title' => 'Giriş Yap',
    'register_title' => 'Kayıt Ol',
    'login_subtitle' => 'Marketplace Yönetim Paneli',
    'register_subtitle' => 'Yeni hesap oluştur',

    // Form fields
    'email' => 'E-posta',
    'password' => 'Şifre',
    'password_confirmation' => 'Şifre Tekrar',
    'name' => 'Ad Soyad',
    'username' => 'Kullanıcı Adı',
    'remember_me' => 'Beni Hatırla',
    'forgot_password' => 'Şifremi Unuttum?',

    // Messages
    'login_failed' => 'Giriş başarısız oldu',
    'registration_failed' => 'Kayıt başarısız oldu',
    'login_success' => 'Giriş başarılı! Yönlendiriliyorsunuz...',
    'register_success' => 'Kayıt başarılı! Yönlendiriliyorsunuz...',
    'logout_success' => 'Çıkış başarılı',
    'error_occurred' => 'Bir hata oluştu. Lütfen tekrar deneyin.',

    // Links
    'not_member_yet' => 'Henüz üye değil misiniz?',
    'already_member' => 'Zaten üye misiniz?',
    'back_to_login' => 'Girişe dön',

    // Password reset
    'forgot_password_title' => 'Şifremi Sıfırla',
    'forgot_password_subtitle' => 'E-posta adresinizi girin, sıfırlama bağlantısı göndereceğiz.',
    'send_reset_link' => 'Sıfırlama Bağlantısı Gönder',
    'reset_link_sent' => 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.',
    'new_password' => 'Yeni Şifre',
    'new_password_confirm' => 'Yeni Şifre (Tekrar)',
    'reset_password_title' => 'Yeni Şifre Belirle',
    'reset_password_btn' => 'Şifremi Güncelle',
    'password_reset_success' => 'Şifreniz başarıyla güncellendi.',
    'error' => 'Hata',

    // Two-factor authentication (TOTP)
    'failed' => 'Giriş bilgileri hatalı.',
    'throttle' => 'Çok fazla giriş denemesi. Lütfen :seconds saniye sonra tekrar deneyin.',
    'two_factor' => [
        'challenge_title' => 'İki Adımlı Doğrulama',
        'challenge_subtitle' => 'Authenticator uygulamanızdaki 6 haneli kodu girin.',
        'verify_button' => 'Doğrula',
        'recovery_hint' => 'Telefonunuza erişiminiz yoksa kurtarma kodlarınızdan birini de girebilirsiniz.',
        'setup_title' => 'İki Adımlı Doğrulama Kur',
        'setup_intro' => 'QR kodu Google Authenticator, Authy veya 1Password gibi bir uygulama ile tarayın. Onaylamak için ürettiği 6 haneli kodu girin.',
        'manage_title' => 'İki Adımlı Doğrulama',
        'manual_code_label' => 'Manuel Kod',
        'code_label' => 'Doğrulama Kodu',
        'confirm_button' => 'Aktifleştir',
        'invalid_code' => 'Doğrulama kodu hatalı veya süresi geçmiş.',
        'enabled' => 'İki adımlı doğrulama etkinleştirildi.',
        'disabled' => 'İki adımlı doğrulama devre dışı bırakıldı.',
        'recovery_codes_title' => 'Kurtarma Kodları',
        'recovery_codes_intro' => 'Her kod tek kullanımlıktır. Güvenli bir yerde saklayın.',
        'disable_title' => 'Devre Dışı Bırak',
        'disable_intro' => 'Devre dışı bırakmak için parolanızı tekrar girin.',
        'disable_button' => 'İki Adımlı Doğrulamayı Kapat',
    ],
];
