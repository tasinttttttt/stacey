<?php

namespace Stacey\Core\Parser;

use Stacey\Extension\Config;
use Stacey\Extension\StaceyTwigExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

final class TemplateParser
{

  static function find_template($template)
  {
    if (!file_exists($template)) {
      throw new \Exception('\'' . $template . '\' template not found.');
    }
    return preg_replace('/.+\//', '', $template);
  }

  static function parse($data, $template)
  {

    $template = self::find_template($template);

    // Twig_Autoloader::register();
    $loader = new FilesystemLoader(Config::$templates_folder);
    $cache = is_writable(Config::$cache_folder . '/templates') ? Config::$cache_folder . '/templates' : false;
    $twig = new Environment($loader, array(
      'cache' => $cache,
      'auto_reload' => true,
      'autoescape' => false
    ));
    $twig->addExtension(new StaceyTwigExtension());

    return $twig->render($template, array('page' => $data));
  }
}
