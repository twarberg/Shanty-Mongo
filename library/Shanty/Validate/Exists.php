<?php
class Shanty_Validate_Exists extends Zend_Validate_Abstract
{
    /** Error constants
     */
    const ERROR_RECORD_NOT_FOUND    = 'recordNotFound';

    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::ERROR_RECORD_NOT_FOUND    => 'A record matching "%value%" was not found.',
    );

    /**
     * Model name
     * @var string
     */
    private $_model;

    /**
     * Returns the field name
     *
     * @var string
     */
    private $_field;

    private $_filter;

    /**
     * Returns the model name
     *
     * @return string Model name
     */
    public function getModel ()
    {
        return $this->_model;
    }

    /**
     * Returns the fields
     *
     * @return string Fields
     */
    public function getField ()
    {
        return $this->_field;
    }

    public function getFilter ()
    {
        return $this->_filter;
    }

    /**
     * Sets the model name
     *
     * @param string $model Model name
     */
    public function setModel($model)
    {
        $this->_model = $model;
    }

    /**
     * Sets the field name
     *
     * @param array $field Field name
     */
    public function setField ($field)
    {
      $this->_field = $field;
    }

    public function setFilter($filter)
    {
        $this->_filter = $filter;
    }

    /**
     * Constructor
     *
     * The following option keys are supported:
     * 'model'  => The model to validate against
     * 'fields' => The fields to check for a match
     *
     * @param array|Zend_Config $options Options to use for this validator
     */
    public function __construct($options)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } else if (func_num_args() > 1) {
            $options        = func_get_args();
            $temp['model']  = array_shift($options);
            $temp['field']  = array_shift($options);
            $temp['filter'] = array_shift($options);

            $options = $temp;
        }

        if (!array_key_exists('model', $options)) {
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception('Model option missing!');
        }
        $this->setModel($options['model']);

        if (!array_key_exists('field', $options)) {
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception('Field option missing!');
        }

        $this->setField($options['field']);

        if (!array_key_exists('filter', $options)) {
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception('Filter option missing!');
        }

        $this->setFilter($options['filter']);
    }

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * @param mixed $value Value
     * @param array $context Context
     * @return boolean True if $value is valid in context of $context
     * @throws Zend_Valid_Exception If validation of $value is impossible
     * @see Zend_Validate_Interface::isValid()
     */
    public function isValid($value, $context = null)
    {
        $model = $this->getModel();
        $field = $this->getField();
        $filter = $this->getFilter();
        $query = array();

        switch(strtolower($filter)) {
            case 'int':
                $value = intval($value);
                break;
            case 'mongoid':
                $value = new MongoId($value);
        }

        $query[$field] = $value;

        $record = call_user_func(array($model, 'one'), $query);

        // if object, it's ok
        if ($record) {
            return true;
        }

        $this->_setValue($value);
        $this->_error(self::ERROR_RECORD_NOT_FOUND);

        return false;
    }
}