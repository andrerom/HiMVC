<?php
/**
 * File contains Accpt class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\MVC;

use eZ\Publish\API\Repository\Values\ValueObject;

/**
 * Accept class containing all accept headers (skipping accept-codec for utf8)
 */
class Accept extends ValueObject
{
    /**
     * @var array Acceptable Languages
     */
    public $languages = array();

    /**
     * @var array Acceptable content types (mime-types)
     */
    public  $types = array();

    /**
     * @var array Encodings like gzip, deflate, ...
     */
    public $encodings = array();
}
