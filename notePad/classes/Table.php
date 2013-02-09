<?php
defined( 'SYSPATH' ) or die( 'No direct script access.' );
/**
* notePad table base
*
* easily create a table manually or based on a model.
*
* @author happydemon
* @package happyDemon/notePad
*/
class Table
{
    use NotePad_Order;

    /**
    * Store the model we want to base this table on
    *
    * @var ORM
    */
    protected $_model = null;
    /**
    * Store the primary key's field name
    *
    * @var string
    */
    protected $_pk = '';

    /**
    * Index of the columns
    *
    * @var array
    */
    protected $_columns = array();

    /**
    * Specific column definitions
    *
    * @var array
    */
    protected $_column_definitions = array();
    
    /**
     * Table name
     * @var string
     */
    protected $_name = 'notePad-dataTable';
    
    /**
	 * Buttons that are put at the end of every table row
	 * @var array
     */
    protected $_row_buttons = array(
    	'edit' => array(
    		'icon' => 'edit',
    		'class' => 'warning'
    	),
    	'remove' => array(
    		'icon' => 'remove-sign',
    		'class' => 'danger'
    	)
    );

    /**
    * Table config options
    *
    * @var array
    */
    protected $_config = array(
        'display_length' => 10, // How many records to display - iDisplayLength
        'display_start' => 1, // Which page to start display from - iDisplayStart
        'state_save' => false, // Save the state of the table in a cookie - bStateSave
        'state_duration' => 7200, // How long the state will be saved (2 hours default) - iCookieDuration
        'pagination' => 'bootstrap', // Pagination plugin to use - sPaginationType (bootstrap|fullnumbers|twobutton)
        'class_render' => 'offset3 span6', //set the table's dimensions
        'class_table' => 'table table-hover table-striped',
        'sDom' => null
 	);

    /**
	* Set a table config option
	* 
	* @param string $option The option name
	* @param mixed $value The new config value
     */
    public function cfg($option, $value) {
    	$this->_config[$option] = $value;
    	
    	return $this;
    }
    
    /**
	* Add a button to the row options 
	* 
	* @param string $name The button identifier
	* @param string $location Where to insert the column (end|start|before|after)
    * @param string $relative Column to insert this one $location(before|after)
     */
    public function add_button($name, $icon, $class=null, $location='end', $relative=null) {
    	$val = array('icon' => $icon, 'class' => $class);
    	
    	$this->_place_key($this->_row_buttons, $name, $val, $location, $relative);
    	
    	return $this;
    }
    
    /**
	* Move a button to a different position
	* 
	* @param string $name The neame of the button you wish to move
	* @param string $location Where to insert the column (end|start|before|after)
    * @param string $relative Column to insert this one $location(before|after)
	 */
    public function move_button($name, $location='end', $relative=null) {
    	$this->_place_key($this->_row_buttons, $name, null, $location, $relative);
    	
    	return $this;
    }
    
    /**
    * Create a column definition
    *
    * @param string $name A column alias
    * @param array $options Specific column options
    * @param boolean $sortable Whether the column should be sortable
    * @param boolean $searchable Whether the column is searchable
    * @throws NotePad_Table_Exception
    * @return array
    */
    protected function _define_column( $name, $options, $sortable, $searchable )
    {
        $definition = array(
            'sortable' => $sortable,
            'searchable' => $searchable
            );
        
        // value at the top of the table presenting this column
        $definition['head'] = ( isset( $options['head'] ) ) ? $options['head'] : ucfirst( $name );
        // Column's head class (can be used for spacing the columns)
        $definition['class'] = ( isset( $options['class'] ) ) ? $options['class'] : 'span2';
        // is the html shown on-screen?
        $definition['visible'] = ( isset( $options['visible'] ) ) ? $options['visible'] : true;
        // default content if the column has no value
        $definition['default'] = ( isset( $options['default'] ) ) ? $options['default'] : null;
        // exact width the column should be (20px, 12em, ..)
        $definition['width'] = ( isset( $options['width'] ) ) ? $options['width'] : null;
        // How to start sorting the column
        $definition['sort'] = ( isset( $options['sort'] ) ) ? $options['sort'] : ( $definition['sortable'] ) ? array( 'asc', 'desc' ) : array();
        
        $definition['param'] = (isset($options['param'])) ? $options['param'] : null;
        
        // specify how the column's values should be parsed by mRender
        if ( isset( $options['format'] ) )
        {        	
            // check if custom format is specified as a callable function
            if ( is_callable( $options['format'], false, $format_name ) )
            {
                $format = $options['format'];
            }
            // otherwise check if it's shipped with our table formats
            else if ( is_callable( array( 'NotePad_Table_Formats', $options['format'] ) ) )
            {
                $format = array( 'NotePad_Table_Formats', $options['format'] );
            }
            else
            {
                Throw new NotePad_Table_Exception( 'Column ":name": does not have a valid format (:type).', array(
                        ':name' => $name, ':type' => (string) $options['format']
                        ) );
            }
        }
        // Use no rendering
        else
            $format = null;

        $definition['format'] = $format;
        //print_r($options);
        
        // specifiy how to retrieve the model's value for this column
        if ( isset( $options['retrieve'] ) )
        {
            // a function has been defined, store it to call later ($model is the only parameter)
            if ( is_callable( $options['retrieve'] ) )
            {
                $definition['retrieve'] = $options['retrieve'];
            }
            // call a property or method from the model
            else if ( is_string( $options['retrieve'] ) )
            {
                $definition['retrieve'] = function ( $model ) use ( $name, $options )
                {
                    if ( is_array( $options['retrieve'] ) )
                    {
                        $keys = $options['retrieve'];
                    }
                    else
                    {
                        // Remove starting delimiters and spaces
                        $path = ltrim( $options['retrieve'], ". " );
                        // Split the keys by delimiter
                        $keys = explode( '.', $path );
                    }

                    $value = $model;
                    
                    do
                    {
                        // get the proprty
                        $key = array_shift( $keys );
                        // check if we're calling a method on the model
                        $method = strpos( $key, '()' );
                        // just retrieve he value
                        if ( $method === false )
                        {
                            $value = $value->get( $key );
                        }
                        // call the method
                        else
                        {
                            $method = substr( $key, 0, - 2 );
                            $value = call_user_func( array( $value, $method ) );
                        }
                        // the second we get a string stop looping
                        if ( is_string( $value ) )
                            $keys = false;
                    }
                    while ( $keys );

                    return $value;
                } ;
            }
            else
            {
                Throw new NotePad_Table_Exception( 'Column ":name": has no valid way to retrieve a column\'s value (:given)', array(
                        ':name' => $name, ':given' => (string) $options['retrieve']
                        ) );
            }
        }
        else
        {
            $definition['retrieve'] = function ( $model ) use ( $name )
            {
                return $model->get( $name );
            } ;
        }

        return $definition;
    }

    /**
    * Add a column to the table
    *
    * @param string $name A column alias
    * @param array $options Specific column options
    * @param boolean $sortable Whether the column should be sortable
    * @param boolean $searchable Whether the column is searchable
    * @param string $location Where to insert the column (end|start|before|after)
    * @param string $relative Column to insert this one $location(before|after)
    */
    public function add_column( $name, $options = array(), $sortable = true, $searchable = true, $location = 'end', $relative = null )
    {
        // define the column
        $this->_column_definitions[$name] = $this->_define_column( $name, $options, $sortable, $searchable );
        // place the column
        $this->_columns = $this->_place_value( $this->_columns, $name, $location, $relative );

        return $this;
    }

    /**
    * Move a column to a different position
    * 
    * @param string $name The name of the column you want to move
    * @param string $location Where to insert the column (end|start|before|after)
    * @param string $relative Column to insert this one $location(before|after)
    */
    public function move_column( $name, $position, $relative = null )
    {
        $this->_place_value( $this->_columns, $name, $position, $relative );

        return $this;
    }

	/**
	 * Add a column to the table
	 *
	 * @param string $name A column alias
	 * @param array $options Specific column options
	 * @param boolean $sortable Whether the column should be sortable
	 * @param boolean $searchable Whether the column is searchable
	 * @param string $location Where to insert the column (end|start|before|after)
	 * @param string $relative Column to insert this one $location(before|after)
	 */
    public function column( $name, $option = null, $value = null )
    {
        if ( isset( $this->_column_definitions[$name] ) )
        {
            if ( $option == null )
                return $this->_column_definitions[$name];
            else if ( $value == null )
                return $this->_column_definitions[$name][$option];
            else
                return $this->_column_definitions[$name][$option] = $value;
        }

        return null;
    }

	/**
	 * Register a model
	 */
	public function model(ORM $model) {
		$this->_model = $model;
		$this->_pk = $model->primary_key();
		
		return $this;
	}
	
	protected function _get_format($format, $param) {
		$format = call_user_func($format, $param);
		
		return 'function(data, type, full) { '.$format.'}';
	}
	/**
	 * Builds a dataTable setup config json object
	 * 
	 * @param string $url Base url to send a standardised request to retrieve data records from
	 */
	public function json($url) {
		$columns = array();
		
		foreach($this->_columns as $name) {
			$columns[] = array(
				'bVisible' => $this->_column_definitions[$name]['visible'],	
				'bSortable' => $this->_column_definitions[$name]['sortable'],
				'bSearchable' => $this->_column_definitions[$name]['searchable'],
				'sDefaultContent' => $this->_column_definitions[$name]['default'],
				'sWidth' => $this->_column_definitions[$name]['width'],
				'mrender' => ($this->_column_definitions[$name]['format'] == null) ? null : '%function-'.$name.'%',
			);
		}
		
		$setup = json_encode(array(
			'bProcessing' => true,
			'bServerSide' => true,
			'sAjaxSource' => $url.'records',
			'iDisplayLength' => $this->_config['display_length'],
			'iDisplayStart' => ($this->_config['display_start'] - 1) * $this->_config['display_length'],
			'bStateSave' => $this->_config['state_save'],
			'iCookieDuration' => $this->_config['state_duration'],
			'sPaginationType' => $this->_config['pagination'],
			'aoColumns' => $columns,
			'sDom' => ($this->_config['sDom'] != null) ? $this->_config['sDom'] : "<'row-fluid'<'span4 offset1'l><'span4 pull-right'f>r><'row-fluid'<'".$this->_config['class_render']."'t>><'row-fluid'<'span6'i><'span6'p>>",
		), JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT |JSON_UNESCAPED_SLASHES);
		
		foreach($this->_columns as $name) {
			if($this->_column_definitions[$name]['format'] != null)
				$setup = str_replace('"%function-'.$name.'%"', preg_replace('/\s+/', ' ',$this->_get_format($this->_column_definitions[$name]['format'], $this->_column_definitions[$name]['param'])), $setup);
		}
		return View::factory('notePad/dataTable', array('setup' => $setup, 'id' => Inflector::underscore($this->_name), 'var_name' => Inflector::camelize($this->_name)));
	}
	
	/**
	 * Handle a dataTable ajax request, all that's left is to render the dataTable
	 * 
	 * @return object DataTables
	 */
	public function request() {
		$this->_options();
		$paginate = Paginate::factory($this->_model);
		
		$datatables = DataTables::factory($paginate)->execute();
		
		$result = $datatables->result();

		foreach ($result as $record)
		{
			$row = array();
			
			foreach($this->_columns as $name) {
				$row[] = call_user_func($this->_column_definitions[$name]['retrieve'], $record);
			}
			
			$datatables->add_row($row);
		}
		
		return $datatables;
	}
	
	/**
	 * Generate a basic html table
	 * 
	 * @return string HTML table parsed template
	 */
	public function template() {
		$this->_options();
		
		$heads = array();
		
		foreach($this->_columns as $name) {
			$heads[] = array(
				'class' => $this->_column_definitions[$name]['class'],
				'title' => $this->_column_definitions[$name]['head']
			);
		}
		
		return View::factory('notePad/table', array(
			'heads' => $heads, 
			'head_count' => count($heads),
			'id' => Inflector::underscore($this->_name),
			'title' => $this->_name,
			'class' => $this->_config['class_table']
		));
	}
	
	/**
	 * Add table options at the end if they haven't been already
	 */
	protected function _options() {
		if(!in_array('table_options', $this->_columns))
		{
			$class = (count($this->_row_buttons) > 4) ? 'span3' : 'span2';
			
			$options = array('head' => 'Options', 'class' => $class, 'retrieve' => $this->_pk, 'format' => 'options', 'param' => $this->_row_buttons);
			$this->add_column('table_options', $options, false, false, 'end');
		}
	}
}