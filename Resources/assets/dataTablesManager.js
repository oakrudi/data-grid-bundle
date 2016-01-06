/**
 * This plugin is a wrapper for the DataTables Plugin
 * In order to keep a global accessible instance of each DataTable object created
 * in the site this small jquery extension provides two access modes.
 * The first is used to instantiate a dataTable object and is used in the following manner:
 *      $(selector).dataTablesManager(name,options)  : The options parameter is passed directly to de DataTables object initialization
 *
 *
 * The second is used to access previously created dataTable Objects and can be used like this:
 *
 *      $.dataTablesManager.get(name)               : obtains a dataTableManagerObject
 *      $.dataTablesManager.getAll()                : returns all dataTables objects
 *      $.dataTablesManager.update(name,options)    : updates the table 'name' using the provided options
 *
 */
(function ($) {
    var filters = null;
    var dataTables = [];
    /**
     * When the table is initialized the select input must be update accordingly
     */
    $(document).on('init.dt', function () {
        for (var j = 0; j < dataTables.length; j++) {
            var dt = dataTables[j];
            if (dt.infinite) {
                $(dt.domElem).closest('.dataTables_wrapper').find('select').val(0);
            } else if (dt.count) {
                $(dt.domElem).closest('.dataTables_wrapper').find('select').val(dt.count);
            }
        }
    });

    /**
     * Whenever a table is drawn the
     */
    $(document).on('draw.dt',function() {
        for (var j = 0; j < dataTables.length; j++) {
            var dt = dataTables[j];
            if (dt.infinite && dt.scroll ) {
                $.dataTablesManager.getAll()[0].dt.settings().scroller().scrollToRow(dataTables[j].scroll,false);
            }
        }

    });

    /**
     * When a event slices:table:reload is triggered the table must be refreshed
     */
    $(document).on('slices:table:reload', function (e) {
        for (var j = 0; j < dataTables.length; j++) {
            if( dataTables[j].infinite){
                var numbers = dataTables[j].domElem.closest('.dataTables_wrapper').find('.dataTables_info').text().match(/(\d+,\d+|\d+)/g);
                numbers = $.map(numbers,function(n){
                    return n.replace(/,/g,'');
                });
                dataTables[j].scroll = Math.min.apply(Math,numbers);
            }
            dataTables[j].dt.draw(false);
        }
    });

    /**
     * Before start request set ajax.data attr
     */
    $(document).on('preXhr.dt', function ( e, settings, data ) {
        data.filters = filters;
    });

    /**
     * @param name
     * @param options
     * @returns {$.fn}
     */
    $.fn.dataTablesManager = function (name, options) {
        var dt = $.dataTablesManager.get(name);
        if (dt) {
            dt.dt.destroy();
        } else {
            dt = {};
            dt.domElem = this;
            dt.name = name;
            dt.options = options;
            dt.infinite = false;
            dataTables.push(dt);
        }
        dt.dt = $(this).DataTable(options);
        //set callbacks
        dataTables[0].dt.settings()[0].aoRowCallback.push( {
            "fn": function (nRow, aData, iDisplayIndex, iDisplayIndexFull) {
                if (typeof callbackRows == 'function') {
                    callbackRows(nRow, aData, iDisplayIndex, iDisplayIndexFull);
                }
            }
        });
        dt.dt.on('init', function () {
            $(dt.domElem).closest('.dataTables_wrapper').find('select').change(function () {
                dt.count = $(this).val();
                if ($(this).val() == 0) {
                    $.dataTablesManager.makeInfinite(dt.name);
                } else if (dt.infinite) {
                    $.dataTablesManager.reset(dt.name);
                }
            });
        });
        return this;
    };
    var jExtension = {};
    jExtension.getAll = function () {
        return dataTables;
    };
    jExtension.get = function (name) {
        for (var i = 0; i < dataTables.length; i++) {
            if (dataTables[i].name == name) {
                return dataTables[i];
            }
        }
        return null;
    };
    jExtension.makeInfinite = function (name) {
        var params = {};
        params.scroller = {
            loadingIndicator: true
        };
        params.serverSide = true;
        params.scrollY = '600px';


        var dt = jExtension.get(name);
        dt.infinite = true;
        var newOptions = $.extend({}, params, dt.options);
        $(dt.domElem).dataTablesManager(name, newOptions);
    };

    jExtension.reset = function (name) {
        var dt = jExtension.get(name);
        dt.infinite = false;
        var newOptions = $.extend({}, dt.options, {pageLength: dt.count ? dt.count : 10});
        $(dt.domElem).dataTablesManager(name, newOptions);
    };
    jExtension.reDraw = function (name) {
        var dt = jExtension.get(name);
        var records = $(dt.domElem).closest('.dataTables_wrapper').find('select').val();
        var newOptions = $.extend({}, dt.options, {pageLength: records ? records : 10});
        $(dt.domElem).dataTablesManager(name, newOptions);
    };

    /**
     * Define filters to be used internally by dataTables Strategies
     * @param filtersData
     * @param reloadTable
     */
    jExtension.setFilters = function (filtersData, reloadTable) {
        filters = filtersData;
        if(reloadTable) {
            $(document).trigger('slices:table:reload');
        }
    };

    $.extend({
        dataTablesManager: jExtension
    });
})(jQuery);
