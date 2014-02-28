var Klass = (function() {
	var $removeOn = function(string) {
		return string.replace(/^on([A-Z])/, function(full, first) {
			return first.toLowerCase();
		});
	};
	var $tryRemoveOn = function(string) {
		if (/^on([A-Z]\w+)$/.test(string)) {
			return String(RegExp.$1).toLowerCase();
		} else {
			return string;
		}
	};
	var $proxy = function(fn, obj) {
		return function() {
			fn.apply(obj, arguments);
		};
	};
	var $extend = function(obj, properties) {
		for ( var p in properties)
			if (Object.hasOwnProperty.call(properties, p)) {
				if (!Object.hasOwnProperty.call(obj, p)) {
					obj[p] = properties[p];
				}
			}
	};
	if (Object.hasOwnProperty.call(Array.prototype, 'indexOf')) {
		var $include = function(array, item) {
			if (-1 == array.indexOf(item))
				array.push(item);
			return array;
		};
		var $contains = function(array, item) {
			return -1 != array.indexOf(item);
		};
	} else {
		var $include = function(array, item) {
			for ( var i = array.length; i--;) {
				if (array[i] == item)
					return array;
			}
			array.push(item);
			return array;
		};
		var $contains = function(array, item) {
			for ( var i = array.length; i--;) {
				if (array[i] == item)
					return true;
			}
			return false;
		};
	}
	var $erase = function(array, item) {
		for ( var i = array.length; i--;) {
			if (array[i] === item)
				array.splice(i, 1);
		}
		return array;
	};
	var $each_array = function(array, fn, bind) {
		for ( var i = 0; i < array.length; i++) {
			fn.apply(bind || array, [ array[i], i ]);
		}
		;
	};
	var $each_object = function(obj, fn, bind) {
		for ( var p in obj)
			if (Object.hasOwnProperty.call(obj, p)) {
				fn.apply(bind || obj, [ obj[p], p ]);
			}
		return obj;
	};
	var $clone = function(obj) {
            if( obj ) {
                if (obj instanceof Array) {
                        var len = obj.length;
                        var _obj = new Array(len);
                        for ( var i = 0; i < len; i++) {
                                _obj[i] = arguments.callee(obj[i]);
                        }
                        ;
                        return _obj;
                } else if (typeof obj == 'object') {
                        var _obj = new Object;
                        for ( var i in obj)
                                if (Object.hasOwnProperty.call(obj, i)) {
                                        _obj[i] = arguments.callee(obj[i]);
                                }
                        ;
                        return _obj;
                }
            }
            return obj;
	};
 
	_Klass.each = function(obj, fn, bind) {
		if (obj instanceof Array) {
			return $each_array(obj, fn, bind);
		} else if (typeof obj == 'object') {
			return $each_object(obj, fn, bind);
		} else {
			throw new Exception('error');
		}
	};
        
	_Klass.delay = function(fn, scope, delay){
            if( ! delay ) delay = 100 ;
            var iTimer  = null ;
            return function(){
                if( iTimer ) {
                    clearTimeout(iTimer) ;
                }
                var args    = arguments ;
                iTimer  = setTimeout(function(){
                    iTimer  = null ;
                    fn.apply(scope, args );
                });
            }
        };
        
	function _Klass(methods) {
		var binds = {};
		if (Object.hasOwnProperty.call(methods, 'Binds')) {
			$each_array(methods['Binds'], function(p) {
				if (Object.hasOwnProperty.call(methods, p)) {
					binds[p] = methods[p];
					delete methods[p];
				}
			});
			delete methods['Binds'];
		}
		var $options = {};
		if (Object.hasOwnProperty.call(methods, 'options')) {
			$options = methods['options'];
			delete methods['options'];
		}
		var $options_events = {};
		$each_object($options, function(fn, type) {
			var _type = $tryRemoveOn(type);
			if (_type != type) {
				$options_events[_type] = $include($options_events[_type] || [], fn) ;
				delete $options[type];
			}
		});
		if (Object.hasOwnProperty.call($options, 'events')) {
			$each_object($options['events'], function(fn, type) {
				type = $removeOn(type);
				$options_events[type] = $include($options_events[type] || [], fn);
			});
			delete $options['events'];
		}
 
		var klass = function() {
			$each_object(binds, function(fn, property) {
				this[property] = $proxy(fn, this);
			}, this);
			this.options = $clone($options);
			$options_initialize = null ;
			this.setOptions = function(options) {
				if (typeof options != 'object') {
					return this;
				}
				var scope_initialize = null ;
				if (Object.hasOwnProperty.call(options, 'initialize')) {
					if (true === $options_initialize) {
						scope_initialize	=  options['initialize'] ;
					} else {
						$options_initialize = options['initialize'];
					}
					delete options['initialize'];
				}
				for ( var p in options)
					if (Object.hasOwnProperty.call(options, p)) {
						var _type = $tryRemoveOn(p);
						if (p != _type) {
							this.addEvent(_type, options[p]);
						} else if (p == 'events') {
							this.addEvents(options[p]);
						} else {
							this.options[p] = options[p];
						}
					}
				if ( scope_initialize ) {
					scope_initialize.call(this) ;
				}
				return this;
			};
 
			var $events = $clone($options_events);
			this.addEvent = function(type, fn) {
				if (!(fn instanceof Function)) {
					throw new Exception('add event need Function argument!');
				}
				type = $removeOn(type);
				$events[type] = $include($events[type] || [], fn);
				return this;
			};
			this.addEvents = function(events) {
				for ( var type in events)
					if (Object.hasOwnProperty.call(events, type)) {
						this.addEvent(type, events[type]);
					}
				return this;
			};
			this.removeEvent = function(type, fn) {
				type = $removeOn(type);
				if (Object.hasOwnProperty.call($events, type)) {
					$erase($events[type], fn);
				}
				return this;
			};
			this.removeEvents = function(type) {
				if (!(type instanceof String)) {
					for (_type in type)
						if (Object.hasOwnProperty.call(type, _type)) {
							this.removeEvent(_type, type[_type]);
						}
					return this;
				}
				type = $removeOn(type);
				if (Object.hasOwnProperty.call($events, type)) {
					delete $events[type];
				}
				return this;
			};
			this.fireEvent = function(type, args) {
				type = $removeOn(type);
                                args  = args || [] ;
				if (Object.hasOwnProperty.call($events, type)) {
					var events = $events[type];
					for ( var i = 0; i < events.length; i++) {
						var fn = events[i];
                                                fn.apply(this, args);
					}
				}
				return this;
			};
 
			this.initialize.apply(this, arguments);
			if ('function' === typeof $options_initialize) {
				$options_initialize.call(this);
			}
			$options_initialize = true;
		};
		for ( var property in methods)
			if (Object.hasOwnProperty.call(methods, property)) {
				klass.prototype[property] = methods[property];
			}
		if (!klass.prototype.initialize)
			klass.prototype.initialize = function() {
			};
		return klass;
	}
	;
	return _Klass;
})();