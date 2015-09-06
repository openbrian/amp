<?php
namespace Amp\Httpd;
use Amp\Util\Filesystem;
use Amp\Permission\PermissionInterface;
use Symfony\Component\Templating\EngineInterface;

class VhostTemplate implements HttpdInterface {
  /**
   * @var string, path to which we should write new config files
   */
  private $dir;

  /**
   * @var Filesystem
   */
  private $fs;

  /**
   * @var string absolute path to a log directory
   */
  private $logDir;

  /**
   * @var PermissionInterface
   */
  private $perm;

  /**
   * @var boolean
   */
  private $withRewrite;

  /**
   * @var string, name of the template file
   */
  private $template;

  /**
   * @var EngineInterface
   */
  private $templateEngine;

  public function __construct() {
    $this->fs = new Filesystem();
  }

  /**
   * @param string $root local path to document root
   * @param string $url preferred public URL
   */
  public function createVhost($root, $url) {
    $parameters = parse_url($url);
    if (!$parameters || !isset($parameters['host'])) {
      throw new \Exception("Failed to parse URL: " . $url);
    }
    if (empty($parameters['port'])) {
      $parameters['port'] = 80;
    }
    $parameters['root'] = $root;
    $parameters['url'] = $url;
    $parameters['include_vhost_file'] = '';
    $parameters['log_dir'] = $this->getLogDir();
    $content = $this->getTemplateEngine()->render($this->getTemplate(), $parameters);
    $this->fs->dumpFile($this->createFilePath($root, $url), $content);

    $this->setupLogDir();

    if ($this->withRewrite && php_uname('s') == 'Linux') {
      $distro = trim(explode( ':', exec('lsb_release -i'))[1]);
      if (in_array($distro, array( 'Debian', 'Ubuntu' ))) {
        $this->enableModRewrite(); 
      }
  }

  public function setupLogDir() {
    $this->fs->mkdir($this->getLogDir());
    $this->getPerm()->applyDirPermission(PermissionInterface::WEB_WRITE, $this->getLogDir());
  }

  public function enableModRewrite() {
    exec( 'a2enmod rewrite' );
  }

  /**
   * @param string $root local path to document root
   * @param string $url preferred public URL
   */
  public function dropVhost($root, $url) {
    $this->fs->remove($this->createFilePath($root, $url));
  }

  /**
   * Determine the path to the configuration file for a given host
   *
   * @param $root
   * @param $url
   */
  public function createFilePath($root, $url) {
    $parameters = parse_url($url);
    if (empty($parameters['port'])) {
      $parameters['port'] = 80;
    }
    return $this->getDir() . DIRECTORY_SEPARATOR . $parameters['host'] . '_' . $parameters['port'] . '.conf';
  }

  /**
   * @param string $dir
   */
  public function setDir($dir) {
    $this->dir = $dir;
  }

  /**
   * @return string
   */
  public function getDir() {
    return $this->dir;
  }

  /**
   * @param string $logDir
   */
  public function setLogDir($logDir) {
    $this->logDir = $logDir;
  }

  /**
   * @return string
   */
  public function getLogDir() {
    return $this->logDir;
  }

  /**
   * @param \Amp\Permission\PermissionInterface $perm
   */
  public function setPerm($perm) {
    $this->perm = $perm;
  }

  /**
   * @return \Amp\Permission\PermissionInterface
   */
  public function getPerm() {
    return $this->perm;
  }

  /**
   * @param boolean $withRewrite
   */
  public function setWithRewrite($withRewrite) {
    $this->withRewrite = $withRewrite;
  }

  /**
   * @return boolean
   */
  public function getWithRewrite() {
    return $this->withRewrite;
  }

  /**
   * @param string $template
   */
  public function setTemplate($template) {
    $this->template = $template;
  }

  /**
   * @return string
   */
  public function getTemplate() {
    return $this->template;
  }

  /**
   * @param \Symfony\Component\Templating\EngineInterface $templateEngine
   */
  public function setTemplateEngine($templateEngine) {
    $this->templateEngine = $templateEngine;
  }

  /**
   * @return \Symfony\Component\Templating\EngineInterface
   */
  public function getTemplateEngine() {
    return $this->templateEngine;
  }

}