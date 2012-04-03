<?php
/**
 * File contains Module class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Base;

use eZ\Publish\API\Repository\Values\ValueObject;

/**
 * Module class
 *
 * A module is a collection of features, it can contain controllers, settings, templates, assets(?), (...)
 */
class Module extends ValueObject
{
    /**
     * @var string Name of module (the module name)
     */
    public $name;

    /**
     * @var string Absolute path to module
     */
    public $path;

    /**
     * @var string List of designs avaialble in this module
     */
    public $designs;

    /**
     * Constructor
     *
     * @param string $name Name of module
     * @param string $path Absolute path to module
     * @param array $designs Optional list of designs avaialble in this module
     */
    public function __construct( $name, $path, array $designs = array() )
    {
        $this->name = $name;
        $this->path = $path;
        $this->designs = $designs;
    }
}

?>