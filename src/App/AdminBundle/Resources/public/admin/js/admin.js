jQuery(function(){
    $('#form_locale').change(function(evt){
            $(this).closest('form').submit() ;
    });
    
    $('.api_call').each(function(){
        $(this).click(function(){
           var uri = $(this).attr('uri') ;
           alert(uri)
        });
    })
});
