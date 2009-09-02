<?php
class Crypt {

	var $key = "";

	// constructor
	function Crypt($key) {
		if ($key == "")
			die("Crypt: No key given");
		$this->key = $key;
	}
	
	function encrypt($s)
	{
	  for($i=0;$i<=strlen($s);$i++)
	  $r.=substr(str_shuffle(md5($this->key)),($i % strlen(md5($this->key))),1).$s[$i];
	  for($i=1;$i<=strlen($r);$i++) $s[$i-1]=chr(ord($r[$i-1])+ord(substr(md5($this->key),($i % strlen(md5($this->key)))-1,1)));
	  return urlencode(base64_encode($s));
	}
	function decrypt($s)
	{
	  $s=base64_decode(urldecode($s));
	  for($i=1;$i<=strlen($s);$i++) $s[$i-1]=chr(ord($s[$i-1])-ord(substr(md5($this->key),($i % strlen(md5($this->key)))-1,1)));
	  for($i=1;$i<=strlen($s)-2;$i=$i+2) $r.=$s[$i];
	  return $r;
	}
}
?>