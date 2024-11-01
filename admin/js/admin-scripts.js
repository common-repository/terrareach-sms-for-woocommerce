jQuery(document).ready(function($) {
    $('#terrareach_sms_api_key').on('input', function() {
        var apiKey = $(this).val();
        var isValid = /^tr_prd_[a-z0-9]{32}$/.test(apiKey);

        if (isValid) {
            $(this).removeClass('invalid-api-key').addClass('valid-api-key');
        } else {
            $(this).removeClass('valid-api-key').addClass('invalid-api-key');
        }
    });
});
