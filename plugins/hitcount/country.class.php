<?php

class HitcountCountries {

  public static function retrieve() {
    self::outputHeader();
    self::outputProgressParagraph();
    if (!isset($_GET['continue']) && file_exists(GSDATAOTHERPATH.'ip2country.txt')) {
      unlink(GSDATAOTHERPATH.'ip2country.txt');
    }
    if (file_exists(GSDATAOTHERPATH.'ip2country.csv')) {
      self::createIndex();
    } else if (file_exists(GSDATAOTHERPATH.'ip2country.zip')) {
      self::extractZip();
    } else {
      self::downloadZip();
    }
  }

  private static function downloadZip() {
    // download ip-to-country list
    self::outputProgress(sprintf(i18n('hitcount/DOWNLOADING_COUNTRIES',false),0));
    $target = fopen(GSDATAOTHERPATH.'ip2country.zip','w');
    $source = fopen(HITCOUNT_URL,'r');
    $bytes = 0;
    while (($s = fread($source, 1024*50))) {
      fwrite($target, $s);
      $bytes += strlen($s);
      self::outputProgress(sprintf(i18n('hitcount/DOWNLOADING_COUNTRIES',false),$bytes/1024));
    }
    fclose($source);
    fclose($target);
    if ($bytes > 0) {
      self::redirect('load.php?id=hitcount&download&continue');
    } else {
      unlink(GSDATAOTHERPATH.'ip2country-error.zip');
      rename(GSDATAOTHERPATH.'ip2country.zip', GSDATAOTHERPATH.'ip2country-error.zip');
      self::outputError(i18n('hitcount/CANT_DOWNLOAD_COUNTRIES',false));
    }
  }

  private static function extractZip() {
    // unzip ip-to-country list
    $f = fopen(GSDATAOTHERPATH.'ip2country.csv','w');
    $zip = zip_open(GSDATAOTHERPATH.'ip2country.zip');
    $extracted = false;
    if (is_resource($zip)) {
      self::outputProgress(sprintf(i18n('hitcount/EXTRACTING_COUNTRIES',false),0));
      while ($entry = zip_read($zip)) {
        if (preg_match('/^.*\.csv$/i',zip_entry_name($entry)) === 1) {
          zip_entry_open($zip, $entry);
          $bytes = 0;
          while (($s = zip_entry_read($entry,16384))) {
            fwrite($f, $s);
            $bytes += strlen($s);
            self::outputProgress(sprintf(i18n('hitcount/EXTRACTING_COUNTRIES',false),$bytes/1024));
          }
          zip_entry_close($entry);
          $extracted = true;
        }
      }
      zip_close($zip);
      fclose($f);
    }
    if ($extracted) {
      self::redirect('load.php?id=hitcount&download&continue');
    } else {
      unlink(GSDATAOTHERPATH.'ip2country.csv');
      unlink(GSDATAOTHERPATH.'ip2country-error.zip');
      rename(GSDATAOTHERPATH.'ip2country.zip', GSDATAOTHERPATH.'ip2country-error.zip');
      self::outputError(i18n('hitcount/CANT_EXTRACT_COUNTRIES',false));
    }
  }

  private static function createIndex() {
    // create index file and read countries
    $t = fopen(GSDATAOTHERPATH.'ip2country.txt','a');
    $f = fopen(GSDATAOTHERPATH.'ip2country.csv','r');
    $iIpFrom = defined('HITCOUNT_IPFROM_COLUMN') ? HITCOUNT_IPFROM_COLUMN : 0;
    $iIpTo = defined('HITCOUNT_IPTO_COLUMN') ? HITCOUNT_IPTO_COLUMN : 1;
    $iCountryCode = defined('HITCOUNT_COUNTRYCODE_COLUMN') ? HITCOUNT_COUNTRYCODE_COLUMN : 2;
    $iMax = max($iIpFrom,$iIpTo,$iCountryCode);
    $linelen = 70;
    $min = (int) (filesize(GSDATAOTHERPATH.'ip2country.txt') / $linelen);
    $max = $min + 50000;
    $num = 0;
    $added = 0;
    $finished = true;
    while (($line = fgetcsv($f)) !== false) {
      if ($num >= $max) {
        $finished = false;
        break;
      }
      if ($num >= $min) {
        if (($min + $added) % 1000 == 0) self::outputProgress(sprintf(i18n('hitcount/INDEXING_COUNTRIES',false),$min+$added));
        if (count($line) > $iMax) {
          $ipFrom = self::asIp6Hex($line[$iIpFrom]);
          $ipTo = self::asIp6Hex($line[$iIpTo]);
          $countryCode = $line[$iCountryCode];
          if (strlen($countryCode) <= 2) {
            fprintf($t, "%032s %032s %-2s\r\n", $ipFrom, $ipTo, $countryCode);
            $added++;
          }
        }
      }
      $num++;
    }
    fclose($f);
    fclose($t);
    if (!$finished) {
      self::redirect('load.php?id=hitcount&download&continue');
    } else if ($min + $added > 100) {
      self::createCountries();
      unlink(GSDATAOTHERPATH.'ip2country.zip');
      unlink(GSDATAOTHERPATH.'ip2country.csv');
      self::outputSuccess(sprintf(i18n('hitcount/SUCCESS_COUNTRIES', false),$min+$added));
    } else {
      // there is certainly something wrong, if there are less than 100 IP/country mappings
      unlink(GSDATAOTHERPATH.'ip2country.txt');
      unlink(GSDATAOTHERPATH.'ip2country-error.zip');
      rename(GSDATAOTHERPATH.'ip2country.zip', GSDATAOTHERPATH.'ip2country-error.zip');
      unlink(GSDATAOTHERPATH.'ip2country-error.csv');
      rename(GSDATAOTHERPATH.'ip2country.csv', GSDATAOTHERPATH.'ip2country-error.csv');
      self::outputError(i18n('hitcount/CANT_INDEX_COUNTRIES',false));
    }
  }

  private static function createCountries() {
    $countries = array();
    $f = fopen(GSDATAOTHERPATH.'ip2country.csv','r');
    $iCountryCode = defined('HITCOUNT_COUNTRYCODE_COLUMN') ? HITCOUNT_COUNTRYCODE_COLUMN : 2;
    $iCountryName = defined('HITCOUNT_COUNTRYNAME_COLUMN') ? HITCOUNT_COUNTRYNAME_COLUMN : 3;
    while (($line = fgetcsv($f)) !== false) {
      $countryCode = $line[$iCountryCode];
      $countryName = $line[$iCountryName];
      $countries[$countryCode] = $countryName;
    }
    ksort($countries);
    $c = fopen(GSDATAOTHERPATH.'countries.txt','w');
    foreach ($countries as $code => $name) {
      fwrite($c, "$code $name\r\n");
    }
    fclose($c);
  }

  private static function asIp6Hex($ipNumeric) {
    if (strlen($ipNumeric) <= 10) {
      // it is ip4 - convert to ip6
      return sprintf('ffff%08x', intval($ipNumeric));
    }
    return self::convert_base_10_to_16($ipNumeric);
  }

  private static function outputHeader() {
    echo '<h3 class="floated" style="float:left">';
    i18n('hitcount/DOWNLOAD_IP2COUNTRY');
    echo '</h3>';
    echo '<div class="clear" style="clear:both"></div>';
  }

  private static function outputProgressParagraph() {
    echo '<div class="notify" id="progress"><p></p></div>';
    flush();
  }

  private static function outputProgress($s) {
    echo '<script type="text/javascript">$("#progress").removeClass().addClass("notify").children().text('.json_encode($s).');</script>'."\r\n";
    flush();
  }

  private static function outputError($s) {
    echo '<script type="text/javascript">$("#progress").removeClass().addClass("error").children().text('.json_encode($s).');</script>'."\r\n";
    flush();
    i18n('hitcount/ERROR_COUNTRIES_HELP');
?>
    <p class="submitline">
      <a class="cancel" href="load.php?id=hitcount&amp;download"><?php i18n('hitcount/RETRY'); ?></a>
      &nbsp;
      <a class="cancel" href="load.php?id=hitcount"><?php i18n('hitcount/CANCEL'); ?></a>
    </p>
<?php
  }

  private static function outputSuccess($s) {
    echo '<script type="text/javascript">$("#progress").removeClass().addClass("updated").children().text('.json_encode($s).');</script>'."\r\n";
    flush();
    i18n('hitcount/SUCCESS_COUNTRIES_HELP');
?>
    <p class="submitline">
      <a class="cancel" href="load.php?id=hitcount&amp;reindex"><?php i18n('hitcount/REINDEX'); ?></a>
      &nbsp;
      <a class="cancel" href="load.php?id=hitcount"><?php i18n('hitcount/CONTINUE'); ?></a>
    </p>
<?php
  }

  private static function redirect($link) {
    echo '<script type="text/javascript">window.location = '.json_encode($link).';</script>';
    flush();
    die;
  }

  private static function convert_base_10_to_16($source_str) {
    $source = array();
    // convert to digit array with least significant digit first
    $source_len = strlen($source_str);
    for ($i = 0; $i < $source_len; $i++) {
      $c = ord($source_str[$i]);
      if ($c >= 48 && $c <= 57) array_unshift($source, $c - 48);
    }
    while (count($source) > 0 && $source[count($source) - 1] === 0) array_pop($source);
     $target = array();
     while (count($source) > 0) {
       // divide by $to
       $remainder = 0;
       for ($i = count($source) - 1; $i >= 0; $i--) {
         $d = ($remainder << 3) + ($remainder << 1) + $source[$i];
         $source[$i] = $d >> 4;
         $remainder = $d & 0x0f;
       }
       while (count($source) > 0 && $source[count($source) - 1] === 0) array_pop($source);
       // .. and push remainder
       array_push($target, $remainder);
     }
    $target_str = '';
    for ($i = count($target) - 1; $i >= 0; $i--) {
      $d = $target[$i];
      $target_str .= $d < 10 ? chr(48 + $d) : chr(87 + $d);
    }
    return $target_str;
  }


}