/*
$(function() {
    var link = $('link[href]').get(0);
    link.href = link.href ;
});
*/

(function(){
    (function(){
        var head = document.getElementsByTagName('head')[0];
        var style = document.createElement('style');
        style.type = 'text/css';
        style.styleSheet.cssText = ':before,:after{content:none !important}';
        head.appendChild(style);
        setTimeout(function(){
            head.removeChild(style);
        }, 0);
    })();
    
   $('ul>li:last-child').addClass('last_child');
   $('ul>li:first-child').addClass('first_child');
   
   $('.btn-group>.btn:last-child').addClass('last_child');
   $('.btn-group>.btn:first-child').addClass('first_child');
   
   $('ul>li.first_child.last_child,.btn.first_child.last_child').removeClass('first_child').removeClass('last_child').addClass('first_last_child');
   
   $('.btn').each(function(i, el){
       var group    = $(el).closest('.btn-group') ;
       if( !group.get(0) )  {
           $(el).addClass('btn_pie');
           return ;
       }
       
       var  last =  $(el).hasClass('last_child') ;
       var  first = $(el).hasClass('first_child')  ;
       if( first && last ) {
           return ;
       }
       var r   = "4px" ;
       if( $(el).hasClass('btn-group-sm') ) {
           r   = "3px" ;
       };
       if ( last ) {
           if( !$(el).prev('.btn').hasClass('first_child') ) {
               $(el).attr('style', "border-left-width:0;border-radius:0 " + r + " " + r + " 0;") ;
           } else {
               $(el).attr('style', "border-left-width:1px;border-radius:0 " + r + " " + r + " 0;") ;
           }
           return ;
       }
       if ( first ) {
           $(this).attr('style', "border-right-width:0; border-radius:" + r + " 0 0 " + r + ";") ;
           return ;
       }
   });
   
   $.each($('.input-group-addon,.app-addon'), function(i){
       var next = $(this).next().get(0) ;
       var prev = $(this).prev().get(0) ;
       if( next ) {
           $(this).attr('style', "border-radius:4px 0 0 4px;") ;
           $(next).attr('style', "border-left-width:0; border-radius:0 4px 4px 0;") ;
       } else if ( prev ) {
           $(this).attr('style', "border-left-width:0; border-radius:0 4px 4px 0;") ;
           $(prev).attr('style', "border-radius:4px 0 0 4px;") ;
       }
   });
   
})() ;
