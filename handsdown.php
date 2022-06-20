<?php
namespace Webfan\App\Frdlweb;
 
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
  

//$page = $_GET['page'];
$u = explode('?', $_SERVER['REQUEST_URI']);

$page = ltrim(array_shift($u), '/ ');
if ($page == '') {
  $page = 'index';
}

if (is_dir('pages/' . $page)) {
  $page .= '/index';
}
 
$cms = new CMS();
$result = $cms('pages', $page, '');
if ($result !== FALSE) {
  echo $result;
}
else {
  header("HTTP/1.0 404 Not Found");
  echo $cms('pages', '404', '');
}



 
