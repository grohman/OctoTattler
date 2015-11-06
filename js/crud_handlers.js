var ocCrudHandlers = function () {

    "use strict";

    var desktopNotifications = false;
    if (window.Notification !== undefined) {
        Notification.requestPermission(function (result) {
            desktopNotifications = result
        });
    }

    var notifyAndHighlight = function (data, type, callback) {
        if (window['debugCrud'] !== undefined) {
            console.info('CRUD: ' + type, data, 'callback: ' + callback)
        }
        var growlRows = [];
        var rowMessage = [];
        var rowId = data['row_id'];
        var rowKey = data['row_key'];
        var translatedKey = data['columns'][rowKey];
        var col = $("table tbody tr:first td[data-title='" + translatedKey + "']");
        var colIndex = col.index();
        var $row = $("table tbody tr td:eq(" + colIndex + "):contains('" + rowId + "')").parent();
        if ($row.length) {
            $row.addClass('danger');
            setTimeout(function () {
                $row.removeClass('danger');
            }, 1500);
        }
        var n = 0;
        var tooManyCols = false;
        for (var i in data['columns']) {
            if (n < 5) {
                if (data['row_data'][i] !== undefined) {
                    rowMessage.push(data['columns'][i] + ': ' + data['row_data'][i]);
                    n++;
                }
            } else {
                tooManyCols = true;
                break;
            }
        }
        var resultMessage = rowMessage.join(', ');
        if (tooManyCols == true) {
            resultMessage += '...';
        }
        growlRows.push(resultMessage);


        var growlOpts = {
            'title': '',
            'text': '',
            'sticky': false
        };
        if (type == 'update') {
            growlOpts['title'] = 'Обновлена запись';
        } else if (type == 'create') {
            growlOpts['title'] = 'Добавлена запись';
        } else if (type == 'delete') {
            growlOpts['title'] = 'Удалена запись';
        }

        var author = 'Автор: ' + data['by']['name'];

        for (var i in growlRows) {
            growlOpts['text'] = growlRows.join('<br>')
        }

        if (desktopNotifications && desktopNotifications !== 'denied') {
            var notification = new Notification($.trim($('title').text()), {
                tag: type,
                body: growlOpts['title'] + "\n" + author + "\n" + growlOpts['text'],
                icon: location.origin + "/themes/demo/assets/images/october.png"
            });
            setTimeout(function(){
                notification.close();
            }, 5000);
        } else {
            growlOpts['text'] = author + '<br>' + growlOpts['text'];
            $.gritter.add(growlOpts);
        }

        if (typeof callback == 'function') {
            callback.call(this);
        }
    };

    var updateTable = function (data, type, callback) {
        if ($('table').length != 0 && $('form').length == 0) {
            $('table').request('list::onRefresh', {
                complete: function () {
                    if (typeof callback == 'function') {
                        callback.call(this);
                    } else {
                        notifyAndHighlight(data, type);
                    }
                }
            });
        } else if (typeof callback == 'function') {
            callback.call(this);
        } else {
            notifyAndHighlight(data, type);
        }
    };

    this.crud_create = function (data) {
        updateTable(data, 'create')
    };
    this.crud_update = function (data) {
        updateTable(data, 'update')
    };
    this.crud_delete = function (data) {
        notifyAndHighlight(data, 'delete', function () {
            updateTable(data, 'delete', function () {
            });
        });
    };
};

if (window.tattler !== undefined) {
    var process = new ocCrudHandlers();
    window.tattler.addHandler('crud_create', function (data) {
        process.crud_create(data);
    });
    window.tattler.addHandler('crud_update', function (data) {
        process.crud_update(data);
    });
    window.tattler.addHandler('crud_delete', function (data) {
        process.crud_delete(data);
    });
}