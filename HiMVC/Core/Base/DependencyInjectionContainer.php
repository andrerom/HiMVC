<?php
/**
 * Service Container class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @copyright Copyright (C) 2009-2012 github.com/andrerom. All rights reserved.
 * @license http://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License v3
 * @version //autogentag//
 */

namespace HiMVC\Core\Base;
use eZ\Publish\Core\Base\Exceptions\BadConfiguration,
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue,
    eZ\Publish\Core\Base\Exceptions\InvalidArgumentException,
    eZ\Publish\Core\Base\Exceptions\MissingClass,
    ReflectionClass,
    HiMVC\API\Container;

/**
 * Service container class
 *
 * A dependency injection container that uses configuration for defining dependencies.
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
 *
 * "arguments" values in service.ini can start with either @ in case of other services being dependency, $ if a
 * predefined global variable is to be used ( currently: $_SERVER, $_POST, $_GET, $_COOKIE, $_FILES and $body )
 * or plain scalar if that is to be given directly as argument value.
 * If the argument value starts with %, then it is a lazy loaded service provided as a callback (closure).
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
     * Array of optional settings overrides
     *
     * @var array[]
     */
    private $settings;

    /**
     * Construct object with optional configuration overrides
     *
     * @param array $settings Services settings
     * @param mixed[]|object[] $dependencies Optional initial dependencies
     */
    public function __construct( array $settings, array $dependencies = array() )
    {
        $this->settings = $settings;
        $this->dependencies = $dependencies + array(
            '$_SERVER' => $_SERVER,
            '$_POST' => $_POST,
            '$_GET' => $_GET,
            '$_COOKIE' => $_COOKIE,
            '$_FILES' => $_FILES,
            '$body' => file_get_contents( "php://input" ),
        );
    }

    /**
     * @param array $settings Services settings
     */
    public function setSettings( array $settings )
    {
        $this->settings = $settings;
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
     * @return \HiMVC\API\MVC\Values\Request
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
     * @return \HiMVC\Core\Base\Module[]
     * @todo Fix the fact that a seperate call to this function after settings are reloaded could return other modules
     *       (aka the collection is not shared)
     */
    public function getModules()
    {
        $modules = array();
        foreach ( $this->getListOfExtendedServices( '@-module' ) as $modulePrefix => $moduleName )
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

        $settings = $this->getSettings( $serviceName );
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
                    $this->lookupArguments( $settings['PreFilters'] ),
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
                $this->lookupArguments( $settings['PostListeners'] ),
                $serviceObject
            );
        }

        return $serviceObject;
    }

    /**
     * Lookup arguments for variable, service or arrays for recursive lookup
     *
     * Does not keep key of first level arguments!
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
                $builtArguments[] = $this->getServiceArgument( $argument );
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
     * Keep keys of arguments.
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
                $builtArguments[$key] = $this->getServiceArgument( $argument );
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
     * @return array|closure|mixed|object
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue
     */
    protected function getServiceArgument( $argument )
    {
        $function = '';
        $serviceContainer = $this;
        if ( stripos( $argument, '::' ) !== false )// callback
            list( $argument, $function  ) = explode( '::', $argument );

        if ( ( $argument[0] === '%' || $argument[0] === '@' ) && $argument[1] === '-' )// expand extended services
        {
            return $this->recursivlyLookupArguments( $this->getListOfExtendedServices( $argument, $function ) );
        }
        elseif ( $argument[0] === '%' )// lazy loaded services
        {
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
            throw new InvalidArgumentValue( "\$arguments", $argument );
        }
        else// Try to load a @service dependency
        {
            $serviceObject = $this->get( ltrim( $argument, '@' ) );
        }

        if ( $function !== '' )
            return array( $serviceObject, $function );

        return $serviceObject;
    }

    /**
     * @param string $parent Eg: %-controller
     * @param string $function Optional function string
     * @return array
     */
    protected function getListOfExtendedServices( $parent, $function = '' )
    {
        $prefix = $parent[0];
        $parent = ltrim( $parent, '@%' );
        $services = array();
        if ( $function !== '' )
            $function = '::' . $function;

        foreach ( $this->settings as $service => $settings )
        {
            if ( preg_match( "/^(?P<name>\w+){$parent}$/", $service, $match ) )
            {
                $services[$match['name']] = $prefix . $match['name'] . $parent . $function;
            }
        }
        return $services;
    }

    /**
     * @param $serviceName
     * @return array
     * @throws \eZ\Publish\Core\Base\Exceptions\BadConfiguration
     * @todo Consider adding support for several levels of settings inheretance, or consider adding settings includes
     */
    protected function getSettings( $serviceName )
    {
        if ( strpos( $serviceName, '-' ) )// If - is at a positive position, then service extends another one
        {
            $serviceParent = explode( '-', $serviceName );
            $serviceParent = '-' . $serviceParent[1];
            if ( !empty( $this->settings[$serviceName] ) && !empty( $this->settings[$serviceParent] ) )// Validate settings
            {
                return array_merge(
                    $this->settings[$serviceParent] + array( 'shared' => true ),
                    $this->settings[$serviceName]
                );// uses array_merge on puposes to make sure arguments are reset
            }
            else if ( !empty( $this->settings[$serviceParent] ) )
            {
                return $this->settings[$serviceParent] + array( 'shared' => true );
            }
        }

        if ( !empty( $this->settings[$serviceName] ) )// Validate settings
        {
            return $this->settings[$serviceName] + array( 'shared' => true );
        }

        throw new BadConfiguration( "service\\[{$serviceName}]", "no settings exist for '{$serviceName}'" );
    }


    /**
     * Filter a value on all listeners
     *
     * All listerneres must return ALL $params even if they are not modified!
     *
     * @param array $listerners
     * @param array $params The arguments for the specific event as simple array structure (not hash)
     * @return mixed $params param after being filtered by filters, or unmodified if no filters or not changed
     */
    protected function filter( array $listerners, array $params )
    {
        foreach ( $listerners as $listener )
        {
            $params = call_user_func_array( $listener, $params );
        }
        return $params;
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
