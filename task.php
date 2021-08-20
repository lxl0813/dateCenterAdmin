<?php

$ch = curl_init(); 
curl_setopt ($ch, CURLOPT_URL, "http://datacenter.shjlit.com/FormulaTask/formula_task_plan"); 
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10); 
$content = curl_exec($ch); 
return $content; 