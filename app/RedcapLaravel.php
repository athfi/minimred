<?php

namespace App;

use App\Models\Project;
use IU\PHPCap\RedCapProject;

class RedcapLaravel extends RedCapProject
{
    public $recordId;

    public $metadata;

    public function __construct( Project $project,
                                         $sslVerify = false,
                                         $caCertificateFile = null,
                                         $errorHandler = null,
                                         $connection = null )
    {
        $apiUrl = $project->redcap_url;
        $superToken = $project->redcap_token;
        parent::__construct( $apiUrl, $superToken, $sslVerify, $caCertificateFile, $errorHandler, $connection );

        $this->metadata = $this->getMetadata();

        $this->recordId = $this->getRecordID();


    }

    // Get REDCap metadata and mapped to field name
    public function getMetadata()
    {
        $metadata = collect( $this->exportMetadata() )
            ->mapWithKeys( function ( $item ) {
                $field_with_choices = [ 'checkbox', 'radio', 'dropdown' ];
                // convert the choices to array
                if ( in_array( $item[ 'field_type' ], $field_with_choices )
                    && $item[ 'select_choices_or_calculations' ] ) {
                    $item[ 'select_choices_or_calculations' ] =
                        $this->splitChoices( $item[ 'select_choices_or_calculations' ] );
                }
                return [ $item[ 'field_name' ] => $item ];
            } );
        return ( $metadata );
    }

    /**
     * Get list of radio filed form metadata
     * @return \Illuminate\Support\Collection
     */
    public function getRadioField(){
        return $this->metadata->filter(function ($meta){
            return $meta['field_type'] == 'radio';
        });
    }

    // Convert REEDCap choices to arrau
    private function splitChoices( $choices )
    {
        // get all choices
        $choices = explode( ' | ', $choices );
        // maps as key => label
        $list_choice = [];
        foreach ( $choices as $choice ) {
            list( $key, $label ) = preg_split( "/, /", $choice, 2 );
            $list_choice[ $key ] = $label;
        }
        return $list_choice;
    }

    // get REDCap field name that hold record ID data
    public function getRecordID()
    {
        return array_key_first( $this->metadata->toArray() );
    }

    public function records(  $recordIds = null, $fields = null)
    {
        $format = 'php';
        $type = 'flat';
        $forms = null;
        $events = null;
        $filterLogic = null;
        $rawOrLabel = 'raw';
        $rawOrLabelHeaders = 'raw';
        $exportCheckboxLabel = false;
        $exportSurveyFields = false;
        $exportDataAccessGroups = true;
        $dateRangeBegin = null;
        $dateRangeEnd = null;
        $csvDelimiter = ',';
        $decimalCharacter = null;

        return $this->exportRecords(
            $format,
            $type,
            $recordIds,
            $fields,
            $forms,
            $events,
            $filterLogic,
            $rawOrLabel,
            $rawOrLabelHeaders,
            $exportCheckboxLabel,
            $exportSurveyFields,
            $exportDataAccessGroups,
            $dateRangeBegin,
            $dateRangeEnd,
            $csvDelimiter,
            $decimalCharacter
        );
    }

    public function record( $recordId, $fields = null )
    {
        return collect( $this->records( [ $recordId ], $fields ) )
            ->mapWithKeys( $this->handleMultiChoices() )
            ->first();
    }

    public function allRecords( $fields = null )
    {
        return collect( $this->records( null, $fields ) );
    }

    /**
     * combine the multiple choices field to array
     * @return \Closure
     */
    private function handleMultiChoices(): \Closure
    {
        return function ( $item ) {
            $data = [];

            foreach ( $item as $key => $label ) {
                // if field name contain '__', handle as mulitple choices field
                $fields = preg_split( "/___/", $key, 2 );
                if ( sizeof( $fields ) === 2 ) {
                    // combine the field data to array: fieldName.key.Label
                    $data[ $fields[ 0 ] ] [ $fields[ 1 ] ] = $label;
                } else {
                    // otherwise just save the data
                    $data[ $fields[ 0 ] ] = $label;
                }
            }
            return [ $item[ $this->recordId ] => $data ];
        };
    }

    public function getTimeField()
    {
        return $this->metadata->filter(function ($meta){
            $isText = $meta['field_type'] == 'text';
            $istime = in_array( $meta['text_validation_type_or_show_slider_number'],
                ['datetime_dmy']);
            return $isText && $istime;
        });
    }

    public function getUser( $redcap_user )
    {
        $users = collect($this->exportUsers()) ;

        return $users->where('username', $redcap_user)->first();
    }
}
