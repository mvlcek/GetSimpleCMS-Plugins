<?php

# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

define('DOC_INDEX', 'doc_index.txt'); 

# register plugin
register_plugin(
  $thisfile, 
  'Document Indexer',   
  '0.1.1',     
  'Martin Vlcek',
  'http://mvlcek.bplaced.net', 
  'Indexes (currently only PDF) documents (requires I18N Search)',
  '',
  ''  
);

add_action('search-index', 'doc_index'); 
add_filter('search-item','doc_index_item');

function doc_index() {
  require_once(GSPLUGINPATH.'doc_index/indexer.class.php');
  DocIndexer::index();
}

function doc_index_item($id, $language, $creationDate, $publicationDate, $score) {
  require_once(GSPLUGINPATH.'doc_index/indexer.class.php');
  return DocIndexer::getDocumentItem($id, $language, $creationDate, $publicationDate, $score);
}
