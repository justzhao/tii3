<?php
//条形码生成器
function EAN_13($code) { 
  //一个单元的宽度 
  $lw = 2; 
  //条码高  
  $hi = 100; 
  // the guide code is no coding,is used to show the left part coding type// 
  // Array guide is used to record the EAN_13 is left part coding type// 
  $Guide = array(1=>'AAAAAA','AABABB','AABBAB','ABAABB','ABBAAB','ABBBAA','ABABAB','ABABBA','ABBABA'); 
  $Lstart ='101'; 
  $Lencode = array("A" => array('0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'), 
                   "B" => array('0100111','0110011','0011011','0100001','0011101','0111001','0000101','0010001','0001001','0010111')); 
  $Rencode = array('1110010','1100110','1101100','1000010','1011100', 
                   '1001110','1010000','1000100','1001000','1110100');     
    
  $center = '01010'; 
   
  $ends = '101'; 
  if ( strlen($code) != 13 ) 
   { die("UPC-A Must be 13 digits."); } 
$lsum =0; 
$rsum =0; 
  for($i=0;$i<(strlen($code)-1);$i++) 
  { 
    if($i % 2) 
{ 
 // $odd += $ncode[$x] 
  $lsum +=(int)$code[$i]; 
 }else{ 
  $rsum +=(int)$code[$i]; 
 } 
   
  } 
  $tsum = $lsum*3 + $rsum; 
    if($code[12] != (10-($tsum % 10))) 
{ 
   die("the code is bad!"); 
    }  

 // echo $Guide[$code[0]]; 
  $barcode = $Lstart; 
  for($i=1;$i<=6;$i++) 
  { 
    $barcode .= $Lencode [$Guide[$code[0]][($i-1)]] [$code[$i]]; 
  } 
  $barcode .= $center; 
   
  for($i=7;$i<13;$i++) 
  { 
    $barcode .= $Rencode[$code[($i)]] ; 
  } 
  $barcode .= $ends; 
   
    $img = ImageCreate($lw*95+60,$hi+30); 
  $fg = ImageColorAllocate($img, 0, 0, 0); 
  $bg = ImageColorAllocate($img, 255, 255, 255); 
  ImageFilledRectangle($img, 0, 0, $lw*95+60, $hi+30, $bg); 
  $shift=10; 
  for ($x=0;$x<strlen($barcode);$x++) { 
    if (($x<4) || ($x>=45 && $x<50) || ($x >=92))  
  {  
    $sh=10;  
  } else {  
    $sh=0;  
  } 
    if ($barcode[$x] == '1')  
{  
  $color = $fg; 
    } else {  
  $color = $bg;  
} 
    ImageFilledRectangle($img, ($x*$lw)+30,5,($x+1)*$lw+29,$hi+5+$sh,$color); 
  } 
  /* Add the Human Readable Label */ 
  ImageString($img,5,20,$hi+5,$code[0],$fg); 
  for ($x=0;$x<6;$x++) { 
    ImageString($img,5,$lw*(8+$x*6)+30,$hi+5,$code[$x+1],$fg); 
    ImageString($img,5,$lw*(53+$x*6)+30,$hi+5,$code[$x+7],$fg); 
  } 
 // ImageString($img,4,$lw*95+17,$hi-5,$code[12],$fg); 
  /* Output the Header and Content. */ 
  header("Content-Type: image/png"); 
  ImagePNG($img); 
    
} 
$code= $_GET[code]?$_GET[code]:$_POST[code];
EAN_13($code); 

?>  　　