<?php

class DataPreparator
{

    private $AWSKeys;

    private $unsetKeys;


    public function __construct(
        array $unsetKeys = null,
        array $AWSKeys = null)
    {
        $this->unsetKeys = isset( $unsetKeys )
            ? $unsetKeys
            : array( 'profileImage', 'profileCover', 'filePath', 'videoThumb' );

        $this->AWSKeys = isset( $AWSKeys )
            ? $AWSKeys
            : array( 'modified', 'timestamp' );
    }


    public function addUnsetKeys( $keys )
    {
        $this->unsetKeys = array_merge( $this->unsetKeys, (array) $keys );
    }


    public function addAWSKeys( $keys )
    {
        $this->AWSKeys = array_merge( $this->AWSKeys, (array) $keys );
    }


    public function getPreparedData( $data )
    {
        $this->adjustAWSUrlInArray( $data, $this->AWSKeys );
        $this->unsetRecursive( $data, $this->unsetKeys );

        return $data;
    }


    public function adjustAWSUrlInArray( &$data, $keys )
    {
        if ( is_array( $data ) ) {
            foreach( $keys as $key ) {
                if( isset( $data[$key] ) ) $data[ $key ] = $this->adjustAWSUrl( $data[ $key ] );
            }
            foreach( $data as &$field ) {
                if( is_array( $field ) OR is_object( $field ) )
                    $this->adjustAWSUrlInArray( $field, $keys );
            }
            unset( $field );
        }
        if( is_object( $data ) ) {
            foreach( $keys as $key ) {
                if( isset( $data->$key ) ) $data->$key = $this->adjustAWSUrl( $data->$key );
            }
            foreach( $data as &$field ) {
                if( is_array( $field ) OR is_object( $field ) )
                    $this->adjustAWSUrlInArray( $field, $keys );
            }
            unset( $field );
        }
    }


    public function adjustAWSUrl( $url )
    {
        // remove all after 'Expires'. Otherwise comperison will fail  due to different sufixes added by AWS client

        if( empty( $url ) ) return $url;

        $index = strpos( $url, 'Expires' );
        if( $index === false ) return $url;

        return substr( $url, 0, $index );
    }


    public function unsetRecursive( & $data, $keys )
    {
        if( is_array( $data ) )
        {
            foreach( $keys as $key )
            {
                unset( $data[ $key ] );
            }
        }
        elseif( is_object( $data ) )
        {
            foreach( $keys as $key )
            {
                unset( $data->$key );
            }
        }

        foreach( $data as &$value )
        {
            if( is_array( $value ) or is_object( $value ) )
            {
                $this->unsetRecursive( $value, $keys );
            }
        }
    }

}
