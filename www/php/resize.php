<?php


class makeAlbum
{
  private $config = array(
    'dir'           => '/photo/2002/0109/',
    'tn_dir'        => 'tn',
    'recursive'     => false,
    'rewrite_tn'    => true,
    'size_original_w' => 800,
    'size_original_h' => 600,
    'size_tn'       => 100,
    'html'          => true,
    'html_only'     => false,
    'html_name'     => 'index.html',
    'readme_file'   => 'readme.txt',
    'default_albumname' => 'Фотки',
    'delpath' => '',
    'random_imgs' => 5
  );
  protected $dirs = array();
  private $html = array();
  function __construct()
  {
    include_once('classes/ImageResizer.class.php');
    $this->img = new ImageResizer;


  }

  function setConfig($var, $value)
  {
    $this->config[$var] = $value;
  }
  function setHTML($var, $value)
  {
    $this->html[$var] = $value;
  }

  function parseDirs($dir)
  {
    $dir = rtrim(strtr($dir, '\\','/'),'/');

    if(!is_dir($dir)) return false; // если нет такой папки, то выходим
    if(is_dir($dir.'/'.$this->config['tn_dir']) AND !$this->config['rewrite_tn']) return false; // если есть папка tn и её нельза переписывать, то выходим
    $files = array();
    foreach (scandir($dir) as $dirname)
    {
      if($dirname == '.' or $dirname == '..') continue;
      if(is_dir($dir.'/'.$dirname) AND $this->config['recursive'])
      {
        $this->parseDirs($dir.'/'.$dirname);
      }elseif(!$this->config['html_only']){
        switch (substr(strtolower($dirname),-3))
        {
        	case 'jpg':
        	case 'jpeg':
        	case 'gif':
        	  $this->convertOrig( $dir.'/'.$dirname, $dir.'/'.$dirname);
        	  echo $dir.'/'.$dirname."\n";
            $this->convertTn($dir.'/'.$dirname);
            echo $dir.'/'.$this->config['tn_dir'].'/'.$dirname."\n";
            $files[$dirname] = $this->config['tn_dir'].'/'.$dirname;
        		break;

        	default:

        		break;
        }
      }else{
        switch (substr(strtolower($dirname),-3))
        {
        	case 'jpg':
        	case 'jpeg':
        	case 'gif':
        	  $files[$dirname] = $this->config['tn_dir'].'/'.$dirname;
        	  break;
        }
      }

    }

    if($this->config['html'])
    {
      $this->makeHTML($dir, $files);
      echo $dir.'/'.$this->config['html_name']."\n";
    }
  }

  function run($method)
  {
    print $method;

    switch ($method) {
    	case 'parseDirs':
    		if(is_dir($this->config['dir'])) $this->parseDirs($this->config['dir']);
    		break;
    	case 'scanForTNdirs':
    		if(is_dir($this->config['dir'])) $this->scanForTNdirs($this->config['dir']);
    		$this->makeHTMLPhotoDir();
    		print_r($this->dirs);
    		break;
    	default:
    		break;
    }

  }
  function convertOrig($input, $output)
  {
    $sz = getimagesize($input);
    if($sz[0] > $this->config['size_original_w'])
    {
      $this->img->resize($this->config['size_original_w'], $this->config['size_original_h'], $input, $output); // конвертим оригинал
    }elseif($sz[1] > $this->config['size_original_h']){
      $this->img->resize($this->config['size_original_h'], $this->config['size_original_h'], $input, $output); // конвертим оригинал
    }
  }

  function convertTn($input)
  {
    $output_dir = dirname($input).'/'.$this->config['tn_dir'];
    $basename  = basename($input);

	  if(!is_dir($output_dir) OR $this->config['rewrite_tn'] OR !file_exists($output_dir.'/'.$basename)) // если нет папки tn или можно её переписать или нет такого файла даже
	  {
      if(!is_dir($output_dir))                    mkdir($output_dir, 0777,true); // если нет такой папки - создаём
      if(file_exists($output_dir.'/'.$basename))  unlink($output_dir.'/'.$basename); // если нет такого файла и нужно его переписать - стираем
      $this->img->resize($this->config['size_tn'], $this->config['size_tn'], $input, $output_dir.'/'.$basename); //конвертим tn
	  }
  }

  function makeHTML($dir, $files = array())
  {
    $body = '';
    if(!count($files)) return false;
    foreach ($files as $orig => $tn)
    {
      $this->config['delpath'] = strtr($this->config['delpath'], '\\','/');
      $orig = preg_replace('|'.$this->config['delpath'].'|','',$orig);
      $tn   = preg_replace('|'.$this->config['delpath'].'|','',$tn);
      $tr = array(
        '%%%img_full%%%'        => $orig,
        '%%%img_tn%%%'          => $tn,
        '%%%title%%%'           => $orig,
        '%%%config_size_tn%%%'  => $this->config['size_tn']
      );
      $body .= strtr($this->html['body'], $tr);
    }
    if(file_exists($dir.'/'.$this->config['readme_file']))
    {
      $tr = array('%%%album_title%%%' => file_get_contents($dir.'/'.$this->config['readme_file']));
    }else{
      $tr = array('%%%album_title%%%' => '%%%%album_title%%%', $this->config['default_albumname'].' - '.basename($dir));
    }
    $head = strtr($this->html['head'], $tr);
    $foot = strtr($this->html['foot'], $tr);
    file_put_contents($dir.'/'.$this->config['html_name'], $head.$body.$foot);
  }



  function scanForTNdirs($dir)
  {
    $dir = rtrim(strtr($dir, '\\','/'),'/');

    if(!is_dir($dir)) return false; // если нет такой папки, то выходим

    $files = array();
    foreach (scandir($dir) as $dirname)
    {
      if($dirname == '.' or $dirname == '..') continue;
      if(is_dir($dir.'/'.$dirname))
      {
        $this->scanForTNdirs($dir.'/'.$dirname);
      }else{
        switch (substr(strtolower($dirname),-3))
        {
        	case 'jpg':
        	case 'jpeg':
        	case 'gif':
        	  $files[$dirname] = $this->config['tn_dir'].'/'.$dirname;
        	  break;
        }
      }

    }
    if(is_dir($dir.'/'.$this->config['tn_dir']) and count($files) and scandir($dir.'/'.$this->config['tn_dir']))
    {
      $this->dirs[dirname($dir)][] = $dir;
    }

  }

  function makeHTMLPhotoDir()
  {
    if(!count($this->dirs)) return false;
    $output = '';
    $this->config['delpath'] = strtr($this->config['delpath'], '\\','/');
    foreach ($this->dirs as $parentdir => $arr)
    {
    	$sub_body = '';
      foreach ($arr as $dir)
    	{
        $imgs = '';
    	  if(file_exists($dir.'/'.$this->config['readme_file']))
    	  {
          $dir_readme = file_get_contents($dir.'/'.$this->config['readme_file']);
    	  }else{
    	    $dir_readme = basename($dir);
    	  }
    	  if(file_exists($parentdir.'/'.$this->config['readme_file']))
    	  {
          $parent_dir_readme = file_get_contents($dir.'/'.$this->config['readme_file']);
    	  }else{
    	    $parent_dir_readme = basename($parentdir);
    	  }
        $files = scandir($dir.'/'.$this->config['tn_dir']);
        foreach ($files as $key => $value) {
          switch (substr(strtolower($value),-3))
          {
          	case 'jpg':
          	case 'jpeg':
          	case 'gif':
          	  // всё гуд
          	  break;
          	  default: unset($files[$key]);
          }
        	if($value == '.' or $value == '..') unset($files[$key]);
        }
        $rand_imgs = $this->config['random_imgs'];
        if(count($files) < $rand_imgs) $rand_imgs = count($files);
        foreach (array_rand($files , $rand_imgs) as $key)
        {
        	$imgs .= "\n".'<img src="'.preg_replace('|'.$this->config['delpath'].'|','',$dir).'/'.$this->config['tn_dir'].'/'.$files[$key].'" />';
        }
        $imgs .= "\n<hr />\n";
  	    $tr = array(
          '%%%parent_dir_name%%%'   => basename($parentdir),
          '%%%parent_dir_readme%%%' => $parent_dir_readme,
          '%%%dir_html%%%'          => $dir.'/'.$this->config['html_name'],
          '%%%dir_readme%%%'        => $dir_readme,
          '%%%dir_fotos%%%'         => $imgs
        );
        $sub_body .= strtr($this->html['sub_body'],$tr);
    	}
  	    $tr = array(
          '%%%parent_dir_name%%%'   => basename($parentdir),
          '%%%parent_dir_readme%%%' => $parent_dir_readme,
          '%%%dir_html%%%'          => $dir.'/'.$this->config['html_name'],
          '%%%dir_readme%%%'        => $dir_readme,
          '%%%dir_fotos%%%'         => $imgs,
          '%%%sub_body%%%'          => $sub_body
        );
    	$output .= strtr($this->html['body'],$tr);
    }
    file_put_contents(__file__.'1.html', $this->html['head'].$output.$this->html['foot']);
  }
}


$html['head'] = <<<EOL
<html>
  <head>
      <title>%%%album_title%%%</title>
      <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
      <link rel="stylesheet" href="/css/lightbox.css" type="text/css" media="screen" />
      <link rel="stylesheet" href="/css/style.css" type="text/css" media="screen" />
      <link rel="stylesheet" href="/css/index.css" type="text/css" media="screen" />
      <script type="text/javascript" src="/js/prototype.lite.js"></script>
      <script type="text/javascript" src="/js/moo.fx.js"></script>
      <script type="text/javascript" src="/js/litebox-1.0.js"></script>
  </head>

  <body onload="initLightbox()">
    <div class="main">
      <div class="top_menu">
        <a href="/">Главная страница</a> &middot;
        <a href="/photo/">Фотки</a> &middot;
        <a href="/links/">Ссылки</a>
      </div>
EOL;
$html['body'] = <<<EOL
      <div class="photos">
        <div id="%%%parent_dir_name%%%-title" class="containerTitle" onclick="openClose('%%%parent_dir_name%%%-container');">%%%parent_dir_readme%%%</div>
        <div id="%%%parent_dir_name%%%-container" class="containerBody">
            %%%sub_body%%%
        </div>
      </div>

EOL;
$html['sub_body'] = <<<EOL
<div class="photosTitle"><a href="%%%dir_html%%%">%%%dir_readme%%%</a></div>
%%%dir_fotos%%%
<hr />
EOL;

$html['foot'] = <<<EOL

    </div>
  </body>
</html>
EOL;


$maker = new makeAlbum();
$maker->setConfig('dir', 'D:\HTDOCS\asid666\www\photo');
$maker->setConfig('delpath', 'D:/HTDOCS/asid666/www');
$maker->setHTML('head',$html['head']);
$maker->setHTML('body',$html['body']);
$maker->setHTML('sub_body',$html['sub_body']);
$maker->setHTML('foot',$html['foot']);
$maker->run('scanForTNdirs');
die;


$html['head'] = <<<EOL
<html>
  <head>
      <title>%%%album_title%%%</title>
      <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
      <link rel="stylesheet" href="/css/lightbox.css" type="text/css" media="screen" />
      <link rel="stylesheet" href="/css/style.css" type="text/css" media="screen" />
      <link rel="stylesheet" href="/css/index.css" type="text/css" media="screen" />
      <script type="text/javascript" src="/js/prototype.lite.js"></script>
      <script type="text/javascript" src="/js/moo.fx.js"></script>
      <script type="text/javascript" src="/js/litebox-1.0.js"></script>
  </head>

  <body onload="initLightbox()">
    <div class="main">
      <div class="top_menu">
        <a href="/">Главная страница</a> &middot;
        <a href="/photo/">Фотки</a> &middot;
        <a href="/links/">Ссылки</a>
      </div>
      <div class="photos">
        <div class="photosTitle">%%%album_title%%%</div>
        <div class="photosTitle s16"><a href="/photo/">Вернуться к альбомам</a></div>
<br />
EOL;
$html['body'] = <<<EOL
          <a href="%%%img_full%%%"  rel="lightbox[album]" title="%%%title%%%"><img src="%%%img_tn%%%" title="%%%title%%%" width="%%%config_size_tn%%%"/></a>

EOL;

$html['foot'] = <<<EOL

      </div>
    </div>
  </body>
</html>
EOL;


$maker->setConfig('dir', 'D:\HTDOCS\asid666\www\photo\2002');
$maker->setConfig('delpath', 'D:/HTDOCS/asid666/www/');
//$maker->setConfig('html_only', true);
$maker->setConfig('rewrite_tn',false);
$maker->setConfig('recursive', true);
$maker->setHTML('head',$html['head']);
$maker->setHTML('body',$html['body']);
$maker->setHTML('foot',$html['foot']);
$maker->run();



?>