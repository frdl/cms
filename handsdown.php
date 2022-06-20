<?php
namespace Webfan\App\Frdlweb;

use League\CommonMark\CommonMarkConverter;
use Spyc;
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
  

//$page = $_GET['page'];
$page = ltrim($_SERVER['REQUEST_URI'], '/ ');
if ($page == '') {
  $page = 'index';
}

if (is_dir('pages/' . $page)) {
  $page .= '/index';
}

//$result = find_and_parse_md_or_php_file('pages', $page, 'hmmm');
$cms = new CMS();
$result = $cms('pages', $page, 'hmmm');
if ($result !== FALSE) {
  echo $result;
}
else {
  header("HTTP/1.0 404 Not Found");
  echo $cms('pages', '404', '');
}



 
