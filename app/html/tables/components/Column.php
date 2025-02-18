<?php
namespace Jenga\App\Html\Tables\Components;

use Jenga\App\Html\Tables\Components\Cell;

/**
 * Holds the column information
 *
 * @author stanley
 */
class Column {
    
    protected static $data = [];
    
    /**
     * A column counter
     * @var type 
     */
    private static $_count = 0;
    
    /**
     * Add new column cell
     * @param type $data
     * @param type $grid
     * @param type $attributes
     * @return type
     */
    public static function cell($data, array $grid = [], $attributes = null) {
        
        $count = self::$_count;
        self::$data[$count] = new Cell($data, $attributes, TRUE);
        self::$data[$count]->setGrid($grid);
        
        self::$_count++;
        
        return self::$data[$count];
    }
    
    /**
     * Add the specific column attributes
     * @param array $attrs
     * @return type
     */
    public static function attributes(array $attrs){
        
        $count = self::getCount();
        self::$data[$count]['attributes'] = $attrs;
        
        return self;
    }
    
    /**
     * Gets the column counter
     * @return type
     */
    public static function getCount(){
        return self::$_count;
    }
}
