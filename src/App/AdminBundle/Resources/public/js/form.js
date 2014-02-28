
var FormDynamic = (function(){
    
    var Element = new Klass({
        initialize: function(parent, dom ){
            this.parent = parent ;
            this.name   = $(dom).attr('appform_name') ;
            this.type   = $(dom).attr('appform_type') ;
            this.dom    = $(dom) ;
            parent.elements[ this.name ] = this ;
            if( dom.hasAttribute ('form_dynamic_show_on') ) {
                this.show_on = $.parseJSON( $(dom).attr('form_dynamic_show_on') ) ;
            } else {
                this.show_on = null ;
            }
        } ,
        setup: function() {
            var type1 = {
                radio: function(_this){
                    var es = _this.dom.find('input[type="radio"]') ;
                    es.on('click', function(evt){
                        _this.fireEvent('change') ;
                    });
                    _this.getValue  = function(){
                        var len = es.length ; 
                        for(var i = 0 ; i < len; i-- ) {
                            if( es.get(i).checked ) {
                                return es.get(i).value ;
                            }
                        }
                    };
                },
                html: function(_this) {
                    
                } ,
                textarea: function(_this){
                    
                },
                select: function(_this) {
                    _this.dom.find('select').on('click', function(evt){
                        _this.fireEvent('change') ;
                    });
                }
            };
            var type2 = {
                appworkflow: 'radio' ,
                appentity:'select' ,
                apphtml:'html' ,
                appowner:'select'
            };
    
            if( type2.hasOwnProperty(this.type) ) {
                type1[type2[this.type]](this) ;
            } else if( type1.hasOwnProperty(this.type) ) {
                type1[this.type](this) ;
            } else {
                 // console.log(this.name, this.type ) ;
            }
            if( !this.getValue ) {
                this.getValue   = function(){
                    console.log('unimplement', this.name, this.type );
                };
            }
            
            if( this.show_on ) {
                var show_on_handle = Klass.delay(function(evt){
                    if( _.some(this.show_on, function(and){
                        return _.every(and, function(values, name){
                            var value   = this.parent.elements[name].getValue() ;
                            // console.log( 'myname:', this.name, 'parent name:', name, 'parent values:',  typeof values , values,  'parent value:',  typeof value, value )
                            return _.contains(values, value) ;
                        }, this) ;
                    }, this ) ) {
                        this.dom.removeClass('form-group-hide');
                    } else {
                        this.dom.addClass('form-group-hide');
                    }
                }, this, 50 );
                
                Klass.each(this.show_on, function(and, i ){
                    Klass.each(and, function(value, name ){
                        if( this.parent.elements.hasOwnProperty(name) ) {
                            this.parent.elements[name].addEvent('change', show_on_handle );
                        } else {
                            // ?  no elements 
                            delete( this.show_on[i][name] ) ;
                            // console.log("no property", name );
                        }
                    }, this ) ;
                }, this );
            } 
        } 
    });
    var Class   = new Klass({
        options: {
           url: null 
        } ,
        initialize: function(form, options){
            this.dom    = $(form) ;
            this.setOptions( options ) ;
            this.elements = {} ;
            var klass   = this ;
            $.each(this.dom.find('div[appform_name]'), function(){
                new Element( klass, this ) ;
            }) ;
            $.each(klass.elements, function(name){
                if( klass.elements.hasOwnProperty(name) ) {
                    this.setup() ;
                }
            }); 
            setTimeout(function(){
                $.each(klass.elements, function(name){
                    if( klass.elements.hasOwnProperty(name) ) {
                        this.fireEvent('change') ;
                    }
                }); 
            }, 10 );
        },

        test: function(){
            
        }
    });
    return Class ;
})();

$(function(){
   $.each($('form.form-dynamic'), function(){
       var form = new FormDynamic(this) ; 
   });
   $('.app_form_btn_cancel').click(function(evt){
       var url  = null ;
        _.some($(this).closest('form').find('input[type="hidden"]').toArray(), function(el){
            if( /app_admin_form_referer/.test( $(el).attr('name') ) ) {
                url = $(el).val() ;
                return true ;
            }
        });
        window.location = url ;
    });
});



var FormValidator   = (function(){
    
    var Element = new Klass({
        initialize: function(parent, group, type, elements ){
            this.parent = parent ;
            this.group  = group ;
            this.type   = type ;
            this.elements   = elements ;
        },

        setup: function() {
            if( ! this.elements.length ) {
                return ;
            }
            var iTimer  = null ;
            var klass   = this ;
            $.each(this.elements, function(el) {
                var _this   = $(this) ;
                _this.on("focus",  function(evt){
                    if( iTimer ) {
                       clearTimeout(iTimer) ;
                       iTimer   = null ;
                    }
                });
                _this.on("blur",  function(evt){
                   if( iTimer ) {
                       clearTimeout(iTimer) ;
                   }
                   iTimer = setTimeout(function(){
                       klass.validate(evt, _this);
                   }, 100 ) ;
                });
            })
        },

        setError: function( error ){
            var group   = $(this.group); // .closest('.form-group') ; 
            if( error ) {
                group.addClass('has-error') ;
                var help    = group.find('.help-block') ;
                if( !help.get(0) ) {
                    help    = $('<span></span>') ;
                    help.addClass('help-block');
                    if( this.elements.lenfth ) {
                        help.appendTo( $(this.elements[0] ).closest('div') ) ; 
                    } else if( this._elements.length ) {
                        help.appendTo( $(this._elements.get(0) ).closest('div') ) ; 
                    } else {
                        help.appendTo( group.find('div').get(0) ) ; 
                    }
                }
                help.html( error ) ;
            } else {
                group.find('.help-block').empty() ;
                group.removeClass('has-error') ;
            }
        },
        
        validate: function(evt, input) {
            var data    = this.parent.getJsonData() ;
            data['app_validate_element'] = input.attr('name') ;
            var klass   = this ;
            $.ajax( this.parent.options.url , {
                cache: false ,
                data: data ,
                dataType: "json" ,
                type:  'POST' ,
                complete: function(xhr, status) {
                    if( 'success' == status ) {
                        var o   = xhr.responseJSON ;
                        if( o.error.length ) {
                           klass.setError( o.error.join('<br />') );
                        } else {
                             klass.setError(null) ;
                        }
                    } else {
                       // console.log( status, xhr.responseText ) ;
                    }
                }
            });
        } 
    });
    
    if (Object.hasOwnProperty.call(Array.prototype, 'indexOf')) {
		var $contains = function(array, item) {
			return -1 != array.indexOf(item);
		};
	} else {
		var $contains = function(array, item) {
			for ( var i = array.length; i--;) {
				if (array[i] == item)
					return true;
			}
			return false;
		};
	}
        
    var Class   = new Klass({
        options: {
           url: null ,
           elements: null , 
           skip: null 
        } ,
        initialize: function(form, options){
            this.dom    = $(form) ;
            this.setOptions( options ) ;
            
            if( !this.options.url ) {
                this.options.url    = this.dom.attr('action') ;
            }
            
            var force_elements  = null ;
            if( this.options.elements ) {
                force_elements = $(this.options.elements).toArray() ;
                if( ! force_elements.length ) {
                    force_elements  = null ;
                }
            }
            var skip_elements  = null ;
            if( this.options.skip ) {
                skip_elements = $(this.options.skip).toArray() ;
                if( ! skip_elements.length ) {
                    skip_elements  = null ;
                }
            }
            
            var groups      = this.dom.find('.form-group').toArray() ;
            
            var children    = [] ;
            var last_child  = null ;
            for(var i = 0; i < groups.length; i++ ) {
                var _elements  = $(groups[i]).find('input,email,select,textarea') ;
                var elements   = _elements.toArray() ;
                if( elements.length ) {
                    for(var j = elements.length; j-- ;) {
                        if( force_elements && !$contains(force_elements, elements[j]) ) {
                            elements.splice(j, 1) ;
                        }
                        if( skip_elements && $contains(skip_elements, elements[j])  ) {
                            elements.splice(j, 1) ;
                        }
                    }
                }
                var type    = 'null' ;
                if( elements.length ) {
                    type   = String(elements[0].tagName).toLowerCase() ;
                    if( 'input' == type ) {
                        type    = $(elements[0]).attr('type') ;
                    }
                } 
                
                var child   = null ;
                if( last_child && 'password' == type && type == last_child.type ) {
                    if( /^\w+\[\w+\]\[\w+\]/.test(elements[0].name) && /^\w+\[\w+\]\[\w+\]/.test( last_child.elements[0].name) ) {
                        child   = last_child ;
                        $.each(elements, function(el) {
                            child.elements.push(this) ;
                        });
                    }
                } 
                
                if( !child ){
                    child   = new Element(this, groups[i],type, elements ) ; 
                    child._elements = _elements ;
                    if( elements.length  ) {
                        child.name  = $(elements[0]).attr('name') ;
                    } else if( _elements.length ){
                        child.name  = $(_elements.get(0)).attr('name') ;
                    } else {
                        child.name  = null ;
                    }
                }
                if( elements.length  ) {
                    last_child  = child ;
                }
                children.push( child ) ;
            }
            for(var i = 0; i < children.length; i++ ) {
                var child   = children[i] ;
                child.setup() ;
            }
            this.children   = children ;
        },
        setError: function( name , error ){
            _.some(this.children, function(child, i){
                if( child.name == name ) {
                    child.setError( error ) ;
                    return true ;
                }
            }, this );
        },
        getJsonData: function(){
            var o = {};
            var a = this.dom.serializeArray() ;
            $.each(a, function() {
                if (o[this.name] !== undefined) {
                    if (!o[this.name].push) {
                        o[this.name] = [o[this.name]];
                    }
                    o[this.name].push(this.value || '');
                } else {
                    o[this.name] = this.value || '';
                }
            });
            return o;
        }
    });
    return Class ;
})();

var AppFormFile = new Klass({
    Binds: [ 'onImageLoad', 'onAdjustClick', 'onDeleteClick', 'onDefaultClick' ],
    options: {
        is_image: false ,
        types: 'txt' ,
        max: 10240 ,
        url: null ,
        type_error: null ,
        size_error: null ,
        id: null 
    } ,
    initialize: function(input, options){
        this.setOptions(options);
        this.input  = $(input) ;
        this.box    =  this.input.closest('div') ;
        this.handle  = this.box.find('input[type="file"]') ;
        this.view  = this.box.find('.app_form_file_view') ;
        
        var _this   = this ;
        if( this.options.is_image ){
            this.setupImageTools() ;
        }
        this.onload = true ;
        this.setValue( this.options.value.url, this.options.value.name ) ;
        
        function format_size(fileSizeInBytes) {
                var i = -1;
                var byteUnits = [' kB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
                do {
                    fileSizeInBytes = fileSizeInBytes / 1024;
                    i++;
                } while (fileSizeInBytes > 1024);

                return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
            };
        
        $( this.handle ).fileupload({
            url: _this.options.url ,
            // replaceFileInput: false ,
            dataType: 'json' ,
            paramName: 'attachment' ,
            autoUpload: true ,
            formData: function() {
                 var data = [ 
                        { name:'id', value : _this.options.id } ,
                        { name:'url', value: _this.input.val() } 
                     ] ;
                 return  data ;
            } ,
            error: function( data , e){
                alert( data.responseText, e ) ;
            } ,
            add: function (e, data) {
                var type_reg = new RegExp('\.(' + _this.options.types + ')$'  ,  'i' ) ;
                if( !type_reg.test(data.originalFiles[0]['name'])   ) {
                    _this.showError( _this.options.type_error, {
                        types : _this.options.types.replace(/\|/, ', ')  ,
                        file: data.originalFiles[0]['name'] 
                    });
                } else if(data.originalFiles[0]['size'] > _this.options.max ) {
                    _this.showError( _this.options.size_error, {
                        max_size : format_size(_this.options.max) ,
                        size: format_size(data.originalFiles[0]['size']) 
                    });
                } else {
                    data.submit();
                }
            },
            done: function (e, data) {
                var o   = data.result ;
                if ( o.url ) {
                    _this.setValue( o.url, o.name ) ;
                } else {
                     if( o.error ) {
                        _this.showError(o.error, o.args) ;
                     }
                     console.log( o ) ;
                }
            }
        }) ;
    },
    
    onAdjustClick: function(evt){
        var width   = this.image_real_width ;
        if( width < this.image_box_width ) {
            width   = this.image_box_width ;
        }
        if( width > 976 ) {
            width   = 976 ;
        } else if ( width < 300 ) {
            width   = 300 ;
        }
        var html = '<img id="app_form_image_resize_corp" width="'+ width +'" src="' + this.input.val() + '"/>' ;
        var new_image_crop_percent = null ;
        
       var _this = this ;
        function on_close(){
            _this.image_jcrop_api.destroy() ;
            _this.image_jcrop_api   = null ;
            // console.log('close') ;
        }
        
        var modal   = bootbox.dialog({
            message: html ,
            title: this.file_name ,
            buttons: {
              main: {
                label: "保存",
                className: "btn-primary",
                callback: function() {
                    on_close();
                    if( new_image_crop_percent ) {
                        _this.setImageCropValue( new_image_crop_percent, true ) ;
                    }
                }
              },
              danger: {
                label: "取消",
                className: "btn-danger",
                callback: on_close 
              }
            }, 
            "onEscape": on_close
          });
          var modal_width = width + 40  ;
          if( modal_width < 300 ) modal_width = 300 ;
          modal.find("div.modal-dialog").css('width',  modal_width );
          setTimeout(function(){
              var height  = $('#app_form_image_resize_corp').height() ;
              var p   = _this.image_crop_percent ;
              
              var options   = {
                  setSelect: crop , 
                  aspectRatio: _this.image_ratio ,
                  onSelect: function( _p ){
                      new_image_crop_percent = {
                          'left': _p.x / width ,
                          'top': _p.y / height ,
                          'width': _p.w / width ,
                          'height': _p.h / height  ,
                      } ;
                  },
              };
              
              if( 1 !== p.width || 1 !== p.height  || 0 !== p.top || 0 !== p.left ) {
                  var crop    = [ p.left * width, p.top * height, p.width * width, p.height * height ] ;
                  crop[2]   += crop[0] ;
                  crop[3]   += crop[1] ;
                  options['setSelect']  = crop ;
              }
              
              $('#app_form_image_resize_corp').Jcrop(options, function() {
                    _this.image_jcrop_api = this ;
                    new_image_crop_percent = null ; 
              });
              
          }, 250);
    } ,
    onDeleteClick: function(evt){
        alert('onDeleteClick')
    } ,
    onDefaultClick: function(evt){
        alert('onDefaultClick')
    } ,
    
    onImageLoad: function(evt){
        this.image_real_width   = this.image_element.width() ;
        this.image_real_height  = this.image_element.height() ;
        
        if( !this.options.config.use_crop ) {
            // reset this image size
            var _ratio  = this.image_real_width / this.image_real_height ;
            if( _ratio > this.image_ratio ) { 
                this.image_element.width( this.options.config.width );
            } else {
                this.image_element.height( this.options.config.height  );
            }
            return ;
        }
        
        $( this.image_element ).resizecrop({
            width: this.options.config.width ,
            height: this.options.config.height ,
            vertical:"top"
        });
        var width   = this.image_element.width() ;
        var height  = this.image_element.height() ;
        this.image_box_width   = width ;
        this.image_box_height  = height ;
        
        var _p = this.image_element.position() ;
        if( _p.left < 0 ) {
            _p.left = 0 - _p.left ;
            if( _.top < 0 ) {
                alert('resizecrop error')
            }
        } else if ( _.top < 0 ){
            _p.top = 0 - _p.top ;
            if( _.left < 0 ) {
                alert('resizecrop error')
            }
        }
        var p   = {
            'width': this.options.config.width / width , 
            'height': this.options.config.height / height , 
            'left': Math.round(_p.left) / width , 
            'top': Math.round(_p.top) / width , 
        } ;
        this.setImageCropValue(p) ;
        if( this.onload ) {
              this.onload = false ;
        }
    },
    
    setImageCropValue: function(p, resize ){
        this.image_crop_percent = p ;
        if( this.onload ) {
            if( 1 !== p.width || 1 !== p.height  || 0 !== p.top || 0 !== p.left ) {
                this.input.next().val( JSON.stringify(p) ) ;
            }
        } else {
            this.input.next().val( JSON.stringify(p) ) ;
        }
        if( resize ) {
            var width    = this.options.config.width  / p.width ;
            var height    = this.options.config.height  / p.height ;
            var left   = 0 - width * p.left ;
            var top    = 0 - height * p.top ;
            this.image_element.css({
                'width': width ,
                'height': height ,
                'left': left ,
                'top': top 
            });
        }
    } ,
    setupImageTools: function(){
        this.image_element  = $(this.view).find('img') ;
        this.image_ratio    = this.options.config.width / this.options.config.height ;
        this.image_element.on('load', this.onImageLoad) ;
        this.image_real_width   = 0 ;
        this.image_real_height  = 0 ;
        
        this.image_adjust_handle  = this.box.find('.app_form_image_adjust') ;
        this.image_delete_handle  = this.box.find('.app_form_image_delete') ;
        this.image_default_handle  = this.box.find('.app_form_image_default') ; 
        this.image_adjust_handle.click( this.onAdjustClick );
        this.image_delete_handle.click( this.onDeleteClick );
        this.image_default_handle.click( this.onDefaultClick );
    },
            
    setValue: function( url , name ) {
        
        this.file_name  = name ;
        
        if( this.options.is_image ) {
            if( url ) {
                  this.box.removeClass('app_form_image_hidden') ;
                  this.image_element.attr('src', url ) ;
                  this.image_element.attr('alt', name ) ;
                  this.image_element.css('width', 'auto' ) ;
                  this.image_element.css('height', 'auto' ) ;
                  this.input.val( url ) ;
              } else {
                  this.box.addClass('app_form_image_hidden') ;
                  this.image_element.attr('src', '' ) ;
                  this.image_element.attr('alt', '' ) ;
                  this.input.val( '' ) ;
                  if( this.onload ) {
                        this.onload = false ;
                  }
              }
        } else {
            if( url ) {
                  this.box.addClass('app_form_file_show') ;
                  this.view.attr('href', url ) ;
                  this.view.attr('target', '_blank' ) ;
                  this.view.text(name ) ;
                  this.input.val( url ) ;
            } else {
                  this.box.removeClass('app_form_file_show') ;
                  this.view.attr('href', 'javascript:alert(1)' ) ;
                  this.view.attr('target', '_self' ) ;
                  this.view.text('' ) ;
                  this.input.val( '' ) ;
            }
        }
    } ,
            
    showError: function( error, args ){
        if( args ) {
            for(var key in args ) {
                var reg = new RegExp('\@\{' + key + '\}' ) ;
                error   = error.replace(reg, args[key]) ;
            }
        }
        alert( error )
    } 
}) ;