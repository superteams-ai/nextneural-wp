jQuery(document).ready(function($) {
    const reindexButton = $('#nlcb-reindex-button');
    const reindexLog = $('#nlcb-reindex-log');

    if (reindexButton.length && reindexLog.length) {
        reindexButton.on('click', function() {
            if ($(this).hasClass('disabled')) return;

            const data = {
                action: 'nlcb_reindex_faqs',
                nonce: nlcbAdmin._ajax_nonce
            };

            reindexLog.html('Starting reindexing...<br>');
            reindexButton.addClass('disabled').prop('disabled', true);

            $.post(nlcbAdmin.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        reindexLog.append('<span style="color: green;">' + (response.data || 'Success') + '</span><br>');
                    } else {
                        reindexLog.append('<span style="color: red;">' + (response.data || 'Error') + '</span><br>');
                    }
                })
                .fail(function(xhr, status, error) {
                    reindexLog.append('<span style="color: red;">AJAX Error: ' + (xhr.responseText || status) + '</span><br>');
                })
                .always(function() {
                    reindexButton.removeClass('disabled').prop('disabled', false);
                    reindexLog.append('Reindexing finished.<br>');
                });
        });
    }
}); 