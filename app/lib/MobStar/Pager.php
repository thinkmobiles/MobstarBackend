<?php

namespace MobStar;

class Pager
{

    private $url;

    private $pageSize;

    private $page;

    private $needNextLink;

    public function __construct( $url, $pageSize = 0, $page = 0 )
    {
        $this->url = $url;
        $this->pageSize = $pageSize <= 0 ? 0 : $pageSize;
        $this->page = $page <= 0 ? 0 : $page;

        if( ($this->page == 0) && ($this->pageSize > 0) ) {
            $this->page = 1;
        }

        $this->needNextLink = null;
    }


    public function getLimit()
    {
        return $this->pageSize > 0 ? ($this->pageSize + 1) : 0;
    }


    public function getOffset()
    {
        if( $this->page == 0 ) return 0;
        return $this->pageSize > 0 ? ($this->pageSize * ($this->page - 1)) : 0;
    }


    /**
     * Must be called before needNextLink, that may be forgotten
     *
     * @todo remake it to return some object with prev and next links
     *
     * @param array $results
     */
    public function getAdjustedToPage( array $results )
    {
        $this->needNextLink = false;
        if( ! $this->pageSize ) {
            return $results;
        }

        while( count( $results ) > $this->pageSize ) {
            $this->needNextLink = true;
            array_pop( $results );
        }

        return $results;
    }


    public function needNextLink() {
        if( ! isset( $this->needNextLink ) ) {
            throw new \Exception( 'you need to call getAdjustedToPage first' );
        }
        return $this->needNextLink;
    }


    public function needPrevLink() {
        return ($this->page > 1);
    }


    public function getNextLink( array $params = array() ) {

        $params['limit'] = $this->pageSize;
        $params['page'] = $this->page + 1;
        return $this->url.'?'.http_build_query( $params );
    }


    public function getPrevLink( array $params = array() ) {

        $params['limit'] = $this->pageSize;
        $params['page'] = $this->page - 1;
        return $this->url.'?'.http_build_query( $params );
    }
}
