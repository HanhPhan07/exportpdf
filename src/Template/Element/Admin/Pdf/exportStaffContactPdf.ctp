<html>
<head>
  <style>
        .Pdfpage {
      border: 1px solid;
      color: black;
      background-color: #20c997;
      text-align: center;
      width: 70%;
      margin-left: 70px;
    }
    .div-divide {
      width: 2px;
      height: 15px;
      margin-left: 100px;
      background-color: black;
    }
    .divHR{
        margin-left: 30px;
        display: table;
    }
    .HR {
        display: table-cell;
        border: 1px solid black;
        padding: 10px;
        margin: 5px;
        width: auto;
        height: 50px;
        background-color: #FFCC99;
    }
    .spanHR {
      padding-left: 5px;
    }
    .divide {
      width:20px;
      color: black;
      border: 1px solid;
      margin-top: 0px;
    }
    .abc {
      width: 2px;
      height: 50px;
      margin-left: 100px;
      background-color: black;
      display: inline-block;
    }
    .staff {
      border: 1px solid black;
    }
    #container {
    width:100%;
    text-align:left;
    height: max-content;   
}
 
  #left {
    /* display: inline-block; */
      float:left;
      width:10%;
      border: 1px solid black;
      background-color: #FFCC99;
  }
  
  #center {
      display: inline-block;
      margin:0 auto;
      width:40%;
      border: 1px solid black;
  }
  
  #right {
    /* display: inline-block; */
      float:right;
      width:50%;
      /* border: 1px solid black; */
  }
  </style>
<head>
<body>
  <div class="Pdfpage">
      <h1>MR. KUBOTA 0906337420</h1>
  </div>
  <div class="div-divide"></div>
  <?php
  // dd (count($data['BO']['Staff']['Back Office']));
  echo "<div class='divHR'>";
  foreach($data['BO']['Staff']['Back Office'] as $key=>$value){
    //echo $key;
    echo '<p class="HR"><span class="spanHR">'. $value->StaffID .'</span><span class="spanHR">' . $value->StaffName .'</span><span class="spanHR">'. $value->TBLMStaff2->PhoneNumber .'</p>';
    if((count($data['BO']['Staff']['Back Office'])-1)!=$key){
      echo '<span><hr  class="divide"></span>';
    }
  }
  echo "</div>";
  foreach($data as $key=>$value){
    // dd($key);
    if($key!='BO'){
      //dd($key);
      if(count($data[$key]['Leader'])>1){
        foreach($data[$key]['Leader'] as $key1=>$value1){
          // echo '<div class="abc"></div>';
          echo '<div id="container" style="padding-bottom: 10px;">';
            echo '<div id="left">'.$key.'</div>';
            echo '<div id="center">';
              foreach($value1 as $key2=>$value2){
                // dd($value2->StaffID);
                echo '<p ><span class="spanHR">'. $value2->StaffID .'</span><span class="spanHR">' . $value2->StaffName .'</span><span class="spanHR">'. $value2->TBLMStaff2->PhoneNumber .'</p>' ;
              }
            echo '</div>'; 
            
        }
          foreach($data[$key]['Staff'] as $key3=>$value3){
              echo '<div id="right">';
              foreach($value3 as $key4=>$value4){
                // dd($value2->StaffID);
                echo '<p class="staff"><span class="spanHR">'. $value4->StaffID .'</span><span class="spanHR">' . $value4->StaffName .'</span><span class="spanHR">'. $value4->TBLMStaff2->PhoneNumber .'</p>' ;
              }
              echo '</div>';  
          }
          echo '</div>';
      }
      else{
        // echo '<div class="abc"></div><div class="team ">'.$key.'</div>';
        foreach($data[$key]['Leader'] as $key5=>$value5){
          // echo '<div class="abc"></div>';
          echo '<div id="container">';
            echo '<div id="left">'.$key.'</div>';
            echo '<div id="center">';
              foreach($value5 as $key6=>$value6){
                // dd($value2->StaffID);
                echo '<span class="spanHR">'. $value6->StaffID .'</span><span class="spanHR">' . $value6->StaffName .'</span><span class="spanHR">'. $value6->TBLMStaff2->PhoneNumber .'</span><br>' ;
              }
            echo '</div>'; 
            
        }
        foreach($data[$key]['Staff'] as $key7=>$value7){
            echo '<div id="right">';
            foreach($value7 as $key8=>$value8){
              // dd($value2->StaffID);
              echo '<p class="staff"><span class="spanHR">'. $value8->StaffID .'</span><span class="spanHR">' . $value8->StaffName .'</span><span class="spanHR">'. $value8->TBLMStaff2->PhoneNumber .'</p>' ;
            }
            echo '</div>';
          echo '</div>';
        }
      }
    } 
  }
  ?>
</body>
</html>
  
