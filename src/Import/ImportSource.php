<?php 

namespace xrow\ImportBundle\src\Import;
use xrow\ImportBundle\src\Import\Sourceable;
use Countable;
use Iterator;
use DOMDocument;

abstract class ImportSource implements Iterator, Countable, Sourceable
{
    protected $_iterations;
    protected $_entries;
    protected $_feed;
    protected $_feedurl;
    protected $title;
    protected $id;
    protected $_offset;
    protected $_limit;
    protected $_contenttypeidentifier;
    
    public function __construct($entries)
    {
        $this->_entries = $entries;
    }
    
    public function current( $optionalRow=null )
    {
        return $this->_entries->item($this->_iterations);
    }
    public function fromIteration( $iteration )
    {
        return $this->_entries->item($iteration);
    }
    public function key ()
    {
        return $this->_iterations;
    }
    public function toKey ( $_iteration )
    {
        return $this->_iterations = $_iteration;
    }
    public function next ()
    {
        return $this->_iterations++;
    }
    public function rewind ()
    {
        return $this->_iterations = 0;
    }
    public function valid ()
    {
        return $this->_iterations < $this->count();
    }
    
    public function count()
    {
        #return $this->_entries->length;
        return count($this->_entries);
    }
    
    public function validateImport( )
    {
        return true;
    }
    
    public function setOffset ( $offset )
    {
        return $this->_offset = $offset;
    }
    
    public function setLimit ( $limit )
    {
        return $this->_limit = $limit;
    }
    
    public function offset ( )
    {
        return $this->_offset;
    }
    
    public function limit ( )
    {
        return $this->_limit;
    }
    
    public function setContentTypeIdentifier ( $id )
    {
        return $this->_contenttypeidentifier = $id;
    }
    
    public function contentTypeIdentifier ( )
    {
        return $this->_contenttypeidentifier;
    }
}