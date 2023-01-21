<?php

class DocIndexer {
  
  static $links = null; 

  public static function index() {
    global $filters, $SITEURL;
    $links = array();
    $dir_handle = @opendir(GSDATAPAGESPATH) or die("Unable to open pages directory");
    while ($filename = readdir($dir_handle)) {
      if (strrpos($filename,'.xml') === strlen($filename)-4 && !is_dir(GSDATAPAGESPATH . $filename) ) {
        $pagedata = getXML(GSDATAPAGESPATH . $filename);
        $private = (string) $pagedata->private;
        if ($private == 'Y') continue;
        $content = stripslashes(htmlspecialchars_decode($pagedata->content));
        if (preg_match_all("/ href=\"([^\"]+\.(?:pdf))\"/", $content, $matches)) {
          $vetoed = false;
          $params = array((string) $pagedata->url, (string) @$pagedata->parent, 
                          preg_split('/\s*,\s*/', html_entity_decode(stripslashes(trim(@$pagedata->meta)), ENT_QUOTES, 'UTF-8')));
          foreach ($filters as $filter)  {
            if ($filter['filter'] == 'veto') {
              if (call_user_func_array($filter['function'], $params)) { $vetoed = true; break; }
            }
          }
          if ($vetoed) continue;
          foreach ($matches[1] as $link) {
            $link = html_entity_decode(htmlspecialchars_decode($link), ENT_QUOTES, 'UTF-8');
            if (in_array($link, $links)) continue;
            $file = substr($link,0,strlen($SITEURL)) == $SITEURL ? GSROOTPATH.substr($link,strlen($SITEURL)) : $link;
            $text = self::getAsText($file);
            if ($text === null) continue;
            $pos = strrpos($link,'/');
            $title = $pos === false ? $link : substr($link,$pos+1);
            $pubDate = strtotime((string) $pagedata->pubDate);
            $creDate = isset($pagedata->creDate) ? @strtotime((string) $pagedata->creDate) : $this->pubDate;
            i18n_search_index_item('doc:'.count($links), null, $creDate, $pubDate, array(), $title, $text);
            $links[] = $link;
          }
        }
      }
    }
    file_put_contents(GSDATAOTHERPATH . DOC_INDEX, implode("\n", $links));
  }

  public static function getDocumentItem($id, $language, $creationDate, $publicationDate, $score) {
    if (substr($id,0,4) != 'doc:') return null;
    if (!class_exists('I18nDocResultItem')) {
      doc_index_item_class();
    }
    if (self::$links === null) self::$links = file(GSDATAOTHERPATH . DOC_INDEX);
    $index = intval(substr($id,4));
    $item = new I18nDocResultItem($id, $language, $creationDate, $publicationDate, $score, self::$links[$index]);
    return $item;
  }
  
  private static function getAsText($file) {
    require_once(GSPLUGINPATH.'doc_index/pdf2text.class.php');
    $ext = substr($file,strrpos($file,'.'));
    if ($ext == '.pdf') {
      try {
        $a = new PDF2Text();
        $a->setFilename($file);
        $a->decodePDF();
        return $a->output(); 
      } catch (Exception $e) {
        return null;
      }
    } else if ($ext == '.txt') {
      return file_get_contents($file);      
    } else {
      return null;
    }
  }
  
}

function doc_index_item_class() {
  class I18nDocResultItem extends I18nSearchResultItem {
    protected $link;
    public function __construct($id,$language,$creDate,$pubDate,$score,$link) {
      parent::__construct($id,$language,$creDate,$pubDate,$score);
      $this->link = $link;
    }
    protected function get($name) {
      switch ($name) {
        case 'link': return $this->link; 
        case 'title': 
            $pos = strrpos($this->link,'/');
            return $pos === false ? $this->link : substr($this->link,$pos+1);
        default: return null; 
      }
    }
  }
}
