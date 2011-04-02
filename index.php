<?php

// This should be a full HTTP URL and point to the top directory of your music
// directory. This is the folder where the music browser main script (index.php)
// is installed. This needs to end with a slash. The setting below should
// autodetect the correct setting for most common setups, if not, just replace it
// with a full URL.
//
// $SELF   = 'http://example.com:8080/path/to/music/';
$SELF   = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/';

// This should point to the music browser main script. Usually index.php
// $SELF   = 'http://example.com:8080/path/to/music/index.php';
$SCRIPT = $SELF.basename($_SERVER['SCRIPT_NAME']);

// What should be stripped at the beginning of directories and filenames for
// better sorting (case-insensitive)
$STRIP  = '(The|Der|Die|Das|El|La|Los|A)';


// No modifications below needed


if(isset($_REQUEST['p'])){
    m3u($_REQUEST['p']);
}else{
    html();
}

/**
 * Build the HTML, ommiting all header stuff when using XHR
 */
function html(){
    $dir = '';
    if(isset($_REQUEST['b'])) $dir = $_REQUEST['b'];

    header('Content-Type: text/html; charset=utf-8');

    if(!isset($_SERVER['HTTP_X_IUI'])){
        echo '<!DOCTYPE html>';
        echo '<html>';
        echo '<head>';
        echo '<meta charset="utf-8">';
        echo '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />';
        echo '<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />';

        echo '<title>Music Browser</title>';

        echo '<style type="text/css" media="screen">@import ".data/iui/iui.css";</style>';
        echo '<style type="text/css" media="screen">@import ".data/style.css";</style>';
        echo '<script type="application/x-javascript" src=".data/iui/iui.js"></script>';

        echo '</head>';
        echo '<body>';
        echo '<div class="toolbar"><a id="backButton" class="button" href="#"></a>';
        echo '<h1>Music Browser</h1></div>';
    }
    browse($dir);
    if(!isset($_SERVER['HTTP_X_IUI'])){
        echo '</body>';
        echo '</html>';
    }
}

/**
 * Build a simple M3U playlist
 */
function m3u($dir){
    global $SELF;

    $me  = dirname(__FILE__);
    $dir = str_replace('..','',$dir);
    $dir = str_replace('//','/',$dir);
    $dir = trim($dir,'/');

    $mp3s = m3ulist($me,$dir);
    sort($mp3s);

    header('Content-Type: audio/x-mpegurl; charset=utf-8');
    foreach($mp3s as $mp3){
        echo "$SELF/$mp3\n";
    }
}

/**
 * Recursively scan for mp3 files
 */
function m3ulist($me,$dir){
    $mp3s = array();

    $handle = opendir("$me/$dir");
    if(!$handle) return $mp3s;

    while (false !== ($file = readdir($handle))) {
        if($file[0] == '.') continue;

        if(is_dir("$me/$dir/$file")){
            $mp3s += m3ulist($me,"$dir/$file");
        }elseif(preg_match('/\.mp3$/i',$file)){
            $mp3s[] = "$dir/$file";
        }
    }
    return $mp3s;
}

/**
 * Create a sorted list of folders and mp3 files
 */
function browse($dir){
    global $SCRIPT;
    global $STRIP;

    $me  = dirname(__FILE__);
    $dir = str_replace('..','',$dir);
    $dir = str_replace('//','/',$dir);
    $dir = trim($dir,'/');


    $handle = opendir("$me/$dir");
    if(!$handle) return;

    $dirs = array();
    $mp3s = array();


    while (false !== ($file = readdir($handle))) {
        if($file[0] == '.') continue;

        $clean = preg_replace('/^'.$STRIP.'[\s_]+/i','',$file);
        $clean = preg_replace('/\.mp3$/i','',$clean);
        $clean = str_replace('_',' ',$clean);
        if(is_dir("$me/$dir/$file")){
            $dirs[$file] = $clean;
        }elseif(preg_match('/\.mp3$/i',$file)){
            $mp3s[$file] = $clean;
        }
    }

    asort($dirs);
    asort($mp3s);

    echo '<ul selected="true">';
    foreach($dirs as $d => $c){
        echo '<li class="folder">';
        echo '<a href="'.$SCRIPT.'?b='.rawurlencode("$dir/$d").'">'.htmlspecialchars($c).'</a>';

        echo '<a href="'.$SCRIPT.'?p='.rawurlencode("$dir/$d").'" target="_self" class="button">play</a>';

        echo '</li>';
    }

    foreach($mp3s as $d => $c){
        echo '<li class="mp3">';
        echo '<a href="'.$SCRIPT."$dir/$d".'" target="_self">'.htmlspecialchars($c).'</a>';
        echo '</li>';
    }

    echo '</ul>';
}

