<?php
namespace Jenga\App\Html;

/**
 * Creates a toolbar for each page
 *
 * @author stanley
 */
class ToolBar {
    
    /**
     * The toolbar name
     * @var type 
     */
    protected $name;
    
    /**
     * Toolbar attributes
     * @var type 
     */
    protected $attributes;
    
    /**
     * The internal tools array
     * @var type 
     */
    protected $tools = [];

    public function __construct($name, $attributes = null) {
        $this->name = $name;
        $this->attributes = $attributes;
    }
    
    /**
     * Adds a toolbar tool
     * @param type $name
     * @param type $content
     * @param type $path
     * @param type $attributes
     */
    public function add($name, $content, $path = null, $attributes = null){
        
        //add name and content
        $this->tools[$name] = [
            'content' => $content
        ];
        
        //add path
        if(!is_null($path)){
            $this->tools[$name]['path'] = $path;
        }
        
        //add attributes
        if(!is_null($attributes)){
            $this->tools[$name]['attributes'] = $attributes;
        }
        
        return $this;
    }
    
    /**
     * Open tool link in Bootstrap modal
     * 
     * @param type $target
     * @param type $backdrop
     * @param type $attrs
     * @return $this
     */
    public function modal($target, $backdrop = 'static', $attrs = null){
        
        end($this->tools);
        $name = key($this->tools);
        
        //add to tools
        $this->tools[$name]['modal'] = [
            'data-toggle="modal"',
            'data-target = "'.$target.'"', 
            'data-backdrop = "'.$backdrop.'"'
        ];        
        
        //loop through attributes
        if(!is_null($attrs)){
            foreach($attrs as $attr => $value){
                array_push($this->tools[$name]['modal'], $attr.'="'.$value.'"');
            }
        }
        
        return $this;
    }
    
    /**
     * Create the page toolbar
     */
    public function create() {
        
        //build the tool list
        $toolbar = '<ul ';        
        if(!is_null($this->attributes)){
            $toolbar .= $this->buildToolAttributes();
        }        
        $toolbar .= '>';
        
        //build tools
        foreach($this->tools as $name => $tools){
            
            //start list
            $toolbar .= '<li id="'.$this->name.'-'.$name.'"';
                    
            //tool attrs
            if(array_key_exists('attributes', $tools)){
                $toolbar .= $this->buildToolAttributes($tools['attributes']).' ';
            }
            
            $toolbar .= '>';
            
            //path
            if(array_key_exists('path', $tools)){
                $toolbar .= '<a href="'.$tools['path'].'"';
                
                //add modal
                if(array_key_exists('modal', $tools)){
                    $toolbar .= join(' ', $tools['modal']);
                }
                
                $toolbar .= ' >';
            }
            
            //add content
            if(array_key_exists('content', $tools)){
                $toolbar .= $tools['content'];
            }
            
            //close path
            if(array_key_exists('path', $tools)){
                $toolbar .= '</a>';
            }
            
            //close list
            $toolbar .= '</li>';
        }
        
        //close list
        $toolbar .= '</ul>';
        
        return $toolbar;
    }
    
    /**
     * Build tool attributes
     * 
     * @param type $attrlist
     * @return string
     */
    protected function buildToolAttributes($attrlist = null){
        
        if(is_null($attrlist)){
            $attrlist = $this->attributes;
        }
        
        $attrs = '';
        foreach($attrlist as $name => $attributes){
            $attrs .= $name.'="'.$attributes.'"';
        }
        
        return $attrs;
    }
}
