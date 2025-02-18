<?php
namespace Jenga\App\Html\Tables\Components;

/**
 * The grid class holds all the grid behaviors set by Bootstrap
 *
 * @author stanley
 */
class Grid {
    
    /**
     * State the action or class suffix to add to the cell for 
     * small devices with min width of 768px
     * 
     * @param type $action_suffix Available action are 'visible', 'hide' or 'auto'
     * Class Suffixes are any number from 1 to 12
     */
    public static function onSmallDevices($action_suffix) {
        
        if($action_suffix == 'hide'){
            return 'hidden-sm-down';
        }
        elseif($action_suffix == 'visible'){
            return 'hidden-md-up';
        }
        elseif($action_suffix == 'auto'){
            return 'col-sm-auto';
        }
        else{
            return 'col-sm-'.$action_suffix;
        }
    }
    
    /**
     * State the action or class suffix to add to the cell for 
     * medium devices with min width of 992px
     * 
     * @param type $action_suffix Available action are 'visible', 'hide' or 'auto'
     * Class Suffixes are any number from 1 to 12
     */
    public static function onMediumDevices($action_suffix) {
        
        if($action_suffix == 'hide'){
            return 'hidden-md-down';
        }
        elseif($action_suffix == 'visible'){
            return 'hidden-lg-up';
        }
        elseif($action_suffix == 'auto'){
            return 'col-md-auto';
        }
        else{
            return 'col-md-'.$action_suffix;
        }
    }
    
    /**
     * State the action or class suffix to add to the cell for 
     * medium devices with min width of 1200px
     * 
     * @param type $action_suffix Available action are 'visible', 'hide' or 'auto'
     * Class Suffixes are any number from 1 to 12
     */
    public static function onLargeDevices($action_suffix){
        
        if($action_suffix == 'hide'){
            return 'hidden-lg-down';
        }
        elseif($action_suffix == 'visible'){
            return 'hidden-xl-up';
        }
        elseif($action_suffix == 'auto'){
            return 'col-lg-auto';
        }
        else{
            return 'col-lg-'.$action_suffix;
        }
    }
    
    /**
     * State the action or class suffix to add to the cell for 
     * small devices with width of less than 750px
     * 
     * @param type $action_suffix Available action are 'visible' or 'hide' 
     * Class Suffixes are any number from 1 to 12
     */
    public static function onExtraSmall($action_suffix){
        
        if($action_suffix == 'hide'){
            return 'hidden-xs-down';
        }
        elseif($action_suffix == 'visible'){
            return 'hidden-sm-up';
        }
        else{
            return 'col-'.$action_suffix;
        }
    }
    
    /**
     * State the action or class suffix to add to the cell for 
     * medium devices with width larger than 1200px
     * 
     * @param type $action_suffix Available action are 'visible' or 'hide' 
     * Class Suffixes are any number from 1 to 12
     */
    public static function onExtraLarge($action_suffix){
        
        if($action_suffix == 'hide'){
            return 'hidden-xl-up';
        }
        elseif($action_suffix == 'visible'){
            return 'hidden-lg-up';
        }
        else{
            return 'col-xl-'.$action_suffix;
        }
    }
}
