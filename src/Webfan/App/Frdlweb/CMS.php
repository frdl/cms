<?php
namespace Webfan\App\Frdlweb;

use League\CommonMark\CommonMarkConverter;
use Spyc;
//error_reporting(E_ALL);
//ini_set("display_errors", 1);


class CMS
{
	protected $allowPhp = false;
	protected $options;
	protected $converter;	
	
	public function __construct(array $options = null,  $allowPhp = false){
        $this->allowPhp=$allowPhp;
		$this->options = $this->getDefaultOptions($options);
		

		$this->converter = new CommonMarkConverter([    
			'html_input' => 'strip',    
			'allow_unsafe_links' => false,
		]);		
		
		 
	}

	
	
  public function __invoke($type, $slug, $content_variable = '') {
  $filename_without_extension = trim( $this->options['content-dir'], '//\\') . '/' . $type . '/' . $slug;
  if (is_file($filename_without_extension . '.md')) {
    $filename = $filename_without_extension . '.md';
  }
  else if (is_file($filename_without_extension . \DIRECTORY_SEPARATOR.'index.md')) {
     $filename = $filename_without_extension . \DIRECTORY_SEPARATOR.'index.md';
  }
  else if (is_file($filename_without_extension . '.html')) {
    $filename = $filename_without_extension . '.html';
  }
  else if (is_file($filename_without_extension . '.htm')) {
    $filename = $filename_without_extension . '.htm';
  }
  else if (true===$this->allowPhp && is_file($filename_without_extension . '.php')) {
    $filename = $filename_without_extension . '.php';
  }
  else {
    //echo 'not found: ' . $filename_without_extension . '<br><br>';
    return FALSE;
  }

 // ob_start();
 // include $filename;
 // $content = ob_get_clean();
        $content = file_get_contents($filename);
	  
	  
  $this->parseFrontmatter($content);

  $content = $this->mustache_substitute($content, $content_variable);

  if ($type != 'themes') {


    // Parse markdown
  //  global $Parsedown;
  //  $content = $Parsedown->text($content);
	  //global $converter;
      $content = $this->converter->convert($content);

    // Wrap it in template, if there is one
    //global $theme;
    $wrapped_content = $this('themes', $this->options['frontmatter']['theme'] . '/' . $type . '-' . $slug, $content);
    if ($wrapped_content !== FALSE) {
      $content = $wrapped_content;
    }
    else {
      $wrapped_content = $this('themes', $this->options['frontmatter']['theme'] . '/' . $type, $content);
      if ($wrapped_content !== FALSE) {
        $content = $wrapped_content;
      }
    }
  }

  return $content;
}	
	
	
	protected function mustache_substitute($text, $content_variable) {
       $self = &$this;
  return preg_replace_callback('/{{((?:[^}]|}[^}])+)}}/', function($matches) use ($content_variable, &$self) {
   // global $Parsedown;
   // global $frontmatter_options;

    $tag = trim($matches[1]);

    switch ($tag) {
  /*
      case 'title':
        // If first line in md-file is a heading, use that as the title
        preg_match('/#\s*(.*)/', $page_md, $matches);
        if (count($matches) == 2) {
          return $matches[1];
        }
        return 'default title';*/
      case 'main':
      case 'content':
        return $content_variable;
//        return $Parsedown->text($c);

      case 'theme-name': 
        return $self->options['frontmatter']['theme'];

      case 'root-url': 
        return $self->options['content-dir'];

      case 'theme-url': 
        return $self->options['themes-dir'] .$self->options['frontmatter']['theme'] . '/';
    }
    if (isset( $self->option['frontmatter'][$tag])) {
      return  $self->option['frontmatter'][$tag];
    }

/*
    global $shortcodes_path;
    if (is_file($shortcodes_path . '/' . $tag . '.php')) {
      ob_start();
      include $shortcodes_path . '/' . $tag . '.php';
      return ob_get_clean();
    }*/

    $block_html = $self('blocks', $tag, $content_variable);
    if ($block_html !== FALSE) {
      return $block_html;
    }

    return '';
//    return 'unknown tag: "' . $tag . '"';
  }, $text);
}
	
	
	
	protected function parseFrontmatter(&$text_md) {
     //  $frontmatter_options = $this->option['frontmatter'];

  if (strncmp($text_md, "+++", 3) === 0) {
    // TOML format, but only partly supported
    $endpos = strpos($text_md, '+++', 3);
    $frontmatter = trim(substr($text_md, 3, $endpos - 3));
    $text_md = substr($text_md, $endpos + 3);

    $lines = preg_split("/\\r\\n|\\r|\\n/", $frontmatter);

    $group_prefix = '';
    foreach ($lines as $line) {
      // Grouping
      if (preg_match('/\[(.*)\]/', $line, $matches)) {
        $group_prefix = $matches[1] . '.';
      }
      // String assignments
      if (preg_match('/([\w-]+)\\s*=\\s*([\'"])(.*)\\2/', $line, $matches)) {
         $this->option['frontmatter'][$group_prefix . $matches[1]] = $matches[3];
      }
    }
  }
  if (strncmp($text_md, "---", 3) === 0) {
    $endpos = strpos($text_md, '---', 3);
    $frontmatter = trim(substr($text_md, 3, $endpos - 3));
    $text_md = substr($text_md, $endpos + 3);

    $array = Spyc::YAMLLoadString($frontmatter);

    foreach ($array as $index => $item) {
       $this->option['frontmatter'][$index] = $item;
    }
  }

	}
	
	protected function getDefaultOptions(array $options = null){
		
		if(null===$options){
		  $options = [];	
		}
			
		if(!isset($options['dir'])){
			$options['dir'] = getcwd();
		}
		if(!isset($options['configfile'])){
			$options['configfile'] = '_config.yaml';
		}
		if(!isset($options['content-dir'])){
			$options['content-dir'] = is_dir($options['dir'] . \DIRECTORY_SEPARATOR.'content')
				          ? \DIRECTORY_SEPARATOR.'content'.\DIRECTORY_SEPARATOR
				          : \DIRECTORY_SEPARATOR;
		}		
		if(!isset($options['themes-dir'])){
			$options['themes-dir'] =$options['content-dir'].\DIRECTORY_SEPARATOR.'themes'.\DIRECTORY_SEPARATOR;
		}		
		
		$frontmatter = file_exists($options['dir'] . \DIRECTORY_SEPARATOR.$options['configfile'])
			        ? Spyc::YAMLLoad($options['dir'] . \DIRECTORY_SEPARATOR.$options['configfile'])
					: [
						'theme' =>'basic-with-responsive-menu'					
					];
		
		$o = [
			'parser' => [		   
				'html_input' => 'strip',           
				'allow_unsafe_links' => false,			
			],
			
			'frontmatter' =>  Spyc::YAMLLoad($options['dir'] . \DIRECTORY_SEPARATOR.$options['configfile'])
		];
		$res = array_merge($o, $options);		
		$res['frontmatter']['theme'] = str_replace('//\\', '__INVLID__', $res['frontmatter']['theme'] );
		$res['content-dir'] = str_replace('.', '__INVLID__', $res['content-dir']);
		return $res;
	}

	
}
