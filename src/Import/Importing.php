<?php

namespace xrow\ImportBundle\src\Import;

use \eZ\Publish\API\Repository\Values\Content\Location;

interface Importing
{
    public function __construct( $location, $ContentType, ImportSource $source, $repository, $processes_to_run );
    public function import( );
    public static function threadedImport( $name=null, $from=0, $to=0, $feed );
    public function validate( );
    public function mapClass( $entry, $contentCreateStruct);
    
}