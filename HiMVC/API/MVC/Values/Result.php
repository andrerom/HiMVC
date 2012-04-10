<?php
/**
 * MVC\Result class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\API\MVC\Values;

use eZ\Publish\API\Repository\Values\ValueObject;
use HiMVC\API\MVC\Values\Route;

/**
 * Result object
 *
 * Encapsulates all data from a controller action to be able to generate view and
 * for hmvc use be able to figgure out the overall expiry of the full page.
 *
 * @property-read object $model
 * @property-read string $module
 * @property-read string $action
 * @property-read string $view
 * @property-read Route $route
 * @property-read array $params
 * @property-read null|ResultCacheInfo $cacheInfo
 * @property-read null|ResultMetaData $metaData
 * @property-read ResultCookie[] $cookies
 */
class Result extends ValueObject
{
    /**
     * The model object for the result
     *
     * @var object
     */
    protected $model;

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
     * Optional view of the action if any (If the action support different views)
     *
     * Used for template name conventions in view handlers.
     *
     * @var string
     */
    protected $view = '';

    /**
     * The route that matched this result.
     *
     * @var Route
     */
    protected $route;

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
        if ( !isset( $properties['module'] ) || !isset( $properties['action'] ) || !isset( $properties['model'] ) )
            throw new \Exception( 'Properties that must be present: module, model and action' );

        parent::__construct( $properties );
    }

    /**
     * @param Route $route
     */
    public function setRoute( Route $route )
    {
        $this->route = $route;
    }
}
