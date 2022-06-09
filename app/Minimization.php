<?php

namespace App;

use Exception;
use Illuminate\Support\Collection;

class Minimization
{
    private $groups;
    private $factorsLabel;
    private $setting;
    private $freq_table;
    private $mini_table;
    private $record_id;
    private $factorsWeight;
    private $probs;


    function __construct( $setting, $metadata=null, array $minim_table = NULL )
    {
        $this->setting = $setting;
        if (! is_null($metadata))
        {
            $this->record_id = array_key_first( $metadata->toArray() );
        }
        $this->record_id = $setting['record_id'];
        $this->factorsLabel = $this->extractFactorsLabel();
        $this->factorsWeight = $this->extractFactorsWeight();
        $this->groups = $this->extractGroups( $setting[ 'groups' ] );
        $this->distance_method = $setting[ 'distance_method' ];
        $this->setMiniTable( $minim_table );
        $this->setFreqTable();
        $this->buildProbs();

//        dd( $this->getMinAllocationRatioGroup(), $setting, $this->getBcmProb());
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function getFactorsLabel()
    {
        return $this->factorsLabel;
    }

    public function getFreqTable()
    {
        return $this->freq_table;
    }

    public function getMiniTable(): array
    {
        return $this->mini_table;
    }


    public function enroll( $record_id, $records )
    {
        $new_participant = $records
            ->where( 'record_id', '==', $record_id )
            ->first();

        throw_if( $new_participant[ $this->setting[ 'randGroup' ] ] != '',
            new \Exception( 'Participant already randomised.' ) );

        $this->buildMiniTable( $records );

        $allocation = $this->randomise( $new_participant );

        return $allocation;
    }

    public function buildMiniTable( Collection $records )
    {
        $records = $records->groupBy( [
                        $this->setting[ 'randGroup' ],
                        $this->record_id
                    ] );

        $temp_mini_table = [];
        $freq_table = $this->createFreqTable();
        foreach ( $this->groups as $group => $details ) {
            $temp_mini_table[ $group ] = [];
            if ( $records->has( $group ) ) {
                foreach ( $records[ $group ] as $id => $data ) {
                    $data = $data->first();
                    foreach ( $this->factorsLabel as $factor => $level ) {
                        throw_if( !isset( $data[ $factor ] ), new Exception( 'Error' ) );
                        $temp_mini_table[ $group ][ $id ][ $factor ] = $data[ $factor ];
                    }
                }
            }
        }

        $this->mini_table = $temp_mini_table;
        $this->freq_table = $this->buildFreqTable( $temp_mini_table, $freq_table );
    }

    private function createFreqTable(): array
    {
        $freq_table = [];
        foreach ( $this->groups as $group => $details ) {
            foreach ( $this->factorsLabel as $factor => $levels ) {
                foreach ( $levels as $level => $label ) {
                    $freq_table[ $group ][ $factor ][ $level ] = 0;
                }
            }
        }
        return $freq_table;
    }

    public function randomise( $new_participant )
    {
        $new_factors = $this->getNewFactors( $new_participant );

        $temp_freq = $this->getTempFreq( $new_factors );

        $imbalance_score = $this->getImbalanceScore( $temp_freq );

        $min_list = $this->getMinList( $imbalance_score );

        $new_group = $this->getNewGroup( $min_list );

        array_push( $this->mini_table[ $new_group ], $new_factors );
        $this->buildFreqTable();

        return [ $new_group, $imbalance_score ];
    }

    private function setFreqTable()
    {
        $freq_table = $this->createFreqTable();

        if ( count( $this->mini_table ) > 0 ) {
            $this->freq_table = $this->buildFreqTable( $this->mini_table, $freq_table );
        }

    }


    public function buildFreqTable( array $mini_table = NULL, $freq_table = NULL )
    {
        $mini_table = $mini_table ?? $this->mini_table;
        $freq_table = $freq_table ?? $this->freq_table;
        foreach ( $this->groups as $group => $details ) {
            if ( isset( $mini_table[ $group ] ) && count( $mini_table[ $group ] ) ) {
                $freq_table = $this->newFreqData( $mini_table[ $group ], $group, $freq_table );
            }
        }
        return $freq_table;
    }


    private function getLevel( string $factor, $value )
    {
        throw_if( !$this->factorsLabel->has( $factor ),
            new Exception(
                "Invalid factor name. '$factor' can't be found in minimisation setting." )
        );

        throw_if( $value === "",
            new Exception(
                "'$factor' is required." )
        );

        foreach ( $this->factorsLabel[ $factor ] as $test_val => $label ) {
            if ( $value == $test_val ) {
                return $test_val;
            }
        }

        throw new Exception(
            "Can't find level for '$factor' factor with value = '$value'."
        );
    }

    private function newFreqData( array $new_mini, $group, $freq_table = NULL )
    {

        $freq_table = $freq_table ?? $this->freq_table;
        foreach ( $new_mini as $id => $factors ) {
            foreach ( $factors as $factor => $value ) {
                $level = $this->getLevel( $factor, $value );
                $freq_table[ $group ][ $factor ][ $level ] += 1;
            }
        }

        return $freq_table;
    }


    private function getNewFactors( $data ): array
    {
        $new_factors = [];
        foreach ( $this->factorsLabel as $factor => $level ) {
            throw_if( !isset( $data[ $factor ] ),
                new Exception( "Can't find '$factor' factor in New participant data." ) );

            $new_factors[ $factor ] = $data[ $factor ];
        }
        return $new_factors;
    }


    private function getTempFreq( $new_factors ): array
    {
        $temp_freq = [];

        $factors = $this->factorsLabel->toArray();

        foreach ( $factors as $factor => $l ) {
            $level = $new_factors[ $factor ];
            foreach ( $this->groups as $group => $details ) {
                foreach ( $this->groups as $g => $d ) {
                    $freq = $this->freq_table[ $g ][ $factor ][ $level ];
                    if ( $group == $g ) {
                        $freq ++;
                    }
                    $temp_freq[ $group ][ $factor ][ $g ] = $freq;
                }
            }
        }
        return $temp_freq;
    }

    private function setMiniTable( ?array $minim_table )
    {
        if ( is_null( $minim_table ) ) {
            $minim_table = [];
            foreach ( $this->groups as $group => $details ) {
                $minim_table[ $group ] = [];
            }
        }

        $this->mini_table = $minim_table;
    }

    private function range_distance( array $freqs ): float
    {
        return max( $freqs ) - min( $freqs );
    }

    private function variance_distance( array $freqs ): float
    {
        $variance = 0;
        $mean = 1.0 * array_sum( $freqs ) / count( $freqs );

        foreach ( $freqs as $freq ) {
            $variance += pow( abs( $freq - $mean ), 2 );
        }

        return $variance;
    }

    private function st_dev_distance( array $freqs ): float
    {
        $variance = $this->variance_distance( $freqs );

        return sqrt( 1.0 * $variance );
    }

    private function marginal_balance_distance( array $freqs ): float
    {

        $len = count( $freqs );
        $numerator = 0;

        for ( $i = 0; $i < $len; $i ++ ) {
            for ( $j = $i + 1; $j < $len; $j ++ ) {
                $numerator += abs( $freqs[ $i ] - $freqs[ $j ] );
            }
        }

        $denumerator = ( $len - 1 ) * array_sum( $freqs );

        $imbalance = 1.0 * $numerator / $denumerator;

        return $imbalance;

    }

    private function getNewGroup( $min_list )
    {
        $prefered_group = $min_list[ array_rand( $min_list, 1 ) ];

        if ( count( $this->groups ) == count( $min_list ) ) {
            $ratio_prob = $this->getRatioProb();
            $selected_prob = $ratio_prob[ $prefered_group ];
        } else {
            $selected_prob = $this->probs[ $prefered_group ];
        }

        // use random_int to generate cryptographically secure pseudo-random integers
        // https://wiki.php.net/rfc/easy_userland_csprng
        $random_num = random_int ( 1 , collect($selected_prob)->sum() );

        $current_sum = 0;
        foreach ($selected_prob as $group => $prob )
        {
            $current_sum += $prob;
            if ( $random_num <= $current_sum ){
                return $group;
            }
        }
    }

    private function extractFactorsLabel(): Collection
    {
        $factors = $this->setting[ 'factors' ];

        return collect( $factors )
            ->mapWithKeys( function ( $item ) {
                $levels = collect( $item[ 'levels' ] )->mapWithKeys( function ( $item, $key ) {
                    return [ $item[ 'coded_value' ] => $item[ 'label' ] ];
                } );

                return [ $item[ 'field_name' ] => $levels ];
            } );
    }

    private function extractFactorsWeight(): Collection
    {
        $factors = $this->setting[ 'factors' ];

        return collect( $factors )
            ->mapWithKeys( function ( $item ) {
                return [ $item[ 'field_name' ] => $item[ 'weight' ] ];
            } );
    }

    private function getImbalanceScore( array $temp_freq ): array
    {

        //find imballance score for each group
        $imbalance_score = [];
        foreach ( $temp_freq as $group => $factors ) {
            foreach ( $factors as $factor => $freqs ) {
                //Get addjusted freq
                $adj_freq = $this->addjustFreqWithGroupRatio( $freqs );

                $adjusted_imbalance = $this->factorsWeight[ $factor ];

                switch ( $this->distance_method ) {
                    case 'range':
                        $adjusted_imbalance *= $this->range_distance( $adj_freq );
                        break;

                    case 'variance':
                        $adjusted_imbalance *= $this->variance_distance( $adj_freq );
                        break;

                    case 'st_dev':
                        $adjusted_imbalance *= $this->st_dev_distance( $adj_freq );
                        break;

                    case 'marginal_balance' :
                        $adjusted_imbalance *= $this->marginal_balance_distance( $adj_freq );
                        break;

                    default:
                        throw new Exception(
                            " Distance method '$this->distance_method' is not supported."
                        );
                }
            }
            $imbalance_score[ $group ] = $adjusted_imbalance;
        }

        return $imbalance_score;
    }

    private function getMinList( array $imbalance_scores ): array
    {
        $min_score = min( $imbalance_scores );

        //get treatment groups that have value equal to minimum imbalance score
        $min_list = [];
        foreach ( $imbalance_scores as $group => $value ) {
            if ( $value == $min_score ) {
                $min_list[] = $group;
            }
        }
        return $min_list;
    }

    private function extractGroups( $groups ): Collection
    {
        return collect( $groups )->mapWithKeys( function ( $group ) {
            return [ $group[ 'coded_value' ] => $group ];
        } );
    }

    private function addjustFreqWithGroupRatio( $freqs ): array
    {
        $adj_freq = [];
        foreach ( $freqs as $group => $freq ) {
            $adj_freq[] = 1.0 * $freq / $this->groups[ $group ][ 'ratio' ];
        }

        return $adj_freq;
    }

    private function buildProbs( $prob_method = null )
    {
        $this->probs = [];

        if ( is_null( $prob_method ) ) {
            $prob_method = $this->setting[ 'prob_method' ];
        }

        switch ( $prob_method ) {
            case 'ratio':
                $this->probs = $this->getRatioProb();
            case 'naive':
                $this->probs = $this->getNaiveProb();
                break;
            case 'bcm':
                $this->probs = $this->getBcmProb();
                break;
        }
    }

    private function getNaiveProb()
    {
        $groups = $this->setting[ 'groups' ];
        $high_prob = (int)$this->setting[ 'base_prob' ];
        $low_prob = (int)ceil( ( 100 - $high_prob ) / ( count( $this->groups ) - 1 ) );
        $probs = [];
        foreach ( $groups as $group ) {
            $probs[ $group[ 'coded_value' ] ] = [];
            foreach ( $groups as $g ) {
                $current_prob = $group[ 'coded_value' ] == $g[ 'coded_value' ] ?
                    $high_prob : $low_prob;
                $probs[ $group[ 'coded_value' ] ][ $g[ 'coded_value' ] ] = $current_prob;
            }
        }
        return $probs;
    }

    private function getRatioProb()
    {
        $groups = $this->setting[ 'groups' ];
        $base_prob = collect( $groups )
            ->mapWithKeys( function ( $group ) {
                return [ $group[ 'coded_value' ] => $group[ 'ratio' ] ];
            } );

        return $base_prob->mapWithKeys( function ( $prob, $key ) use ( $base_prob ) {
            return [ $key => $base_prob ];
        } );
    }

    private function getBcmProb()
    {

        $high_prob = (int) $this->setting['base_prob'];
        $min_group = $this->getMinAllocationRatioGroup();;
        $allocations = $this->getGroupsAllocationRatio()->toArray();


        $probs=[];
        foreach( $allocations as $group => $ratio )
        {
            foreach ( $allocations as $g => $r )
            {
                $probs[ $group ][ $g ] = 0;
            }
        }

        // set high prob for min group
        $probs[$min_group][$min_group] = $high_prob;

        foreach( $allocations as $group => $ratio )
        {
            // calculate high prob for other groups
            if ( $group != $min_group ) {
                $numerator = 0;
                $denominator = 0;
                foreach ( $allocations as $g => $r ) {
                    if ( $g != $group ) {
                        $numerator += $r;
                    }
                    if (  $g != $min_group ) {
                        $denominator += $r;
                    }
                }
                $prob = ( 1 - ( $numerator / $denominator ) * ( 1 - $high_prob/100 ) );

                $probs[$group][$group] = (int) round( $prob*100 , 0);
            }
            // calculate low probs
            foreach ( $allocations as $g => $r ){
                if ($group == $g ) {
                    continue;
                }

                $current_high_prob = $probs[$group][$group]/100;
                $numerator = $r;
                $denominator = 0;
                foreach($allocations as $gg => $rr)
                {
                    if($gg != $group ){
                        $denominator += $rr;
                    }
                }
                $prob = ( $numerator / $denominator) * ( 1.0 - $current_high_prob);
                $probs[$group][$g] = (int) round( $prob*100 , 0);;
            }
        }


        return $probs;
    }

    private function getMinAllocationRatioGroup()
    {
        return $this->getGroupsAllocationRatio()
            ->sort()
            ->keys()
            ->first();
    }

    /**
     * @param $groups
     * @param $min_group
     * @param $high_prob
     */
    private function calculateHighProb( $groups, $group, $min_group, $high_prob )
    {
        $numerator = 0;
        $denumerator = 0;
        foreach ( $groups as $row ) {
            if ( $group[ 'coded_value' ] != $row[ 'coded_value' ] ) {
                $numerator += $group[ 'ratio' ];
            }
            if ( $row[ 'coded_value' ] != $min_group ) {
                $denumerator += $group[ 'ratio' ];
            }
        }
        $prob = ( 1 - ( $numerator / $denumerator ) * ( 1 - $high_prob ) );
        return $prob;
    }

    /**
     * @return Collection
     */
    private function getGroupsAllocationRatio(): Collection
    {
        return collect( $this->setting[ 'groups' ] )
            ->mapWithKeys(
                function ( $group ) {
                    return [ $group[ 'coded_value' ] => $group[ 'ratio' ] ];
                } );
    }

    /**
     * @param $current_group
     * @param $row_coded_value
     * @param array $allocation_ratios
     * @param $groups
     */
}
