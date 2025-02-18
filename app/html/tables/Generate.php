<?php
namespace Jenga\App\Html\Tables;

use Jenga\App\Html\Tables\Components\Grid;
use Jenga\App\Html\Tables\Components\Column;

/**
 * Use for quick generation of various table components
 *
 * @author stanley
 */
class Generate {
    
    /**
     * Builds column cell from sent array
     * @param type $columns
     * @return type
     */
    public static function Columns($columns) {
        
        $cols = [];
        foreach ($columns as $key => $column) {
            
            //organise grid
            list($medium, $large, $small) = $column['grid'];
           
            //organise attributes
            $attrs = null;
            if(array_key_exists('attrs', $column)){
                $attrs = $column['attrs'];
            }
            
            $cols[] = Column::cell($key, [
                        Grid::onMediumDevices($medium),
                        Grid::onLargeDevices($large),
                        Grid::onSmallDevices($small)
                    ], $attrs);
        }
        
        return $cols;
    }
}
