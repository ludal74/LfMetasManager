<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/LfMetasManager for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace LfMetasManager;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Config\Reader\Xml as XMLreader;

class Module implements AutoloaderProviderInterface
{
    
    protected $config;
    protected $configXmlFolder;
    protected $xmlFilesName;
    protected $xmlLanguageSeparator;
    protected $title;
    
    /**
     * 
     * @param MvcEvent $e
     */
    public function onBootstrap(MvcEvent $e)
    {      
        $config = $e->getApplication()->getServiceManager()->get("Config"); 
        
        $this->config               = $config["LfMetasManager"];
        $this->configXmlFolder      = $this->config["xmlFilesFolder"];
        $this->xmlFilesName         = $this->config["xmlFileName"];
        $this->xmlLanguageSeparator = $this->config["xmlLanguageSeparator"];
        
        $this->setMetas( $e );
    }
  
    /**
     *
     * @param MvcEvent $e
     */
    function setMetas(MvcEvent $e)
    {
        $e->getApplication()->getEventManager()->attach('route',function  ($e)
        {
        	$application     = $e->getApplication();
        	$services        = $application->getServiceManager();
        	$view            = $services->get('ViewHelperManager')->getRenderer();
        	$language        = $services->get("LfLanguageManagerService")->getLanguage();
        	$router          = $e->getRouter();
        	$request         = $services->get('request');
        
        	if (! is_null($router->match($request)))
        	{
        		$routeName = $router->match($request)->getMatchedRouteName();

        		if ( is_dir( $this->configXmlFolder ) )
        		{
        			$file       = $this->configXmlFolder."/".$this->xmlFilesName.$this->xmlLanguageSeparator.$language.".xml";
        				
        			if( file_exists( $file ) )
        			{  
        				$reader = new XMLreader();
        				$datas =  $reader->fromFile( $file );
        
        				if( array_key_exists( $routeName, $datas ) )
        				{
        					foreach( $datas[ $routeName ] as $meta => $value )
        					{
        						if( array_key_exists( $meta, $datas[ $routeName ] ) )
        						{
        							if( $meta == "title" )
        							{
        								$view->headTitle( $value );
        							}
        							else if( $meta == "ogtitle" )
        							{
        								$view->headMeta()->appendName( 'og::title', $value );
        							}
        							else if( $meta == "ogdescription" )
        							{
        								$view->headMeta()->appendName( 'og::description', $value );
        							}
        							else if( is_array( $value ) )
        							{
        								$arrayKeys = array_keys($value);
        								$view->headMeta()->appendName( $meta, implode( ',', $datas[ $routeName ][$meta][$arrayKeys[0] ] ) );
        							}
        							else
        							{
        								$view->headMeta()->appendName( $meta , $value );
        							}
        						}
        					}
        				}
        			}
        		}
        	}
        }, 1);
    }
    
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
		    // if we're in a namespace deeper than one level we need to fix the \ in the path
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\', '/' , __NAMESPACE__),
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
