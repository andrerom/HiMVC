<?php
/**
 * File containing the XmlText Value class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace HiMVC\Core\Content\FieldType\XmlText;
use eZ\Publish\Core\Repository\FieldType\Value as BaseValue,
    eZ\Publish\SPI\Persistence\Content\FieldValue as PersistenceFieldValue,
    eZ\Publish\Core\Repository\FieldType\XmlText\Input\Handler as InputHandler,
    eZ\Publish\Core\Repository\FieldType\XmlText\Input\Parser\Simplified as SimplifiedInputParser,
    eZ\Publish\Core\Repository\FieldType\XmlText\Input\Parser\OnlineEditor as OnlineEditorParser,
    eZ\Publish\Core\Repository\FieldType\XmlText\Input\Parser\Raw as RawInputParser,
    DOMDocument;

/**
 * Basic, raw value for TextLine field type
 */
class Value extends BaseValue
{
    /**
     * Text content
     *
     * @var string
     */
    public $text;

    /**
     * Text content, as RAW format (self::INPUT_FORMAT_RAW)
     * @var string
     */
    public $rawText;

    /**
     * Text input format
     * @type string One of the INPUT_FORMAT_* class constants
     */
    private $inputFormat;

    /**
     * Input handler
     * @var \eZ\Publish\Core\Repository\FieldType\XmlText\Input\Handler
     */
    private $inputHandler;

    /**
     * HTML input format constant
     */
    // const INPUT_FORMAT_HTML = 'html';

    /**
     * Plain text input format constant
     */
    const INPUT_FORMAT_PLAIN = 'plain';

    /**
     * RAW input format constant
     * Internal storage format
     */
    const INPUT_FORMAT_RAW = 'raw';

    /**
     * Initializes a new XmlText Value object with $text in
     *
     * @param \eZ\Publish\Core\Repository\FieldType\XmlText\Input\Handler $handler
     * @param string $text
     * @param string $inputFormat Which format the input is provided as. Expects one of the INPUT_* class constants
     */
    public function __construct( InputHandler $handler, $text = '', $inputFormat = self::INPUT_FORMAT_PLAIN )
    {
        $this->inputHandler = $handler;
        $this->text = $text;
        if ( $inputFormat === self::INPUT_FORMAT_RAW )
            $this->rawText = $text;

        $this->inputFormat = $inputFormat;
    }

    /**
     * @see \eZ\Publish\Core\Repository\FieldType\Value
     */
    public function __toString()
    {
        // @todo Remove, temporary stuff for demo use
        $doc = new DOMDocument( '1.0', 'utf-8' );
        $doc->loadXML( str_replace(
            array( ' xmlns:tmp="http://ez.no/namespaces/ezpublish3/temporary/"', '<paragraph', '</paragraph>', '<line>', '</line>', '<literal', '</literal>', ),
            array( '', '<p', '</p>', '', '<br/>', '<pre', '</pre>' ),
            $this->text
        ) );

        return $doc->saveXML( $doc->firstChild );
    }

    /**
     * @see \eZ\Publish\Core\Repository\FieldType\Value::getTitle()
     */
    public function getTitle()
    {
        throw new \RuntimeException( 'Implement this method' );
    }

    /**
     * Returns the input handler depending on the input value type
     * @return \eZ\Publish\Core\Repository\FieldType\XmlText\Input\Handler
     */
    public function getInputHandler()
    {
        return $this->inputHandler;
    }

    /**
     * Sets the raw value for the XmlText to $rawText
     * @var text
     */
    public function setRawText( $rawText )
    {
        $this->rawText = $rawText;
    }
}
