<?php


  //Data Type - 0=ignore, 1=boolean
  function TraverseTrie(&$trie, $keySoFar, &$handler=null, $isString=true, $defaultValue="", $dataType=0)
  {
    $default = ($defaultValue != "" ? ($isString ? "\"" . $defaultValue . "\"" : $defaultValue) : ($isString ? "\"\"" : "0"));
    $depth = strlen($keySoFar);
    if(is_array($trie))
    {
      fwrite($handler, "switch(\$cardID[" . $depth . "]) {\r\n");
      foreach ($trie as $key => $value)
      {
        fwrite($handler, "case \"" . $key . "\":\r\n");
        TraverseTrie($trie[$key], $keySoFar . $key, $handler, $isString, $defaultValue, $dataType);
      }
      fwrite($handler, "default: return " . $default . ";\r\n");
      fwrite($handler, "}\r\n");
    }
    else
    {
      if($handler != null)
      {
        if($dataType == 1) fwrite($handler, "return true;//" . $trie . "\r\n");
        else if($isString) fwrite($handler, "return \"" . $trie . "\";\r\n");
        else fwrite($handler, "return " . $trie . ";\r\n");
      }
    }
  }

  function AddToTrie(&$trie, $cardID, $depth, $value)
  {
    if($depth < strlen($cardID)-1)
    {
      if(!array_key_exists($cardID[$depth], $trie)) $trie[$cardID[$depth]] = [];
      AddToTrie($trie[$cardID[$depth]], $cardID, $depth+1, $value);
    }
    else if(!isset($trie[$cardID[$depth]])) $trie[$cardID[$depth]] = $value;
  }

?>
