<?php
namespace Former\Facades;

use \Illuminate\Container\Container;

class FormerAgnostic
{
  /**
   * The Container instance
   * @var Container
   */
  private static $app;

  /**
   * Static facade
   */
  public static function __callStatic($method, $parameters)
  {
    if (!static::$app) static::$app = static::getApp();
    $callable = array(static::$app['former'], $method);

    return call_user_func_array($callable, $parameters);
  }

  /**
   * Build the Former application
   *
   * @return Container
   */
  private static function getApp()
  {
    $app = new Container;

    // Illuminate ------------------------------------------------------ /

    $app->alias('Symfony\Component\HttpFoundation\Request', 'request');
    $app->bind('files', 'Illuminate\Filesystem\Filesystem');
    $app->bind('url', 'Illuminate\Routing\UrlGenerator');
    $app->instance('Illuminate\Container\Container', $app);

    $app->bind('session', function($app) {
      $request   = new \Illuminate\Http\Request;
      $encrypter = new \Illuminate\Encryption\Encrypter('foobar');
      $cookie    = new \Illuminate\Cookie\CookieJar($request, $encrypter, array());

      return new \Illuminate\Session\CookieStore($cookie);
    });

    $app->bind('Symfony\Component\HttpFoundation\Request', function($app) {
      $request = new \Illuminate\Http\Request;
      $request->setSessionStore($app['session']);

      return $request;
    });

    $app->bind('config', function($app) {
      $fileloader = new \Illuminate\Config\FileLoader($app['files'], 'src/');

      return new \Illuminate\Config\Repository($fileloader, 'config');
    });

    $app->bind('loader', function($app) {
      return new \Illuminate\Translation\FileLoader($app['files'], 'src/config');
    });
    $app->bind('translator', function($app) {
      return new \Illuminate\Translation\Translator($app['loader'], 'fr', 'en');
    });

    // Meido ------------------------------------------------------- /

    $app->bind('html', '\Meido\HTML\HTML');
    $app->bind('form', '\Meido\Form\Form');

    // Former ------------------------------------------------------ /

    $app->bind('Former\Interfaces\FrameworkInterface', '\Former\Framework\TwitterBootstrap');
    $app->singleton('former', '\Former\Former');

    return $app;
  }
}
