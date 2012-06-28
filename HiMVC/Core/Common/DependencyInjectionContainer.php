<?php
/**
 * Service Container class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Common;
use eZ\Publish\Core\Base\Exceptions\BadConfiguration,
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue,
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentException,
    eZ\Publish\Core\Base\Exceptions\MissingClass,
    HiMVC\API\Container,
    ReflectionClass;

/**
 * Service container class
 *
 * A dependency injection container that uses configuration for defining dependencies.
 *
 * Features:
 * - Constructor injection
 * - Setter injection
 * - Pre create argument filters
 * - Post create service listerners
 * - "service" dependecies using "@" prefix before dependency name
 *       Example: argument[fieldTypes]=@:fieldType
 *
 * - Variable dependecies using "$" prefix before dependency name
 *       Currently: $_SERVER, $_POST, $_GET, $_COOKIE, $_FILES, $body and $settings
 * - Lazy loaded "service" dependecies using "%" prefix before dependency name, returns a closure that will load service
 * - List of related services referance using ":", works both after @ and % (returns list of closures)
 *       Example: argument[fieldTypes]=@:fieldType
 *                argument[routes]=%:route
 *
 * - Optional dependencies using "?", works after all dependecy symbols: $, @ and %
 * - Factory methods
 * - Callable dependecy object creation with "::" key, works with all dependecy symbols: $, @ and %
 *       Example: argument[parse_callback]=@xztxmlvideo::parseCallback
 *
 * Usage:
 *
 *     $sc = new eZ\Publish\Core\Base\ServiceContainer( $configManager->getConfiguration('service')->getAll() );
 *     $sc->getRepository->getContentService()...;
 *
 * Or overriding $dependencies (in unit tests):
 * ( $dependencies keys should have same value as service.ini "arguments" values explained bellow )
 *
 *     $sc = new eZ\Publish\Core\Base\ServiceContainer(
 *         $configManager->getConfiguration('service')->getAll(),
 *         array(
 *             '@persistence_handler' => new \eZ\Publish\Core\Persistence\InMemory\Handler()
 *         )
 *     );
 *     $sc->getRepository->getContentService()...;
 *
 * Settings are defined in service.ini like the following example:
 *
 *     [repository]
 *     class=eZ\Publish\Core\Base\Repository
 *     arguments[persistence_handler]=@inmemory_persistence_handler
 *
 *     [inmemory_persistence_handler]
 *     class=eZ\Publish\Core\Persistence\InMemory\Handler
 *
 *     # @see \eZ\Publish\Core\settings\service.ini For more options and examples.
 */
class DependencyInjectionContainer implements Container
{
    /**
     * Holds service objects and variables
     *
     * @var object[]
     */
    private $dependencies;

    /**
     * Construct object with optional configuration overrides
     *
     * @param array $settings Services settings
     * @param mixed[]|object[] $dependencies Optional initial dependencies
     */
    public function __construct( array $settings, array $dependencies = array() )
    {
        $this->dependencies = $dependencies + array(
            '$_SERVER' => $_SERVER,
            '$_POST' => $_POST,
            '$_GET' => $_GET,
            '$_COOKIE' => $_COOKIE,
            '$_FILES' => $_FILES,
            '$body' => file_get_contents( "php://input" ),
            '$settings' => $settings,
        );
    }

    /**
     * @param array $settings Services settings
     */
    public function setSettings( array $settings )
    {
        $this->dependencies['$settings'] = $settings;
    }

    /**
     * Get Repository object
     *
     * @uses get()
     * @return \eZ\Publish\API\Repository\Repository
     */
    public function getRepository()
    {
        return $this->get( 'repository' );
    }

    /**
     * Get Request object
     *
     * @uses get()
     * @return \HiMVC\Core\MVC\Values\Request
     */
    public function getRequest()
    {
        return $this->get( 'request' );
    }

    /**
     * Get Router object
     *
     * @uses get()
     * @return \HiMVC\Core\MVC\Router
     */
    public function getRouter()
    {
        return $this->get( 'router' );
    }

    /**
     * Get ViewDispatcher object
     *
     * @uses get()
     * @return \HiMVC\Core\MVC\View\ViewDispatcher
     */
    public function getViewDispatcher()
    {
        return $this->get( 'viewDispatcher' );
    }

    /**
     * Get Dispatcher object
     *
     * @uses get()
     * @return \HiMVC\Core\MVC\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->get( 'dispatcher' );
    }

    /**
     * Get Module objects
     *
     * @uses get()
     * @return \HiMVC\Core\Common\Module[]
     * @todo Fix the fact that a seperate call to this function after settings are reloaded could return other modules
     *       (aka the collection is not shared)
     */
    public function getModules()
    {
        $modules = array();
        foreach ( $this->getListOfExtendedServices( '@:module' ) as $modulePrefix => $moduleName )
        {
            $modules[$modulePrefix] = $this->get( ltrim( $moduleName, '@' ) );
        }
        return $modules;
    }

    /**
     * Get a variable dependency
     *
     * @param string $variable
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getVariable( $variable )
    {
        $variableKey = "\${$variable}";
        if ( isset( $this->dependencies[$variableKey] ) )
        {
            return $this->dependencies[$variableKey];
        }

        throw new InvalidArgumentException(
            "{$variableKey}",
            'Could not find this variable among existing dependencies'
        );
    }

    /**
     * Set a variable dependency
     *
     * @param string $variable
     * @param mixed $value
     */
    public function setVariable( $variable, $value )
    {
        $this->dependencies["\${$variable}"] = $value;
    }

    /**
     * Get service by name
     *
     * @uses lookupArguments()
     * @throws BadConfiguration
     * @throws MissingClass
     * @param string $serviceName
     * @return object
     */
    public function get( $serviceName )
    {
        $serviceKey = "@{$serviceName}";

        // Return directly if it already exists
        if ( isset( $this->dependencies[$serviceKey] ) )
        {
            return $this->dependencies[$serviceKey];
        }

        if ( empty( $this->dependencies['$settings'][$serviceName] ) )// Validate settings
        {
            throw new BadConfiguration( "service\\[{$serviceName}]", "no settings exist for '{$serviceName}'" );
        }

        $settings = $this->dependencies['$settings'][$serviceName] + array( 'shared' => true );
        if ( empty( $settings['class'] ) )
        {
            throw new BadConfiguration( "service\\[{$serviceName}]\\class", 'class setting is not defined' );
        }
        else if ( !class_exists( $settings['class'] ) )
        {
            throw new MissingClass( $settings['class'], 'service' );
        }

        // Expand arguments with other service objects on arguments that start with @ and predefined variables that start with $
        if ( !empty( $settings['arguments'] ) )
        {
            $arguments = $this->lookupArguments( $settings['arguments'], true );
            if ( !empty( $settings['PreFilters'] ) )
            {
                $arguments = $this->filter(
                    $this->recursivlyLookupArguments( $settings['PreFilters'] ),
                    $arguments
                );
            }
        }
        else
        {
            $arguments = array();
        }

        // Create new object
        if ( !empty( $settings['factory'] ) )
        {
            $serviceObject = call_user_func_array( "{$settings['class']}::{$settings['factory']}", $arguments );
        }
        else if ( empty( $arguments ) )
        {
            $serviceObject = new $settings['class']();
        }
        else if ( isset( $arguments[0] ) && !isset( $arguments[2] ) )
        {
            if ( !isset( $arguments[1] ) )
                $serviceObject = new $settings['class']( $arguments[0] );
            else
                $serviceObject = new $settings['class']( $arguments[0], $arguments[1] );
        }
        else
        {
            $reflectionObj = new ReflectionClass( $settings['class'] );
            $serviceObject =  $reflectionObj->newInstanceArgs( $arguments );
        }

        if ( $settings['shared'] )
            $this->dependencies[$serviceKey] = $serviceObject;

        if ( !empty( $settings['MethodInjection'] ) )
        {
            $list = $this->recursivlyLookupArguments( $settings['MethodInjection'] );
            foreach ( $list as $methodName => $arguments )
            {
                foreach ( $arguments as $argumentKey => $argumentValue )
                    $serviceObject->$methodName( $argumentValue, $argumentKey );
            }
        }

        if ( !empty( $settings['PostListeners'] ) )
        {
            $this->notify(
                $this->recursivlyLookupArguments( $settings['PostListeners'] ),
                $serviceObject
            );
        }

        return $serviceObject;
    }

    /**
     * Lookup arguments for variable, service or arrays for recursive lookup
     *
     * 1. Does not keep keys of first level arguments
     * 2. Exists loop when it encounters optional non existing service dependencies
     *
     * @uses getServiceArgument()
     * @uses recursivlyLookupArguments()
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If undefined variable is used.
     * @param array $arguments
     * @param bool $recursivly
     * @return array
     */
    protected function lookupArguments( array $arguments, $recursivly = false )
    {
        $builtArguments = array();
        foreach ( $arguments as $argument )
        {
            if ( isset( $argument[0] ) && ( $argument[0] === '$' || $argument[0] === '@'  || $argument[0] === '%' ) )
            {
                $serviceObject = $this->getServiceArgument( $argument );
                if ( $argument[1] === '?' && $serviceObject === null )
                    break;

                $builtArguments[] = $serviceObject;
            }
            else if ( $recursivly && is_array( $argument ) )
            {
                $builtArguments[] = $this->recursivlyLookupArguments( $argument );
            }
            else // Scalar values
            {
                $builtArguments[] = $argument;
            }
        }
        return $builtArguments;
    }

    /**
     * Lookup arguments for variable, service or arrays for recursive lookup
     *
     * 1. Keep keys of arguments
     * 2. Does not exit loop on optional non existing service dependencies
     *
     * @uses getServiceArgument()
     * @uses recursivlyLookupArguments()
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If undefined variable is used.
     * @param array $arguments
     * @return array
     */
    protected function recursivlyLookupArguments( array $arguments )
    {
        $builtArguments = array();
        foreach ( $arguments as $key => $argument )
        {
            if ( isset( $argument[0] ) && ( $argument[0] === '$' || $argument[0] === '@'  || $argument[0] === '%' ) )
            {
                $serviceObject = $this->getServiceArgument( $argument );
                if ( $argument[1] !== '?' || $serviceObject !== null )
                    $builtArguments[$key] = $serviceObject;
            }
            else if ( is_array( $argument ) )
            {
                $builtArguments[$key] = $this->recursivlyLookupArguments( $argument );
            }
            else // Scalar values
            {
                $builtArguments[$key] = $argument;
            }
        }
        return $builtArguments;
    }

    /**
     * @uses getListOfExtendedServices()
     * @uses recursivlyLookupArguments()
     * @param $argument
     * @return array|closure|mixed|object|null Null on non existing optional dependencies
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue
     */
    protected function getServiceArgument( $argument )
    {
        $function = '';
        $serviceContainer = $this;
        if ( stripos( $argument, '::' ) !== false )// callback
            list( $argument, $function  ) = explode( '::', $argument );

        if ( ( $argument[0] === '%' || $argument[0] === '@' ) && $argument[1] === ':' )// expand extended services
        {
            return $this->recursivlyLookupArguments( $this->getListOfExtendedServices( $argument, $function ) );
        }
        elseif ( $argument[0] === '%' )// lazy loaded services
        {
            // Optional dependency handling
            if ( $argument[1] === '?' && !isset( $this->dependencies['settings'][substr( $argument, 2 )] ) )
                return null;

            if ( $function !== '' )
                return function() use ( $serviceContainer, $argument, $function ){
                    $serviceObject = $serviceContainer->get( ltrim( $argument, '%' ) );
                    return call_user_func_array( array( $serviceObject, $function ), func_get_args() );
                };
            else
                return function() use ( $serviceContainer, $argument ){
                    return $serviceContainer->get( ltrim( $argument, '%' ) );
                };
        }
        else if ( isset( $this->dependencies[ $argument ] ) )// Existing dependencies (@Service / $Variable)
        {
            $serviceObject = $this->dependencies[ $argument ];
        }
        else if ( $argument[0] === '$' )// Undefined variables will trow an exception
        {
            // Optional dependency handling
            if ( $argument[1] === '?' )
                return null;

            throw new InvalidArgumentValue( "\$arguments", $argument );
        }
        else// Try to load a @service dependency
        {
            // Optional dependency handling
            if ( $argument[1] === '?' && !isset( $this->dependencies['settings'][substr( $argument, 2 )] ) )
                return null;

            $serviceObject = $this->get( ltrim( $argument, '@' ) );
        }

        if ( $function !== '' )
            return array( $serviceObject, $function );

        return $serviceObject;
    }

    /**
     * @param string $parent Eg: %:controller
     * @param string $function Optional function string
     * @return array
     */
    protected function getListOfExtendedServices( $parent, $function = '' )
    {
        $prefix = $parent[0];
        $parent = ltrim( $parent, '@%' );// Keep starting ':' on parent for easier matching bellow
        $services = array();
        if ( $function !== '' )
            $function = '::' . $function;

        foreach ( $this->dependencies['$settings'] as $service => $settings )
        {
            if ( stripos( $service, $parent ) !== false &&
                 preg_match( "/^(?P<prefix>[\w:]+){$parent}$/", $service, $match ) )
            {
                $services[$match['prefix']] = $prefix . $match['prefix'] . $parent . $function;
            }
        }
        return $services;
    }

    /**
     * Filter a value on all listeners
     *
     * All listerneres must return ALL $params even if they are not modified!
     *
     * @param array $listerners
     * @param array $arguments The arguments for the specific event as simple array structure (not hash)
     * @return mixed $params param after being filtered by filters, or unmodified if no filters or not changed
     */
    protected function filter( array $listerners, array $arguments )
    {
        foreach ( $listerners as $listener )
        {
            $arguments = call_user_func_array( $listener, $arguments );
        }
        return $arguments;
    }

    /**
     * Notify all listeners
     *
     * For use when a service has been created
     *
     * @param array $listerners
     * @param object $serviceObject The Service object just after being created.
     */
    protected function notify( array $listerners, $serviceObject )
    {
        foreach ( $listerners as $listener )
        {
            call_user_func( $listener, $serviceObject );
        }
    }
}
