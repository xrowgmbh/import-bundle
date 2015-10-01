<?php 
namespace xrow\ImportBundle\src\Import;

use \eZ\Publish\API\Repository\Values\Content\Location;
use \xrow\ImportBundle\src\Import\Core_Thread;

class Process implements Importing
{
    protected $location;
    protected $ContentType;
    protected $source;
    protected $repository;
    protected $processes_to_run;
    protected $importedint;
    
    function __construct( $location, $ContentType, ImportSource $source, $repository, $processes_to_run )
    {
        $this->source = $source;
        $this->location = $location;
        $this->ContentType = $ContentType;
        $this->i_repository = $repository;
        $this->processes_to_run = $processes_to_run;
        $this->importedint=0;

    }
    function validate( )
    {
        return $this->source->validateImport( );
    }
    
    function import( ) {
        #$this->stopReplication();
        $importstartzeit=microtime(true);
        $from=0;
        $till=$this->source->limit();
        
        $this->source->rewind();
        if( $this->source->offset() <= $this->source->count())
        {
            $from=$this->source->offset();
        }
        if( ( $from + $this->source->limit() ) > $this->source->count())
        {
            $till=$this->source->count();
        }
        else
        {
            $till=$from + $this->source->limit();
        }
        
        $process_to_run=$this->processes_to_run;
        
        $i=$from;
        $next=true;
        $compcount=($till - $from);
        #var_dump("total: " . $compcount);
        #var_dump("possiblesteps: " . round($compcount/$process_to_run) + 1);
        #die("sdf");
        $steps=(int)round($compcount/$process_to_run) + 1;
        
        #$till=$till-1;
        
        echo "Erster Datensatz: " . $from . "\n";
        echo "Letzter Datensatz: " . ($till -1) . "\n";
        echo "Insgesamt: " . $compcount . "\n";
        echo "Conc. Processes: " . $process_to_run . "\n";
        echo "Steps: " . $steps . "\n";
        if( $process_to_run > 1 )
        {
            if( !Core_Thread::available() )
            {
                die( 'Threads not supported' );
            }
            $till=$till-1;
            while ($i < $till AND $next)
            {
                $range=array();
                $range["p1"]["from"]=$i;
                
                if( $i+$steps-1 < $till )
                {
                    $range["p1"]["to"]=$i+$steps-1;
                    if( $range["p1"]["to"] == $till )
                    {
                        $next=false;
                    }
                }
                else
                {
                    $range["p1"]["to"]=$till;
                    $next=false;
                }
                
                if( $next )
                {
                    $range["p2"]["from"]=$range["p1"]["to"]+1;
                }
                if( $next AND ($range["p2"]["from"] + $steps -1) < $till )
                {
                    $range["p2"]["to"]=$range["p2"]["from"] + $steps -1;
                    if( $range["p2"]["to"] == $till )
                    {
                        $next=false;
                    }
                }
                else
                {
                    if( $next )
                    {
                        $range["p2"]["to"]=$till;
                    }
                    $next=false;
                }
                if( $process_to_run > 2 )
                {
                    if( $next )
                    {
                        $range["p3"]["from"]=$range["p2"]["to"]+1;
                    }
                    if( $next AND ($range["p3"]["from"] + $steps -1) < $till )
                    {
                        $range["p3"]["to"]=$range["p3"]["from"] + $steps -1;
                        if( $range["p3"]["to"] == $till )
                        {
                            $next=false;
                        }
                    }
                    else
                    {
                        if( $next )
                        {
                            $range["p3"]["to"]=$till;
                        }
                        $next=false;
                    }
                }
                
                if( $process_to_run > 3 )
                {
                    if( $next )
                    {
                        $range["p4"]["from"]=$range["p3"]["to"]+1;
                    }
                    if( $next AND ($range["p4"]["from"] + $steps -1) < $till )
                    {
                        $range["p4"]["to"]=$range["p4"]["from"] + $steps -1;
                        if( $range["p4"]["to"] == $till )
                        {
                            $next=false;
                        }
                    }
                    else
                    {
                        if( $next )
                        {
                            $range["p4"]["to"]=$till;
                        }
                        $next=false;
                    }
                }
                if( $next )
                {
                    if( $process_to_run == 4 )
                    {
                        $i=$range["p4"]["to"]+1;
                    }
                    if( $process_to_run == 3 )
                    {
                        $i=$range["p3"]["to"]+1;
                    }
                    if( $process_to_run == 2 )
                    {
                        $i=$range["p2"]["to"]+1;
                    }
                }
                
                
                $threads=array();
                $importforthissession=0;
                foreach ($range as $it_name => $iThread)
                {
                    $threads[$it_name] = new Core_Thread( array('xrow\EzPublishSolrDocsBundle\src\Import\Process', 'threadedImport'));
                    $threads[$it_name]->start( $it_name, $iThread["from"], $iThread["to"], $this );
                    $importforthissession=$importforthissession + (($iThread["to"] + 1) - $iThread["from"]);
                }

                $stillalive=true;
                while( $stillalive )
                {
                    #sleep(1);
                    $alivecount=0;
                    foreach( $range as $it_name => $threaddeditem )
                    {
                        if($threads[$it_name]->isAlive())
                        {
                            $alivecount++;
                        }
                    }
                    if( $alivecount == 0 )
                    {
                        
                        $this->importedint=($this->importedint+$importforthissession);
                        $stillalive=false;
                    }
                    #else sleep(1);
                    // wait until no thread is alive anymore
                }
                echo "\rImportiert: " . $this->importedint ." von " . $compcount;
            }
        }
        else
        {
            // no Threading
            for ($i = $from; $i < $till; $i++)
            {
                $this->importEntry($this->source->current($i));
                $this->importedint=($this->importedint+1);
                echo "\rImportiert: " . $this->importedint ." von " . $compcount;
            }
        }
        // Implement threading
        
        
        $durationInMilliseconds = (microtime(true) - $importstartzeit) * 1000;
        $timing = number_format($durationInMilliseconds, 3, '.', '') . "ms";
        if($durationInMilliseconds > 1000)
        {
            $timing = number_format($durationInMilliseconds / 1000, 1, '.', '') . "sec";
        }
        echo "\nDauer Import: " . $timing . "\n";
        $average=$compcount / ( $durationInMilliseconds / 1000 );
        echo "Average Objects per second: " . number_format($average, 1, '.', '') . "\n";
        
        #$this->startReplication();
    }
    
    private function importEntry( $entry )
    {
        $repository = $this->i_repository;
        $contentService = $repository->getContentService();
        $contentCreateStruct = $contentService->newContentCreateStruct( $this->ContentType, 'ger-DE' );
        $contentCreateStruct_mapped = self::mapClass($entry, $contentCreateStruct);
        $draft = $contentService->createContent( $contentCreateStruct_mapped, array( $this->location ) );
    }
    
    public static function threadedImport( $name=null, $from=0, $to=0, $feed )
    {
        for ($i = $from; $i <= $to; $i++)
        {
            $feed->importEntry($feed->source->current($i));
        }
        
    }
    
    function mapClass( $entry, $contentCreateStruct )
    {
        #$classDef = array();
        foreach( $this->ContentType->fieldDefinitions as $field )
        {
            if( array_key_exists($field->identifier, $entry) )
            {
                $contentCreateStruct->setField( $field->identifier, $entry[$field->identifier] );
            }
            #$classDef[] = array( "id" => $field->identifier, "required" => $field->isRequired, "ezident" => $field->fieldTypeIdentifier  );
        }
        return $contentCreateStruct;
    }
    
}