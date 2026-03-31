(function($){
    $.fn.bootstrapTransfer = function(options) {
        var settings = $.extend({}, $.fn.bootstrapTransfer.defaults, options);
        var _this;
        /* #=============================================================================== */
        /* # Expose public functions */
        /* #=============================================================================== */
        this.populate = function(input) { _this.populate(input); };
        this.set_values = function(values) { _this.set_values(values); };
        this.get_values = function() { return _this.get_values(); };
        this.initialize_target = function(values) { _this.initialize_target(values); };
        return this.each(function(){
            _this = $(this);
            /* #=============================================================================== */
            /* # Add widget markup */
            /* #=============================================================================== */
            _this.append($.fn.bootstrapTransfer.defaults.template);
            _this.addClass("bootstrap-transfer-container");
            /* #=============================================================================== */
            /* # Initialize internal variables */
            /* #=============================================================================== */
            _this.$filter_input = _this.find('.filter-input');
            _this.$remaining_select = _this.find('select.remaining');
            _this.$target_select = _this.find('select.target');
            _this.$add_btn = _this.find('.selector-add');
            _this.$remove_btn = _this.find('.selector-remove');
            _this.$choose_all_btn = _this.find('.selector-chooseall');
            _this.$clear_all_btn = _this.find('.selector-clearall');
            _this._remaining_list = [];
            _this._target_list = [];
            _this._hidden_list = [];
            /* #=============================================================================== */
            /* # Apply settings */
            /* #=============================================================================== */
            /* target_id */
            if (settings.target_id != '') _this.$target_select.attr('id', settings.target_id);
            /* height */
            _this.find('select.filtered').css('height', settings.height);
            /* target_name */
            if (settings.target_select_name != '') _this.$target_select.attr('name', settings.target_select_name + '[]');
            /* #=============================================================================== */
            /* # Wire internal events */
            /* #=============================================================================== */
            _this.$add_btn.click(function(){
                var selected_items = $.map($('select.remaining option:selected'), function(el, idx) { return el.index; });
                _this.move_elems(selected_items, true);
                return false;
            });
            _this.$remove_btn.click(function(){
                var selected_items = $.map($('select.target option:selected'), function(el, idx) { return el.index; });
                _this.move_elems(selected_items, false);
                return false;
            });
            _this.$choose_all_btn.click(function(){
                _this.move_all(true);
                return false;
            });
            _this.$clear_all_btn.click(function(){
                _this.move_all(false);
                return false;
            });
            _this.$filter_input.keyup(function(){
                _this.update_lists(true);
                return false;
            });
            $('#btn-up').bind('click', function() {
                $('select.target option:selected').each( function() {
                    var newPos = $('select.target option').index(this) - 1;
                    if (newPos > -1) {
                        $('select.target option').eq(newPos).before("<option value='"+$(this).val()+"' selected='selected'>"+$(this).text()+"</option>");
                        $(this).remove();
                        var temp = _this._target_list[newPos + 1];
                        _this._target_list[newPos + 1] =  _this._target_list[newPos];
                        _this._target_list[newPos] = temp;
                    }
                });
            });
            $('#btn-down').bind('click', function() {
                var countOptions = $('select.target option').size();
                $.each($('select.target option:selected').get().reverse(), function() {
                    var newPos = $('select.target option').index(this) + 1;
                    if (newPos < countOptions) {
                        $('select.target option').eq(newPos).after("<option value='"+$(this).val()+"' selected='selected'>"+$(this).text()+"</option>");
                        $(this).remove();
                        var temp = _this._target_list[newPos];
                        _this._target_list[newPos] =  _this._target_list[newPos - 1];
                        _this._target_list[newPos - 1] = temp;
                    }
                });
            });
            /* #=============================================================================== */
            /* # Implement public functions */
            /* #=============================================================================== */
            _this.populate = function(input) {
                // input: [{value:_, content:_}]
                _this.$filter_input.val('');
                for (var i in input) {
                    var e = input[i];
                    _this._remaining_list.push({value:e.value, content:e.content});
                }
                _this.update_lists(true);
            };
            _this.set_values = function(values) {
                _this.move_elems(values, false, true);
            };
            _this.get_values = function(){
                return _this.get_internal(_this.$target_select);
            };
            /* #=============================================================================== */
            /* # Implement private functions */
            /* #=============================================================================== */
            _this.get_internal = function(selector) {
                var res = [];
                selector.find('option').each(function() {
                    res.push($(this).val());
                })
                return res;
            };
            _this.to_dict = function(list) {
                var res = {};
                for (var i in list) res[list[i]] = true;
                return res;
            }
            _this.update_lists = function(force_hilite_off) {
                var old;
                if (!force_hilite_off) {
                    old = [_this.to_dict(_this.get_internal(_this.$remaining_select)),
                        _this.to_dict(_this.get_internal(_this.$target_select))];
                }
                _this.$remaining_select.empty();
                _this.$target_select.empty();
                var lists = [_this._remaining_list, _this._target_list];
                var source = [_this.$remaining_select, _this.$target_select];
                for (var i in lists) {
                    for (var j in lists[i]) {
                        var e = lists[i][j];

                        var selected = '';

                        if (e != undefined){
                            if (!force_hilite_off && settings.hilite_selection && !old[i].hasOwnProperty(e.value.replace('&amp;', '&'))) {
                                selected = 'selected="selected"';
                            }
                            source[i].append('<option ' + selected + 'value="' + e.value + '">' + e.content + '</option>');
                        }
                    }
                }

                _this._hidden_list = [];
                var counter = 0;
                _this.$remaining_select.find('option').each(function() {
                    var inner_html = _this.$filter_input.val();
                    var outer_html = $(this).html();
                    if (inner_html != undefined && outer_html != undefined) {
                        var inner = inner_html.toLowerCase();
                        var outer = outer_html.toLowerCase();
                        if (outer.indexOf(inner) == -1) {
                            _this._hidden_list.push($(this)[0].index + counter);
                            $(this).remove();
                            counter += 1;
                        }
                    }
                });
            };

            // We'll now create a modified remaining items list which removes all hidden items (by search) from consideration.
            _this._remaining_list_minus_hidden = function() {
                var remaining_list_minus_hidden = $.map(_this._remaining_list, function (el, idx) {
                    return ($.inArray(idx, _this._hidden_list) == -1) ? el : false
                });

                remaining_list_minus_hidden = $.grep(remaining_list_minus_hidden, function (value) {
                    return value != false;
                });
                return remaining_list_minus_hidden;
            }

            // We'll also create a list which contains only those items from remaining list, which has been hidden,
            // so that we can restore those to the remaining list once we're done with the move.
            _this._hidden_from_remaining_list = function() {
                var hidden_from_remaining_list = $.map(_this._remaining_list, function (el, idx) {
                    return ($.inArray(idx, _this._hidden_list) == -1) ? false : el
                });

                hidden_from_remaining_list = $.grep(hidden_from_remaining_list, function (value) {
                    return value != false;
                });
                return hidden_from_remaining_list;
            }

            _this.move_elems = function(values, list_selector_boolean) {
                var remaining_list_minus_hidden = _this._remaining_list_minus_hidden();
                var hidden_from_remaining_list = _this._hidden_from_remaining_list();
                var list_to_remove_from = list_selector_boolean ? remaining_list_minus_hidden : _this._target_list;
                var list_to_add_to      = list_selector_boolean ? _this._target_list : _this._remaining_list;

                var counter = 0

                for (var i in values) {
                    val = values[i];
                    selected_value = list_to_remove_from[val - counter];
                    list_to_add_to.push(selected_value);
                    list_to_remove_from = $.grep(list_to_remove_from, function(value) { return JSON.stringify(value) != JSON.stringify(selected_value); });
                    counter += 1
                }

                if(list_selector_boolean) {
                    _this._remaining_list = list_to_remove_from.concat(hidden_from_remaining_list);
                } else {
                    _this._target_list = list_to_remove_from;
                }

                _this.update_lists(false);
            };

            _this.move_all = function(list_selector_boolean) {
                var remaining_list_minus_hidden = _this._remaining_list_minus_hidden();
                var hidden_from_remaining_list = _this._hidden_from_remaining_list();
                var list_to_remove_from = list_selector_boolean ? remaining_list_minus_hidden : _this._target_list;
                var list_to_add_to      = list_selector_boolean ? _this._target_list    : _this._remaining_list;

                $.each(list_to_remove_from, function(idx, el) {list_to_add_to.push(el) });

                if(list_selector_boolean) {
                    _this._remaining_list = hidden_from_remaining_list;
                } else {
                    _this._target_list = [];
                }

                _this.update_lists(false);
            };
            _this.initialize_target = function(values) {
                _this.move_elems(values, false, true);
            };
            _this.data('bootstrapTransfer', _this);
            return _this;
        });
    };
    $.fn.bootstrapTransfer.defaults = {
        'template':
            '<table width="100%" cellspacing="0" cellpadding="0">\
                <tr>\
                    <td width="50%">\
                        <div class="selector-available">\
                            <h2>Available</h2>\
                            <div class="selector-filter">\
                                <table width="100%" border="0">\
                                    <tr>\
                                        <td style="width:14px;">\
                                            <i class="icon-search"></i>\
                                        </td>\
                                        <td>\
                                            <div style="padding-left:10px;">\
                                                <input type="text" class="filter-input">\
                                            </div>\
                                        </td>\
                                    </tr>\
                                </table>\
                            </div>\
                            <select multiple="multiple" class="filtered remaining">\
                            </select>\
                            <a href="#" class="btn selector-chooseall">Add all&nbsp;<i class="icon-forward"></i></a>\
                        </div>\
                    </td>\
                    <td>\
                        <div class="selector-chooser">\
                            <a class="btn selector-add" href="JavaScript:void(0);"><i class="icon-chevron-right"></i></a>\
                            <a class="btn selector-remove" href="JavaScript:void(0);"><i class="icon-chevron-left"></i></a>\
                        </div>\
                    </td>\
                    <td width="50%">\
                        <div class="selector-chosen">\
                            <h2>Selected</h2>\
                            <div class="selector-filter right">\
                                <p>Select from available then click <i class="icon-chevron-right"></i></p></span>\
                            </div>\
                            <select multiple="multiple" class="filtered target rule-engine-multiselect-selected">\
                            </select>\
                            <a href="#" class="btn selector-clearall"><i class="icon-backward"></i>&nbsp;Remove all</a>\
                        </div>\
                    </td>\
                    <td id="bootstrap-transfer-order-buttons">\
                        <a class="btn" href="JavaScript:void(0);" id="btn-up"><i class="icon-chevron-up"></i></a>\
                        <a class="btn" href="JavaScript:void(0);" id="btn-down"><i class="icon-chevron-down"></i></a>\
                    </td>\
                </tr>\
            </table>',
        'height': '10em',
        'hilite_selection': true,
        'target_id': '',
        'target_select_name': 'target'
    }
})(jQuery);
