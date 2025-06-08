jQuery(document).ready(function($) {
    $('#payeerDonationForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $button = $form.find('.donate-button');
        var originalBtnText = $button.text();

        // Показываем индикатор загрузки
        $button.prop('disabled', true).text(payeerDonations.processingText);

        // Валидация суммы
        var amount = parseFloat($('#donationAmount').val());
        if (isNaN(amount) || amount < 0.01) {
            alert(payeerDonations.minAmountError);
            $button.text(originalBtnText).prop('disabled', false);
            return false;
        }

        // Подготовка данных
        var formData = {
            action: 'payeer_process_donation',
            amount: amount,
            currency: $('#donationCurrency').val(),
            _ajax_nonce: payeerDonations.nonce
        };

        // Добавляем сообщение, если поле есть
        if ($('#donationMessage').length > 0) {
            formData.message = $('#donationMessage').val();
        }

        // Отправка AJAX
        $.ajax({
            url: payeerDonations.ajaxurl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 15000
        }).done(function(response) {
            if (response.success && response.form_data) {
                // Создаем форму для редиректа
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
                alert(response.message || payeerDonations.genericError);
            }
        }).fail(function(xhr, status, error) {
            var errorMsg = status === 'timeout' ? payeerDonations.timeoutError :
                         xhr.status === 403 ? payeerDonations.securityError :
                         xhr.status === 500 ? payeerDonations.serverError :
                         payeerDonations.connectionError;
            alert(errorMsg);
        }).always(function() {
            $button.text(originalBtnText).prop('disabled', false);
        });

        return false;
    });
});
