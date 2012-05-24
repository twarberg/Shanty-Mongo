<?php
require_once 'Shanty/Mongo/Exception.php';
require_once 'Shanty/Mongo/Collection.php';
require_once 'Shanty/Mongo/Iterator/Default.php';

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Shanty_Mongo_Profiler extends Zend_Db_Profiler
{
    protected $_message = null;

    public function setEnabled($enable)
    {
        parent::setEnabled($enable);

        if ($this->getEnabled()) {

            if (!$this->_message) {
                $this->_message = new Zend_Wildfire_Plugin_FirePhp_TableMessage('MongoDB');
                $this->_message->setBuffered(true);
                $this->_message->setHeader(array('Database','Collection','Query', 'Fields'));
                $this->_message->setDestroy(true);
                $this->_message->setOption('includeLineNumbers', false);
                Zend_Wildfire_Plugin_FirePhp::getInstance()->send($this->_message);
            }

        } else {

            if ($this->_message) {
                $this->_message->setDestroy(true);
                $this->_message = null;
            }

        }

        return $this;
    }

    public function startQuery($config, $queryType = null)
    {
        if(!$this->_enabled)
            return;

        $string = array();
        foreach($config as $key => $item)
        {
            switch($key)
            {
                case 'database':
                    $string[] = 'Database: '.$item;
                    break;
                case 'collection':
                    $string[] = 'Collection: '.$item;
                    break;
                case 'query':
                    $string[] = 'Query: '.Zend_Debug::dump($item, null, false);
                    break;
                case 'fields':
                    $string[] = 'Fields: '.Zend_Debug::dump($item, null, false);
                    break;
                case 'property':
                    $string[] = 'Property: '.Zend_Debug::dump($item, null, false);
                    break;
                case 'object':
                    $string[] = 'Object: '.Zend_Debug::dump($item, null, false);
                    break;
                case 'document':
                    $string[] = 'Document: '.Zend_Debug::dump($item, null, false);
                    break;
                case 'options':
                    $string[] = 'Options: '.Zend_Debug::dump($item, null, false);
                    break;
                default:
                    $string[] = $key.': '.$item;
            }
        }
        if($this->_message) {
            $this->_message->addRow(array($config['database'], $config['collection'], array_key_exists('query', $config) && !empty($config['query']) ? Zend_JSON::encode($config['query']): '', array_key_exists('fields', $config) && !empty($config['fields']) ? Zend_JSON::encode($config['fields']): 'all'));
        }
        return $this->queryStart(implode(' | ', $string), $queryType);
    }

    public function queryEnd($queryId)
    {
        $state = parent::queryEnd($queryId);

        if (!$this->getEnabled() || $state == self::IGNORED) {
            return;
        }

        $this->_message->setDestroy(false);
    }

    public function setFirePhpEnabled($enabled = true)
    {
        $this->_firePhpEnabled = $enabled;
    }
}
