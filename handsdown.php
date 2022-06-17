<?php
namespace Webfan\App\Frdlweb;


$page = $_GET['page'];
if ($page == '') {
  $page = 'index';
}

if (is_dir('pages/' . $page)) {
  $page .= '/index';
}
 
$cms = new CMS();
$result = $cms('pages', $page, 'hmmm');
if ($result !== FALSE) {
  echo $result;
}else{
  header("HTTP/1.0 404 Not Found");
  echo $cms('pages', '404', '');
} 
