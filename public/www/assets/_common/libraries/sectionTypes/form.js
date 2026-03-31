if (typeof(sectiontype) != "object")
    var sectiontype = {};

sectiontype.form = new function()
{

    this.makeTooltips = function()
    {
        $(".form-tooltip[title][data-target]").each(function(){
            var title = $(this).attr('title'),
            target = $(this).attr('data-target');

            $(this).qtip(
            {
                content:
                {
                    text: title
                },
                position:
                {
                    my: 'bottom left',
                    at: 'top left',
                    viewport: $(window), // Keep it on-screen at all times if possible
                    target: $(target),
                    effect: function(api, pos, viewport) {
                        $(this).animate(pos, {
                            duration: 2000,
                            queue: false
                        });
                    }
                },
                api : {
                    beforeShow: function () {
                        $(this.elements.tooltip).css("visibility", "hidden");
                    },
                    onShow: function () {
                        $(this.elements.tooltip).css("visibility", "visible");
                    }
                },
                hide: {
                    fixed: true // Helps to prevent the tooltip from hiding ocassionally when tracking!
                },
                style: 'ui-tooltip-shadow'
            }
            );
        })
    }
}
