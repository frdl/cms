<?php
namespace Webfan\App\Frdlweb;

use League\CommonMark\ConverterInterface;
use League\CommonMark\CommonMarkConverter;
use Spyc;

class CMS
{
	protected $allowPhp = false;
	protected $options;
	protected $converter;	
	
	
	public function __construct(array $options = null,  $allowPhp = false, ConverterInterface $converter = null){       
		$this->allowPhp=$allowPhp;
		$this->options = $this->getDefaultOptions($options);		
		$this->converter =(null !== $converter)
			         ? $converter
			         : new CommonMarkConverter( $this->options['parser'] );				 
	}

	
	
  public function __invoke($type, $slug, $content_variable = '') {
	  $includePhp = false;
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
    $includePhp = true;	  
  }
  else {
    //echo 'not found: ' . $filename_without_extension . '<br><br>';
    return false;
  }

	  if(true===$this->allowPhp && true === $includePhp){
                ob_start();
		 require $filename;
		$content = ob_get_clean();		  
	  }else{		
	      $content = file_get_contents($filename);
	  }
 
	  
  $this->parseFrontmatter($content);

  $content = $this->mustache_substitute($content, $content_variable);

  if ($type != 'themes') {
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
    if (isset( $self->options['frontmatter'][$tag])) {
      return  $self->options['frontmatter'][$tag];
    }

/*
    global $shortcodes_path;
    if (is_file($shortcodes_path . '/' . $tag . '.php')) {
      ob_start();
      include $shortcodes_path . '/' . $tag . '.php';
      return ob_get_clean();
    }*/

    $block_html = $self('blocks', $tag, $content_variable);
    if ($block_html !== false) {
      return $block_html;
    }

    return '';
//    return 'unknown tag: "' . $tag . '"';
  }, $text);
}
	
	
	
protected function parseFrontmatter(&$text_md) { 

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
         $this->options['frontmatter'][$group_prefix . $matches[1]] = $matches[3];
      }
    }
  }
  if (strncmp($text_md, "---", 3) === 0) {
    $endpos = strpos($text_md, '---', 3);
    $frontmatter = trim(substr($text_md, 3, $endpos - 3));
    $text_md = substr($text_md, $endpos + 3);

    $array = Spyc::YAMLLoadString($frontmatter);

    foreach ($array as $index => $item) {
       $this->options['frontmatter'][$index] = $item;
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
			
			'frontmatter' => array_merge([
						'theme' =>'basic-with-responsive-menu'					
					], $frontmatter),
		];
		$res = array_merge($o, $options);		
		$res['frontmatter']['theme'] = str_replace('//\\', '__INVLID__', $res['frontmatter']['theme'] );
		$res['content-dir'] = str_replace('.', '__INVLID__', $res['content-dir']);
		return $res;
	}

	
}
