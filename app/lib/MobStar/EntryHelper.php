<?php

namespace MobStar;

use DB;

class EntryHelper
{

    private static $basicInfo = array();


    private static $categoriesInfo = array();


    private static $commentCountsInfo = array();


    private static $filesInfo = array();


    private static $tagNamesInfo = array();


    private static $totalVotesInfo = array();


    private static $votedByUserInfo = array();


    private static $emptyEntry = false;


    private static $emptyCommentCounts = 0;


    private static $emptyFiles = array();


    private static $emptyTagNames = array();


    private static $emptyTotalVotes = array(
        'up' => 0,
        'down' => 0,
    );


    private static $emptyVotedByUser = array(
        'up' => false,
        'down' => false,
    );

    /*
     * prepare entry info for future use
     *
     * comments count
     * tags
     * files
     * votes
     */
    public static function prepareEntries( array $entryIds = array(), array $fields = array() )
    {
        self::prepareBasicInfo( $entryIds );

        if( in_array( 'commentCounts', $fields ) )
            self::prepareCommentCounts( $entryIds );

        if( in_array( 'tagNames', $fields ) )
            self::prepareTagNamesInfo( $entryIds );

        if( in_array( 'filesInfo', $fields ) )
            self::prepareFilesInfo( $entryIds );

        if( in_array( 'totalVotes', $fields ) )
            self::prepareTotalVotesInfo( $entryIds );
    }


    public static function prepareBasicInfo( array $entryIds )
    {
        self::prepareCategoriesInfo();

        $newIds = self::getMissingKeys( self::$basicInfo, $entryIds );

        if( empty( $newIds ) )
            return;

        $newEntryRows = DB::table( 'entries' )
            ->whereIn( 'entry_id', $newIds )
            ->get();

        if( $newEntryRows ) {

            foreach( $newEntryRows as $row ) {
                $row->categoryInfo = self::$categoriesInfo[ $row->entry_category_id ];
                self::$basicInfo[ $row->entry_id ] = $row;
            }
        }

        foreach( $newIds as $entryId ) {
            if( ! isset( self::$basicInfo[ $entryId ] ) ) {
                self::$basicInfo[ $entryId ] = self::$emptyEntry;
            }
        }
    }


    public static function prepareCategoriesInfo()
    {
        if( self::$categoriesInfo )
            return;

        $rows = DB::table( 'categories' )
            ->get();

        foreach( $rows as $row )
            self::$categoriesInfo[ $row->category_id ] = $row;
    }


    public static function prepareCommentCounts( array $entryIds )
    {
        $newIds = self::getMissingKeys( self::$commentCountsInfo, $entryIds );

        if( empty( $newIds ) )
            return;

        self::verbose( 'commentCounts', $newIds);

        $newCommentCountRows = DB::table( 'comments' )
            ->select( array(
                'comment_entry_id',
                DB::raw( 'count(*) as commentCount' )
            ) )
            ->whereIn( 'comment_entry_id', $newIds )
            ->where( 'comment_deleted', '=', 0 )
            ->groupBy( 'comment_entry_id' )
            ->get();

        if( $newCommentCountRows ) {

            foreach( $newCommentCountRows as $row ) {
                self::$commentCountsInfo[ $row->comment_entry_id ] = $row->commentCount;
            }
        }

        foreach( $newIds as $entryId ) {
            if( ! isset( self::$commentCountsInfo[ $entryId ] ) ) {
                self::$commentCountsInfo[ $entryId ] = self::$emptyCommentCounts;
            }
        }
    }


    public static function prepareFilesInfo( array $entryIds )
    {
        $newIds = self::getMissingKeys( self::$filesInfo, $entryIds );

        if( empty( $newIds ) )
            return;

        self::verbose( 'filesInfo', $newIds);

        $newFilesInfoRows = DB::table( 'entry_files' )
            ->whereIn( 'entry_file_entry_id', $newIds )
            ->where( 'entry_file_deleted', '=', 0 )
            ->get();

        if( $newFilesInfoRows ) {

            foreach( $newFilesInfoRows as $row ) {
                self::$filesInfo[ $row->entry_file_entry_id ][] = $row;
            }
        }

        foreach( $newIds as $entryId ) {
            if( ! isset( self::$filesInfo[ $entryId ] ) ) {
                self::$filesInfo[ $entryId ] = self::$emptyFiles;
            }
        }
    }


    public static function prepareTagNamesInfo( array $entryIds )
    {
        $newIds = self::getMissingKeys( self::$tagNamesInfo, $entryIds );

        if( empty( $newIds ) )
            return;

        self::verbose( 'tagNamesInfo', $newIds);

        $newTagNamesInfoRows = DB::table( 'entry_tags' )
            ->leftJoin( 'tags', 'entry_tags.entry_tag_tag_id', '=', 'tags.tag_id' )
            ->select( array(
                'entry_tags.entry_tag_entry_id as entry_id',
                'tags.tag_name as tag_name'
            ) )
            ->whereIn( 'entry_tag_entry_id', $newIds )
            ->where( 'entry_tag_deleted', '=', 0 )
            ->get();

        if( $newTagNamesInfoRows ) {

            foreach( $newTagNamesInfoRows as $row ) {
                self::$tagNamesInfo[ $row->entry_id ][] = $row->tag_name;
            }
        }

        foreach( $newIds as $entryId ) {
            if( ! isset( self::$tagNamesInfo[ $entryId ] ) ) {
                self::$tagNamesInfo[ $entryId ] = self::$emptyTagNames;
            }
        }
    }


    public static function prepareTotalVotesInfo( array $entryIds )
    {
        $newIds = self::getMissingKeys( self::$totalVotesInfo, $entryIds );

        if( empty( $newIds ) )
            return;

        self::verbose( 'totalVotesInfo', $newIds );

        $newTotalVotesInfoRows = DB::table( 'votes' )
            ->select( array(
                'vote_entry_id',
                DB::raw('sum( if( vote_up > 0, 1, 0 ) ) as votes_up'),
                DB::raw('sum( if( vote_down > 0, 1, 0 ) ) as votes_down'),
            ) )
            ->whereIn( 'vote_entry_id', $newIds )
            ->where( 'vote_deleted', '=', 0 )
            ->groupBy( 'vote_entry_id' )
            ->get();

        if( $newTotalVotesInfoRows ) {

            foreach( $newTotalVotesInfoRows as $row ) {
                self::$totalVotesInfo[ $row->vote_entry_id ] = array(
                    'up' => $row->votes_up,
                    'down' => $row->votes_down,
                );
            }
        }

        // set other missing votes to empty
        foreach( $newIds as $entryId ) {
            if( ! isset( self::$totalVotesInfo[ $entryId ] ) ) {
                self::$totalVotesInfo[ $entryId ] = self::$emptyTotalVotes;
            }
        }
    }


    public static function prepareVotedByUserInfo( array $entryIds, $userId )
    {
        // special case: get not missing entries, but missing users for entries
        $newIds = array();

        foreach( $entryIds as $entryId ) {
            if( ! isset( self::$votedByUserInfo[ $entryId][ $userId ] ) ) {
                $newIds[] = $entryId;
            }
        }
        if( empty( $newIds ) )
            return;

        self::verbose( 'votedByUser', $newIds );

        $newVotedByUserRows = DB::table( 'votes' )
            ->select( array(
                'vote_entry_id as entry_id',
                DB::raw( 'if( sum( vote_up ) > 0, 1, 0 ) as vote_up' ),
                DB::raw( 'if( sum( vote_down ) > 0, 1, 0 ) as vote_down' ),
            ))
            ->whereIn( 'vote_entry_id', $newIds )
            ->where( 'vote_user_id', '=', $userId )
            ->where( 'vote_deleted', '=', 0 )
            ->groupBy( 'vote_entry_id' )
            ->get();

        if( $newVotedByUserRows ) {

            foreach( $newVotedByUserRows as $row ) {
                self::$votedByUserInfo[ $row->entry_id ][ $userId ] = array(
                    'up' => $row->vote_up,
                    'down' => $row->vote_down,
                );
            }
        }

        // set empty votes
        foreach( $newIds as $entryId ) {
            if( ! isset( self::$votedByUserInfo[ $entryId ][ $userId ] ) ) {
                self::$votedByUserInfo[ $entryId ][ $userId ] = self::$emptyVotedByUser;
            }
        }
    }


    public static function getEntries( array $entryIds, array $fields = array(), $userId = 0 )
    {
        self::prepareEntries( $entryIds, $fields );

        if( in_array( 'votedByUser', $fields ) )
            self::prepareVotedByUserInfo( $entryIds, $userId );

        $entries = array();

        foreach( $entryIds as $entryId ) {
            $entry = self::$basicInfo[ $entryId ];

            // skip not existing entries
            if( ! $entry ) {
                $entries[ $entryId ] = $entry;
                continue;
            }

            foreach( $fields as $field ) {
                switch( $field ) {
                    case 'commentCounts':
                        $entry->commentCounts = self::$commentCountsInfo[ $entryId ];
                        break;
                    case 'filesInfo':
                        $entry->filesInfo = self::$filesInfo[ $entryId ];
                        break;
                    case 'tagNames':
                        $entry->tagNames = self::$tagNamesInfo[ $entryId ];
                        break;
                    case 'totalVotes':
                        $entry->totalVotes = self::$totalVotesInfo[ $entryId ];
                        break;
                    case 'votedByUser':
                        $entry->votedByUser = self::$votedByUserInfo[ $entryId ][ $userId ];
                        break;
                    default:
                        error_log( 'unknown EntryHelper field: '.$field );
                }
            }
            $entries[ $entryId ] = $entry;
        }
        return $entries;
    }


    public static function getBasicInfo( array $entryIds )
    {
        self::prepareBasicInfo( $entryIds );

        $basicInfo = array();

        foreach( $entryIds as $entryId ) {
            $basicInfo[ $entryId ] = self::$basicInfo[ $entryId ];
        }

        return $basicInfo;
    }


    public static function getCommentCount( array $entryIds )
    {
        self::prepareCommentCounts( $entryIds );

        $commentCounts = array();

        foreach( $entryIds as $entryId ) {
            $commentCounts[ $entryId ] = self::$commentCountsInfo[ $entryId ];
        }

        return $commentCounts;
    }


    public static function getFilesInfo( array $entryIds )
    {
        self::prepareFilesInfo( $entryIds );

        $filesInfo = array();

        foreach( $entryIds as $entryId ) {
            $filesInfo[ $entryId ] = self::$filesInfo[ $entryId ];
        }

        return $filesInfo;
    }


    public static function getTagNamesInfo( array $entryIds )
    {
        self::prepareTagNamesInfo( $entryIds );

        $tagNamesInfo = array();

        foreach( $entryIds as $entryId ) {
            $tagNamesInfo[ $entryId ] = self::$tagNamesInfo[ $entryId ];
        }

        return $tagNamesInfo;
    }


    public static function getTotalVotesInfo( array $entryIds )
    {
        self::prepareTotalVotesInfo( $entryIds );

        $totalVotesInfo = array();

        foreach( $entryIds as $entryId ) {
            $totalVotesInfo[ $entryId ] = self::$totalVotesInfo[ $entryId ];
        }

        return $totalVotesInfo;
    }


    public static function getVotedByUserInfo( array $entryIds, $userId )
    {
        self::prepareVotedByUserInfo( $entryIds, $userId );

        $votedByUserInfo = array();

        foreach( $entryIds as $entryId ) {
            $votedByUserInfo[ $entryId ] = self::$votedByUserInfo[ $entryId ][ $userId ];
        }

        return $votedByUserInfo;
    }


    private static function getMissingKeys( array $data, array $keys )
    {
        $missingKeys = array();

        foreach( $keys as $key ) {
            if( ! isset( $data[ $key ] ) ) {
                $missingKeys[] = $key;
            }
        }

        return $missingKeys;
    }


    public static function clear()
    {
        self::$basicInfo = self::$commentCountsInfo = self::$filesInfo = array();
        self::$tagNamesInfo = self::$totalVotesInfo = self::$votedByUserInfo = array();
    }


    public static function dump()
    {
        error_log( 'entries: '.print_r( self::$basicInfo, true ) );
        error_log( 'commentCounts: '.print_r( self::$commentCountsInfo, true ) );
        error_log( 'filesInfo: '.print_r( self::$filesInfo, true ) );
        error_log( 'tagNamesInfo: '.print_r( self::$tagNamesInfo, true ) );
        error_log( 'totalVotesInfo: '.print_r( self::$totalVotesInfo, true ) );
        error_log( 'votedByUserInfo: '.print_r( self::$votedByUserInfo, true ) );
    }


    private static function verbose( $type, $ids )
    {
        return;
        error_log( 'select '.$type.' for '.implode( ', ', $ids ) );
    }

}
