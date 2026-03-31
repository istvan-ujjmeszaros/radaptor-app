jQuery(document).ready(function(){
    jQuery('.section-add-icon').click(function(){
        jQuery(this).next().find('input').click();
        //jQuery(this).next().find('.ui-combobox-input').keydown();

        return false;
    });

    jQuery('.sectionTypeSelector select').combobox({
        position: {
            my: "left top",
            at: "left bottom",
            collision: "flip",
            of: function(e){
                return jQuery(e).parent().parent().prev();
            }
        },
        autosubmit: true,
        onshow: function()
        {
            jQuery('.combobox-info').each(function()
            {
                id = jQuery(this).attr('data-val');

                jQuery(this).qtip(
                {
                    position:
                    {
                        at: 'top right', // Position the tooltip above the link
                        my: 'bottom left',
                        viewport: jQuery(window),
                        effect: false
                    },
                    content:
                    {
                        text: '<img width="31" height="31" class="throbber" src="/assets/_common/media/ajax-loader.gif" alt="Loading..." />',
                        ajax:
                        {
                            url: '/?context=section&event=SectionTypeDescription&id=' + id
                        }
                    },
                    style: {
                        classes: 'ui-tooltip-shadow'
                    },
                    show:
                    {
                        solo: true
                    }
                });
            });
        }
    });

})
