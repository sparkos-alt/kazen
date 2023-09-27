jQuery(function ($) {

    const plugin = userfeedback_deactivation.plugin;
    const name = userfeedback_deactivation.name;
    const apiUrl = userfeedback_deactivation.api_url;
    const homeUrl = userfeedback_deactivation.home_url;

    let $deactivateLink = $('#the-list').find('[data-slug="' + plugin + '"] span.deactivate a'),
        $overlay = $('#am-deactivate-survey-' + plugin),
        $form = $overlay.find('form'),
        formOpen = false;
    // Plugin listing table deactivate link.
    $deactivateLink.on('click', function (event) {
        event.preventDefault();
        $overlay.css('display', 'table');
        formOpen = true;
        $form.find('.am-deactivate-survey-option:first-of-type input[type=radio]').focus();
    });
    // Survey radio option selected.
    $form.on('change', 'input[type=radio]', function (event) {
        event.preventDefault();
        $form.find('input[type=text], .error').hide();
        $form.find('.am-deactivate-survey-option').removeClass('selected');
        $(this).closest('.am-deactivate-survey-option').addClass('selected').find('input[type=text]').show();
    });
    // Survey Skip & Deactivate.
    $form.on('click', '.am-deactivate-survey-deactivate', function (event) {
        event.preventDefault();
        location.href = $deactivateLink.attr('href');
    });
    // Survey submit.
    $form.submit(function (event) {
        event.preventDefault();
        if (!$form.find('input[type=radio]:checked').val()) {
            $form.find('.am-deactivate-survey-footer').prepend('<span class="error">Please select an option</span>');
            return;
        }
        const data = {
            code: $form.find('.selected input[type=radio]').val(),
            reason: $form.find('.selected .am-deactivate-survey-option-reason').text(),
            details: $form.find('.selected input[type=text]').val(),
            site: homeUrl,
            plugin: name
        }
        const submitSurvey = $.post(apiUrl, data);
        submitSurvey.always(function () {
            location.href = $deactivateLink.attr('href');
        });
    });
    // Exit key closes survey when open.
    $(document).keyup(function (event) {
        if (27 === event.keyCode && formOpen) {
            $overlay.hide();
            formOpen = false;
            $deactivateLink.focus();
        }
    });
});