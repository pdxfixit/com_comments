/* Spam Reports Accordion */
jQuery(function () {
    jQuery("#comments-report-accordion").accordion({
        collapsible: true
    });
    jQuery(".comments-report-state").change(function () {
        submitData(jQuery(this));
    });
    jQuery('#comments-mark-reports-valid').click(function () {
        jQuery(".comments-report-state").each(function () {
            submitData(jQuery(this), 1);
        });
    });
    jQuery('#comments-mark-reports-invalid').click(function () {
        jQuery(".comments-report-state").each(function () {
            submitData(jQuery(this), 2);
        });
    });
});


function submitData(element, newValue) {
    var name = element.attr('name');
    var id = name.replace('[', '-').replace(']', '');
    var currentValue = element.attr('value');
    newValue = (typeof newValue === "undefined") ? currentValue : newValue; //if nothing was passed...

    if (newValue != "" && newValue != currentValue) {
        element.val(newValue);
    }

    // check for an existing element
    if (jQuery('#' + id).length) { // if one exists, update the value
        jQuery('#' + id).val(newValue);
    } else { // if not, create a new element
        jQuery('<input type="hidden" name="' + name + '" id="' + id + '" value="' + newValue + '">').prependTo('form#adminForm');
    }
}
