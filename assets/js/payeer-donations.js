
jQuery(document).ready(function($) {
    // Инициализируем AJAX URL и nonce прямо в скрипте
    var payeerAjaxData = {
        ajaxurl: '<?php echo esc_url( admin_url("admin-ajax.php") ); ?>',
        nonce: '<?php echo esc_attr( wp_create_nonce("payeer-donations-nonce") ); ?>'
    };

    $('#payeerDonationForm').on('submit', function(e) {
        e.preventDefault();

        console.log('Form submission started');

        // Проверка заполнения формы
        var amount = parseFloat($('#donationAmount').val());
        if (isNaN(amount)) {
            alert('<?php esc_html_e("Please enter a valid number for the amount", "payeer-donations"); ?>');
            return false;
        }

        if (amount < 0.01) {
            alert('<?php esc_html_e("Minimum donation amount is 0.01. Please increase your donation.", "payeer-donations"); ?>');
            return false;
        }

        // Собираем данные формы
        var formData = {
            action: 'payeer_process_donation',
            amount: $('#donationAmount').val(),
            currency: $('#donationCurrency').val(),
            _ajax_nonce: payeerAjaxData.nonce
        };

        // Добавляем сообщение, если поле включено
        if ($('#donationMessage').length > 0) {
            formData.message = $('#donationMessage').val();
        }

        console.log('Sending data:', formData);

        // Показываем индикатор загрузки
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalBtnText = $submitBtn.text();
        $submitBtn.text('<?php esc_html_e("Processing...", "payeer-donations"); ?>').prop('disabled', true);

        // Отправляем AJAX запрос
        $.ajax({
            url: payeerAjaxData.ajaxurl,
            type: 'POST',
            data: formData,
            dataType: 'json'
        }).done(function(response) {
            console.log('Server response:', response);

            if (response.success && response.form_data) {
                // Создаем временную форму для редиректа на Payeer
                var $redirectForm = $('<form>', {
                    method: 'post',
                    action: 'https://payeer.com/merchant/',
                    style: 'display:none;'
                });

                $.each(response.form_data, function(key, value) {
                    $('<input>', {
                        type: 'hidden',
                        name: key,
                        value: value
                    }).appendTo($redirectForm);
                });

                $('body').append($redirectForm);
                $redirectForm.submit();
            } else {
                // Разные alert для разных ошибок
                if (response.message && response.message.includes('Merchant ID')) {
                    alert('<?php esc_html_e("Payment system error: Merchant ID not configured. Please contact site administrator.", "payeer-donations"); ?>');
                } else if (response.message && response.message.includes('Secret Key')) {
                    alert('<?php esc_html_e("Payment system error: Secret Key not configured. Please contact site administrator.", "payeer-donations"); ?>');
                } else if (response.message && response.message.includes('currency')) {
                    alert('<?php esc_html_e("Currency error: Selected currency is not supported. Please choose another currency.", "payeer-donations"); ?>');
                } else if (response.message) {
                    // Показываем сообщение об ошибке как есть, если оно есть
                    alert(response.message);
                } else {
                    alert('<?php esc_html_e("Payment processing error. Please try again later.", "payeer-donations"); ?>');
                }
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', status, error);

            // Разные alert для разных ошибок соединения
            if (status === 'timeout') {
                alert('<?php esc_html_e("Connection timeout. Please check your internet connection and try again.", "payeer-donations"); ?>');
            } else if (status === 'error') {
                if (xhr.status === 403) {
                    alert('<?php esc_html_e("Security error: Invalid request. Please refresh the page and try again.", "payeer-donations"); ?>');
                } else if (xhr.status === 500) {
                    alert('<?php esc_html_e("Server error: Please try again later or contact site administrator.", "payeer-donations"); ?>');
                } else {
                    alert('<?php esc_html_e("Connection error. Please try again.", "payeer-donations"); ?>');
                }
            } else {
                alert('<?php esc_html_e("Unexpected error occurred. Please try again.", "payeer-donations"); ?>');
            }
        }).always(function() {
            // Восстанавливаем кнопку
            $submitBtn.text(originalBtnText).prop('disabled', false);
        });

        return false;
    });
});

