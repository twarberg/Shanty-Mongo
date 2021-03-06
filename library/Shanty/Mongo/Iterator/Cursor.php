<?php

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Shanty_Mongo_Iterator_Cursor implements OuterIterator
{
	protected $_cursor = null;
	protected $_config = array();

	public function __construct(MongoCursor $cursor, $config)
	{
		$this->_cursor = $cursor;
		$this->_config = $config;
	}

	/**
	 * Get the inter iterator
	 *
	 * @return MongoCursor
	 */
	public function getInnerIterator()
	{
		return $this->_cursor;
	}

	/**
	 * Get the document class
	 *
	 * @return string
	 */
	public function getDocumentClass()
	{
		return $this->_config['documentClass'];
	}

	/**
	 * Get the document set class
	 *
	 * @return string
	 */
	public function getDocumentSetClass()
	{
		return $this->_config['documentSetClass'];
	}

	/**
     * Get the document set class
     *
     * @return boolean
     */
    public function getDocumentFieldLimiting()
    {
        return $this->_config['fieldLimiting'];
    }

	/**
	 * Export all data
	 *
	 * @return array
	 */
	public function export()
	{
		$this->rewind();
		return iterator_to_array($this->getInnerIterator());
	}

	public function toArray()
	{
		return $this->export();
	}

	/**
	 * Construct a document set from this cursor
	 *
	 * @return Shanty_Mongo_DocumentSet
	 */
	public function makeDocumentSet()
	{
		$config = array();
        $config['fieldLimiting'] = $this->_config['fieldLimiting'];
        $config['requireType'] = $this->_config['requireType'];
		$config['new'] = false;
		$config['hasId'] = false;
		$config['connectionGroup'] = $this->_config['connectionGroup'];
		$config['db'] = $this->_config['db'];
		$config['collection'] = $this->_config['collection'];
		$config['requirementModifiers'] = array(
			Shanty_Mongo_DocumentSet::DYNAMIC_INDEX => array("Document:".$this->getDocumentClass())
		);

		$documentSetClass = $this->getDocumentSetClass();
		return new $documentSetClass($this->export(), $config);
	}

	/**
	 * Get the current value
	 *
	 * @return mixed
	 */
	public function current()
	{
		$data = $this->getInnerIterator()->current();
		if ($data === null) {
		    return null;
		}

		$config                    = array();
        $config['fieldLimiting']   = $this->_config['fieldLimiting'];
        $config['requireType']     = $this->_config['requireType'];
		$config['new']             = false;
		$config['hasKey']          = true;
		$config['connectionGroup'] = $this->_config['connectionGroup'];
		$config['db']              = $this->_config['db'];
		$config['collection']      = $this->_config['collection'];

		$documentClass = $this->getDocumentClass();
        if (!empty($data['_type'][0])) {
            $documentClass = $data['_type'][0];
        }

		return new $documentClass($data, $config);
	}

	public function getNext()
	{
		$this->next();
		return $this->current();
	}

	public function key()
	{
		return $this->getInnerIterator()->key();
	}

	public function next()
	{
        /* start the query */
        $key = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => $this->_config['db'],
                'collection' => $this->_config['collection'],
            ),
            'next'
        );

        /* run the query to the DB */
		$next = $this->getInnerIterator()->next();

        /* end the query */
        Shanty_Mongo::getProfiler()->queryEnd($key);
        return $next;
	}

	public function rewind()
	{

        /* start the query */
        $key = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => $this->_config['db'],
                'collection' => $this->_config['collection'],
            ),
            'rewind'
        );

        /* run the query to the DB */
		$last = $this->getInnerIterator()->rewind();

        /* end the query */
        Shanty_Mongo::getProfiler()->queryEnd($key);
        return $last;
	}

	public function valid()
	{
		return $this->getInnerIterator()->valid();
	}

	public function count($all = false)
	{
		return $this->getInnerIterator()->count($all);
	}

	public function info()
	{
		return $this->getInnerIterator()->info();
	}

	public function __call($method, $arguments)
	{
		// Forward the call to the MongoCursor
		$res = call_user_func_array(array($this->getInnerIterator(), $method), $arguments);

		// Allow chaining
		if ($res instanceof MongoCursor) {
			return $this;
		}

		return $res;
	}
}
