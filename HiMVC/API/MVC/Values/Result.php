<?php
/**
 * MVC\Result class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\API\MVC\Values;

use eZ\Publish\API\Repository\Values\ValueObject;

/**
 * Result object
 *
 * Encapsulates all data from a controller action to be able to generate view and
 * for hmvc use be able to figgure out the overall expiry of the full page.
 *
 * @todo: Support disposition and ResultList (Result with several models, aka list views)?
 *        Aka: Make this abstract and cover all cases? (so impl can have 3 variants?)
 */
class Result extends ValueObject
{
    /**
     * The model object for the result
     *
     * @var object
     */
    public $model;

    /**
     * The module name of the controller
     *
     * Used for template name conventions in view handlers.
     *
     * @var string
     */
    public $module;

    /**
     * The action name performend for this result, like: edit, read, ..
     *
     * Used for template name conventions in view handlers.
     *
     * @var string
     */
    public $action;

    /**
     * Optional view of the action if any (If the action support different views)
     *
     * Used for template name conventions in view handlers.
     *
     * @var string
     */
    public $view = '';

    /**
     * Optional view params
     *
     * @var array
     */
    public $params = array();

    /**
     * Contains cache info
     *
     * expiry, last modified, vary by, (...)
     *
     * @var null|ResultCacheInfo
     */
    public $cacheInfo;

    /**
     * Contains meta data
     *
     * language, disposition?, (...)
     *
     * @var null|ResultMetaData
     */
    public $metaData;

    /**
     * Contains all the cookies to be set
     *
     * @var ResultCookie[]
     */
    public $cookies = array();
}