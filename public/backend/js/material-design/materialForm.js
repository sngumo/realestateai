/**
 * Converts any frm into a material design form
 * @type type
 */
(function($){
    $.fn.materialise = function(){
        
        var label;
        var target;
        var checkMark = "fa-check"; //set the font-awesome icon class
        var iconSize = 'fa-1x'; //set iconSize
        var material = this;
        
        function alterForm(){
    
            //add class
            material.addClass('material');
            
            //loop through all the inputs and selects
            $('input').each(function(){
                
                if($(this).val() !== '' || $(this).val() !== ''){
                    
                    //if not null, set the label to active
                    var label = $('label[for=' + $(this).attr('id') + ']');
                    
                    if(label.closest('div.inputGroup').length === 0){
                        label.addClass('active');
                    }
                }
            });
            
            //each select input
            $('select').each(function(){
                
                //remove the blank option 
                var id = $('select#'+ $(this).attr('id') +' option:contains("- select -")').text('');
                    
                if($(this).val() !== '' || $(this).val() !== ''){
                    
                    //if not null, set the label to active
                    $('label[for=' + $(this).attr('id') + ']').addClass('active');
                }
            });
            
            //alter the rest
            $('select').focus(function(e){
                material.setLabel(e.target);
                material.checkFocused();
            });
            $('select').focusout(function(e){
                material.setLabel(e.target);
                material.checkUnfocused(e.target);
            });
    
            //on focus in
            $('input').focus(function(e){ 
                if($(this).hasClass('dont-materilise') === false){
                    material.setLabel(e.target);
                    material.checkFocused();
                }
            });
            
            //on focus out
            $('input').focusout(function(e){
                if($(this).hasClass('dont-materilise') === false){
                    material.setLabel(e.target);
                    material.checkUnfocused(e.target);
                }
            });
            
            //on focus in
            $('textarea').focus(function(e){ 
                if($(this).hasClass('dont-materilise') === false){
                    material.setLabel(e.target);
                    material.checkFocused(true);
                }
            });
            
            //on focus out
            $('textarea').focusout(function(e){
                if($(this).hasClass('dont-materilise') === false){
                    material.setLabel(e.target);
                    material.checkUnfocused(e.target, true);
                }
            });
        };
        
        this.setLabel = function(target){
            label= $('label[for='+target.id+']');
        };
        
        this.getLabel = function(){
            return label;
        };
        
        this.checkFocused= function(textarea = false){
            material.getLabel().addClass('active','');
            
            if(textarea){
                material.getLabel().addClass('text-area');
            }
        };
        
        this.checkUnfocused= function(target, textarea = false){
            if($(target).val().length === 0){

                material.getLabel().removeClass('active');

                if(textarea){
                    material.getLabel().removeClass('text-area');
                }

                if(material.addCheckMark(target)){
                    material.getLabel().next().remove();
                }
            }else if(!$(material.getLabel()).next().is($(checkMark))){
                material.getLabel().after("<span class='fa "+iconSize+" "+checkMark+"'></span>");
            }
        };
        
        this.addCheckMark = function(){
            if(material.getLabel().next().is($("."+ checkMark +""))){
                return true;
            }else{
                return false;
            }
        };
        
        return this.each(function(){
            alterForm();
        });
    };
}(jQuery));