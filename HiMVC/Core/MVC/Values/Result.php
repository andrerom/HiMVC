<?php
/**
 * MVC\Result class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC\Values;

use eZ\Publish\API\Repository\Values\ValueObject;

/**
 * Result object
 *
 * Encapsulates all data from a controller action to be able to generate view and
 * for hmvc use be able to figure out the overall expiry of the full page.
 *
 * @property-read string $module
 * @property-read string $action
 * @property-read string controller
 * @property-read array $params
 * @property-read null|ResultCacheInfo $cacheInfo
 * @property-read null|ResultMetaData $metaData
 * @property-read ResultCookie[] $cookies
 */
abstract class Result extends ValueObject
{
    /**
     * The module name of the controller
     *
     * Used for template name conventions in view handlers.
     *
     * @var string
     */
    protected $module;

    /**
     * The action name performend for this result, like: edit, read, ..
     *
     * Used for template name conventions in view handlers.
     *
     * @var string
     */
    protected $action;

    /**
     * The controller handling the result (for reverse route use)
     *
     * @var string
     */
    protected $controller;

    /**
     * Optional view params
     *
     * @var array
     */
    protected $params = array();

    /**
     * Contains cache info
     *
     * expiry, last modified, vary by, (...)
     *
     * @var null|ResultCacheInfo
     */
    protected $cacheInfo;

    /**
     * Contains meta data
     *
     * language, disposition?, (...)
     *
     * @var null|ResultMetaData
     */
    protected $metaData;

    /**
     * Contains all the cookies to be set
     *
     * @var ResultCookie[]
     */
    protected $cookies = array();

    /**
     * Constructor for Result
     *
     * Check presence of model, module, action and uri as they are minimum properties that needs to be set.
     *
     * @param array $properties
     */
    public function __construct( array $properties = array() )
    {
        if ( !isset( $properties['module'] ) ||
             !isset( $properties['action'] ) ||
             !isset( $properties['controller'] ) )
            throw new \Exception( 'Properties that must be present: module, action and controller' );

        parent::__construct( $properties );
    }

    /**
     * @param array $params
     *
     * @throws \Exception
     * @return \HiMVC\Core\MVC\Values\Result
     */
    public function with( array $params )
    {
        $clone = clone $this;
        foreach ( $params as $key => $value )
        {
            if ( isset( $clone->params[$key] ) )
                $clone->params[$key] = $value;
            else
                throw new \Exception( "Could not find provided param: {$key}", __METHOD__ );
        }
        return $clone;
    }

    /**
     * Protect clone so it is only accessible via createChild()
     */
    protected function __clone()
    {
    }
}
