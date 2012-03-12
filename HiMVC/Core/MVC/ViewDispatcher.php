<?php
/**
 * File contains ViewDispatcher class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

/**
 * ViewDispatcher
 *
 * Deals with override rules by configuration and passes execution on to view handler by those rules or alternativly to
 * default one (first).
 */
class ViewDispatcher
{
    /**
     * @var \Closure[]
     */
    protected $viewHandlers;

    /**
     * @var array
     */
    protected $conditions;

    /**
     * @param \Closure[] $viewHandlers First is default, key of array must be file suffix used by viewHandler
     *                            View handler must be a callback.
     * @param array $conditions Conditions for override
     */
    public function __construct( array $viewHandlers, array $conditions )
    {
        $this->viewHandlers = $viewHandlers;
        $this->conditions = $conditions;
    }

    /**
     * Map view by convention and do a match against override conditions before it is executed
     *
     * view() $source convention: '<$module>/<$action>[/<$view>]'
     *
     * If no override match is found then '.<defaultViewSuffix>' is appended where
     * <defaultViewSuffix> is key of first item in $viewHandlers passed to __construct()
     *
     * @uses viewSource()
     * @param string $module Can be a sub module, but most not start or stop in slash, eg: content/rating
     * @param string $action
     * @param string $view
     * @param array $params
     * @return string
     */
    public function view( $module, $action, $view = '', array $params )
    {
        $source = $module . '/' . $action . ( $view ? '/' . $view : '' );
        return $this->viewBySource( $source, $params );
    }

    /**
     * @param string $source
     * @param array $params
     * @return string
     * @throws \Exception
     */
    protected function viewBySource( $source, array $params  )
    {
        $target = $this->getMatchingConditionTarget( $source, $params );
        if ( $target === null )
        {
            foreach ( $this->viewHandlers as $suffix => $viewHandler )// Select the first view handler (default)
            {
                return call_user_func( $viewHandler, "{$source}.$suffix", $params );
            }
            throw new \Exception( 'No view handler where provided, can not render view' );
        }

        if ( preg_match( "/\.(?P<suffix>[^.]+)$/", $target, $match ) && isset( $this->viewHandlers[ $match['suffix'] ] ) )
        {
            return call_user_func( $this->viewHandlers[ $match['suffix'] ], $target, $params );
        }
        throw new \Exception( "Could not find a view handler that matches target: {$target}" );
    }

    /**
     * Get a matching condition
     *
     * Match points are given like this:
     * - A possible match starts with 6 points
     * - 1 is subtracted for every deeper level in the matching
     * - 3 is added for identifer matchs
     * - 4 is added for remoteId matchs
     *
     * @todo Consider if conditions should be read in reverse order / prepended on match
     *
     * @param string $source
     * @param array $params
     * @return string|null
     */
    protected function getMatchingConditionTarget( $source, array $params )
    {
        $matches = array();
        foreach ( $this->conditions as $identifier => $settings )
        {
            if ( $settings['source'] !== $source )
                continue;

            $totalPoints = 0;
            foreach ( $settings as $name => $value )
            {
                if ( $name === 'target' || $name === 'source' )
                    continue;

                if ( !isset( $params[$name] ) )
                    continue 2;

                if ( $value === $params[$name] )
                    $points = ( $name === 'remoteId' ? 10 : ( $name === 'identifer' ? 9 : 6 ) );
                else if ( !is_array( $value ) )
                    continue 2;
                else if ( !$this->recursiveMatch( $value, $params[$name], $points ) )
                    continue 2;

                $totalPoints += $points;
            }
            $matches[$totalPoints][$identifier] = $settings;
        }

        if ( empty( $matches ) )
            return null;

        krsort( $matches, SORT_NUMERIC );
        $match = reset( $matches );
        $match = reset( $match );
        return $match['target'];
    }

    /**
     * @param array $condition
     * @param mixed $param
     * @param int $points
     * @return bool
     */
    protected function recursiveMatch( array $condition, $param, &$points = 5 )
    {
        --$points;
        foreach ( $condition as $name => $value )
        {
            $valueIsArray = is_array( $value );
            if ( is_object( $param ) )
            {
                if ( !isset( $param->$name ) )
                    return false;

                if ( $valueIsArray)
                {
                    if ( !$this->recursiveMatch( $value, $param->$name, $points )  )
                        return false;
                }
                else if ( $param->$name === $value )
                {
                    $points += ( $name === 'remoteId' ? 4 : ( $name === 'identifer' ? 3 : 1 ) );
                }
                else
                {
                    return false;
                }
            }
            else if ( is_array( $param ) )
            {
                if ( !isset( $param[$name] ) )
                    return false;

                if ( $valueIsArray)
                {
                    if ( !$this->recursiveMatch( $value, $param[$name], $points )  )
                        return false;
                }
                else if ( $param[$name] === $value )
                {
                    $points += ( $name === 'remoteId' ? 4 : ( $name === 'identifer' ? 3 : 1 ) );
                }
                else
                {
                    return false;
                }
            }
            else if ( $valueIsArray )
            {
                return false;
            }
            else if ( $value === $param )
            {
                $points += ( $name === 'remoteId' ? 4 : ( $name === 'identifer' ? 3 : 1 ) );
            }
            else
            {
                return false;
            }
        }
        return true;
    }
}
