(function($) {
  $.fn.canvasLoader = function(config) {
    $.each(this, function(index, item){
      config = $.extend({
        'shape': 'oval', // 'oval', 'rect', 'roundRect', 'spiral', 'square'
        'color': '#000000',
        'diameter': 16,
        'density': 40,
        'range': 1.3,
        'fps': 24,
        'speed': 2
      }, config);

      // color maps to ... color!
      config.color = rgb2hex($(item).css('color'));

      if (!item.canvasLoader) {
        item.canvasLoader = new CanvasLoader(item);
      }

      item.canvasLoader.setColor(config.color);
      item.canvasLoader.setShape(config.shape);
      item.canvasLoader.setDiameter(config.diameter);
      item.canvasLoader.setDensity(config.density);
      item.canvasLoader.setRange(config.range);
      item.canvasLoader.setSpeed(config.speed);
      item.canvasLoader.setFPS(config.fps);
      item.canvasLoader.show();
    });
  };

  function rgb2hex(rgb){
    if (rgb.substr(0, 1) === '#') {
        return rgb;
    }
    rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    return "#" +
      ("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
      ("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
      ("0" + parseInt(rgb[3],10).toString(16)).slice(-2);
  }
}(jQuery));

