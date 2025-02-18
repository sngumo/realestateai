<?php
namespace Jenga\App\Models\Utilities;

use Jenga\App\Core\App;
use Jenga\App\Project\Core\Project;
use Jenga\App\Models\Utilities\GenericModel;

/**
 * For quick loading of table schema for use
 *
 * @author stanley
 */
class DB {
    
    /**
     * Return the Generic Model
     * @param type $schema
     * @return GenericModel
     */
    public static function schema($schema){
        
        //get the schema instance
        $__schema = App::get($schema);
        
        $model = new GenericModel($__schema);
        $dbal = Project::getDatabaseConnector();
        
        return call_user_func_array([$model, '__map'], [App::get($dbal)]);
    }
}
