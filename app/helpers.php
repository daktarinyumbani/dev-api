<?php

function generate_report_number($report_format, $number, $digit)
{
    return $report_format . STR_PAD((string) $number, $digit, "0", STR_PAD_LEFT);
}

function group_by($key,$data){
  $result=array();
  foreach($data as $val)
  {
    if(array_key_exists($key,$val)){
    $result[$val[$key]][]=$val;
    }else{
        $result[''][]=$val;
    }
  }

  return $result;
}