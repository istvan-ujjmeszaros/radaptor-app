if (typeof(widgettype) != "object")
    var widgettype = {};

widgettype._ = new function()
{
    var _WindowObjectReference = null;

    this.getIconSrc = function(config, name)
    {
        return config.iconsPath + name + '.png';
    }

    this.openWindow = function(href, windowname)
    {
        if ( _WindowObjectReference == null || _WindowObjectReference.closed )
        {
            _WindowObjectReference = window.open(href, windowname);
        }
        else
        {
            _WindowObjectReference.close();
            _WindowObjectReference = window.open(href, windowname);
        }
        return _WindowObjectReference;
    }

    this.location = function(href)
    {
        window.location=href;
    }

}
