1) Административная часть ( includes/class-pdr-admin.php)
Всё, что связано с админкой:
    Настройки
    Страницы в WP-админке
    Тип свиньи admin_menu, admin_init

2)Фронтенд ( includes/class-pdr-frontend.php)
Всё, что видит пользователь:
    Шорткоды
    Формы
    AJAX-обработка
    Регистрация CSS/JS

3)Обработка платежей ( includes/class-pdr-callbacks.php)
Логика работы с API Payeer:
    Колбэки
    Валидация платежей
    Тип свиньи init, template_redirect
----------------------------------------------------

1) Administrative part ( includes/class-pdr-admin.php)
Everything related to the admin panel:
Settings
Pages in the WP admin panel
Pig type admin_menu, admin_init

2) Frontend ( includes/class-pdr-frontend.php)
Everything the user sees:
Shortcodes
Forms
AJAX processing
CSS/JS registration

3) Payment processing ( includes/class-pdr-callbacks.php)
Payeer API logic:
Callbacks
Payment validation
Pig type init, template_redirect
