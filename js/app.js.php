<?php header('Content-Type: application/javascript'); ?>
<?php require_once(__DIR__ . '/../inc/config.php'); ?>
<?php require_once(__DIR__ . '/../inc/config_host.php'); ?>
<?php require_once(__DIR__ . '/../inc/functions.php'); ?>

<?php /* Determine language from parameter. */ ?>
<?php define('LANGUAGE', !empty($_GET['lang']) && in_array($_GET['lang'], array_keys(unserialize(LANGUAGES))) ? $_GET['lang'] : 'en'); ?>

jQuery(document).ready(function () {
    /* Load Modal after user clicked on a download button. */
    jQuery('.download').bind('click', function (e) {
        e.preventDefault();
        var url = jQuery(this).attr('href');

        (function (url) {
            location.href = url;
            jQuery('#afterdownload').load('<?php qe(url('afterdownload')); ?>', null, function() {
                jQuery('#afterdownload').modal('show');
            });
        })(url)
    });

    /* Simple application js enhancement. */
    jQuery('select[name="device-type"]').selectpicker({style: 'btn-primary', menuStyle: 'dropdown-inverse'});
    jQuery('select[name="device-country"]').selectpicker({style: 'btn-primary', menuStyle: 'dropdown-inverse'});

    /* Make collapsable teaser boxes. */
    $('.teaser-box').each(function() {
        if($(this).height() > 450) {
            var more_link = $('<a>', {
                text: '[more]',
                click: (function(desc_div) {
                    var toggle = 'less';
                    return function() {
                        if(toggle == 'more') {
                            $(desc_div).css('max-height', '');
                            toggle = 'less';
                        } else {
                            $(desc_div).css('max-height', '400px');
                            toggle = 'more';
                        }
                        $(this).text('[' + toggle + ']');
                    };
                })(this)
            });

            $(this).after(more_link);
            more_link.click();
        }
    });
});
