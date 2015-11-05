// $.tattler
(function ($) {
    "use strict";

    function tattler(action, val) {
        this['socket'] = null;
        this['ws'] = null;

        this['handlers'] = {};
        this['rooms'] = {};

        this.basicHandlers = {
            'console.log': function (data) {
                console.warn('-------------------------------------------------------------');
                console.warn('Tattler remote: ' + data['message']);
                console.warn('-------------------------------------------------------------');
            },
            'string': function (data) {
                var text;
                if (data['title'] !== undefined) {
                    text = data['title'];
                }
                
                text+=data['message'];

                alert(text);
            },
            'growl': function (data) {
                var opts = {
                    'text': data['message'],
                    'sticky': false
                };
                if (data['message'] == undefined && data['text'] !== undefined) {
                    opts['text'] = data['text'];
                }
                if (data['title'] !== undefined) {
                    opts['title'] = data['title'];
                }

                if (window.Notification && window.Notification.permission == 'granted') {
                    if(opts['title'] !== undefined) {
                        opts['text'] = opts['title']+"\n---------------------------------------\n"+opts['text'];
                    }
                    var notification = new Notification($.trim($('title').text()), {
                        tag: data['handler'],
                        body: opts['text'],
                        icon: location.origin + "/themes/demo/assets/images/october.png"
                    });
                    setTimeout(function(){
                        notification.close();
                    }, 5000);
                } else {

                    $.gritter.add(opts);
                }
            }
        }
        this.addHandler = function (event, fn) {
            if (this.handlerExists(event) == false) {
                this.handlers[event] = fn;
                console.info('Tattler: added handler for event «' + event + '»');
            } else {
                console.error('Tattler: preventing handler creation for event «' + event + '»: already exists. Check your code.')
            }
        };
        this.addRoom = function (room) {
            console.log('Tattler: requesting access to room ' + room);
            var _this = this;
            if (_this.rooms[room] == undefined) {
                _this.rooms[room] = false;
                _this.requestRoomsAccess(function (xhr) {
                    _this.socket.emit('subscribe', room);
                    _this.rooms[room] = true;
                    console.log('Tattler: subscribed to room', room);
                    console.log('Tattler: new rooms listing', _this.rooms);
                });
            }
        };
        this.handlerExists = function (handler) {
            if (this.handlers[handler] === undefined) {
                return false;
            }
            return true;
        };


        // livemessaging handler

        var livemessaging = {
            'add_handler': function (a, b) {
                console.log('lm: add_handler', a)
                return window.tattler.addHandler(a, b);
            },
            'isConnected': function () {
                return true;
            },
            'change_url': function (url) {
                return true;
            },
            'handler_exists': function (a) {
                console.log('lm: handler_exists', a)
                return window.tattler.handlerExists(a);
            },
            'add_namespace': function (a) {
                console.log('lm: add_namespace', a)
                var w = setInterval(function () {
                    if (window.tattler.socket.connected) {
                        window.tattler.addRoom(a);
                        clearInterval(w);
                    }
                }, 500);
                return true;
            },
            'disconnect': function () {
                return true;
            },
            'connect': function () {
                return true;
            }
        };
        var livemessagingSettings = {
            'socket': {
                'conn': true,
                'read': null
            }
        };
        $('<div>').addClass('hidden').attr('id', 'live_messaging').data('live_messaging', livemessaging).data('settings', livemessagingSettings).appendTo('body');
    }

    tattler.prototype = {
        'init': function () {
            var _this = this;

            if (this['ws'] == null) {
                console.info('Tattler: requesting WS url');
                $.getJSON('/_tattler', function (res) {
                    _this['ws'] = res['ws'].replace(/\/$/, "");
                    _this.connect();
                }).error(function () {
                    var err = 'Ошибка при создании сокета: сервер не сообщил адрес для подключения';
                    console.error(err);
                    $.alert(err, null, {title: 'Ошибка Tattler'});
                });
            } else {
                this.connect();
            }
        },
        'connect': function () {
            var _this = this;
            console.info('Tattler: processing socket at ' + this['ws'])

            if (this.socket == null) {
                console.info('Tattler: creating new socket')
                this.socket = io(this['ws']);
                this.socket.on('connect', function () {
                    console.warn('Tattler: connected');
                    $(document).trigger('tattler.connected');
                    _this.requestRoomsAccess(function (xhr) {
                        for (var i in _this.rooms) {
                            if (_this.rooms[i] == false) {
                                _this.socket.emit('subscribe', i);
                                console.log('Tattler: subscribed to room', i);
                                _this.rooms[i] = true;
                            }
                        }
                    });
                });
                this.socket.on('disconnect', function () {
                    console.error('Tattler: disconnected');
                    $(document).trigger('tattler.disconnected');
                    for (var i in _this.rooms) {
                        _this.rooms[i] = false;
                    }
                });


                for (var i in this.basicHandlers) {
                    this.addHandler(i, this.basicHandlers[i]);
                }
                this.socket.on('defaultEvent', function (data) {
                    //console.log('Tattler: incoming...', data);

                    var handler = data['handler'];
                    if (_this.handlerExists(handler)) {
                        if (data['livemessaging'] !== undefined) {
                            data = [data];
                        }
                        _this['handlers'][handler](data);
                    } else {
                        console.error('Tattler: handler ' + handler + ' not found', data);
                    }

                })
            } else {
                console.info('Tattler: reconnecting to socket at ' + this['ws'])
                this.socket.io.uri = url;
                this.socket.connect();
            }
        },
        'requestRoomsAccess': function (callback) {
            var socketId = this.socket.io.engine.id;
            var rooms = Object.keys(this.rooms).join(',');
            console.log('Tattler: requesting access for ' + socketId, rooms)
            $.clickHelper({
                url: '/_tattler',
                data: {'socketId': socketId, 'rooms': rooms},
                ajax: true,
                method: 'post',
                successCallback: function (xhr) {
                    if (typeof callback == 'function') {
                        callback(xhr);
                    }
                },
                errorCallback: function (xhr) {
                    console.error('Tattler: ошибка при запросе доступа к каналам связи', rooms, xhr)
                },
                complete: function () {
                    console.log('Tattler: access request for ' + socketId + ' is complete');
                }

            });
        }
    };

    $.tattler = function () {
        if (window.tattler == undefined) {
            console.info("Tattler: creating socket's stuff...");
            var rev = new tattler();
            rev.init();
            window.tattler = rev;
            return rev;
        }
        return window.tattler;
    };

    $.tattler();
    $(document).on('tattler.connected', function(){
        var rooms = $('script#tattlerJs').data('rooms');
        for(var i in rooms) {
            window.tattler.addRoom(rooms[i]);
        }

    });


})(jQuery);

// $.clickhelper
(function ($) {
    "use strict";

    function ClickHelper(options) {
        //Defaults:
        this.defaults = {
            'ajax': true,
            'async': true,
            'url': location.href,
            'method': 'GET',
            'data': '',
            'dataType': 'JSON',
            'confirm': false,
            'confirmOpts': {},
            'beforeSend': function (xhr) {
            },
            'complete': function () {
            },
            'successCallback': function (xhr, textStatus, XMLHttpRequest) {
            },
            'errorCallback': function (xhr, exception) {
            }
        };
        //Extending options:
        this.opts = $.extend({}, this.defaults, options);
        if (this.opts.ajax !== true) {
            this.opts.ajax = false;
        }

        this.opts.method = this.opts.method.toUpperCase();

        this.allowedMethods = ['GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'OPTIONS', 'TRACE'];

        var method_ok = false;
        for (var i in this.allowedMethods) {
            if (this.allowedMethods[i] === this.opts.method) {
                method_ok = true;
            }
        }
        if (method_ok === false) {
            this.opts.method = 'GET';
            this.opts.ajax = false;
        }


        //Privates:
        this.go = function () {
            location.href = this.opts.url;
        };
        this.ajax = {};
        this.object2string = function (obj) {
            return $.param(obj);
        };

        if (typeof this.opts.data === 'object') {
            this.opts.data = this.object2string(this.opts.data);
        }
    }

    ClickHelper.prototype = {
        "init": function () {
            var _this = this;
            if (_this.opts.ajax === false) {
                if (_this.opts.confirm !== false && _this.opts.confirm !== undefined) {

                    $.confirm(_this.opts.confirm, {}, function () {
                        _this.go();
                    }, function () {
                        if (_this.opts.confirmCancel !== false) {
                            _this.opts.confirmCancel();
                        }
                        return true;
                    });
                    return false;

                }
                return _this.go();
            }
            _this.ajax = {
                type: _this.opts.method,
                url: _this.opts.url,
                async: _this.opts.async,
                dataType: _this.opts.dataType,
                data: _this.opts.data,
                beforeSend: _this.opts.beforeSend,
                success: _this.opts.successCallback,
                error: _this.opts.errorCallback,
                complete: _this.opts.complete
            };
            if (_this.opts.confirm !== false) {
                var confirmOptions, confirmCancelCallback, confirmShowCallback;
                if (_this.opts.confirmOpts['options'] !== undefined) {
                    confirmOptions = _this.opts.confirmOpts;
                } else {
                    confirmOptions = {};
                }
                if (_this.opts.confirmOpts['cancelCallback'] !== undefined) {
                    confirmCancelCallback = _this.opts.confirmOpts.cancelCallback;
                } else {
                    confirmCancelCallback = function () {
                    };
                }

                if (_this.opts.confirmOpts['showCallback'] !== undefined) {
                    confirmShowCallback = _this.opts.confirmOpts.showCallback;
                } else {
                    confirmShowCallback = function () {
                    };
                }

                $.confirm(_this.opts.confirm, confirmOptions, function () {
                    $.ajax(_this.ajax);
                    return true;
                }, function () {
                    confirmCancelCallback.call(this);
                    return true;
                }, function () {
                    confirmShowCallback.call(this)
                });
                return false;
            }

            return $.ajax(_this.ajax);
        }
    };

    $.clickHelper = function (options) {
        var rev = new ClickHelper(options);
        return rev.init();
    };

})(jQuery);