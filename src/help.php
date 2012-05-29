<html>
<h1>Help</h1>
<hr/>
<h2>Browser</h2>
<h3>WebGL</h3>
<a href="http://www.khronos.org/webgl/">
  <img src="images/webgl_200px.gif" alt="webgl_200px.gif" width="100">
</a>
<hr/>

<h3>Internet Explorer</h3>
<a href="http://windows.microsoft.com/ja-JP/internet-explorer/products/ie/home">Internet Explorer</a>
<hr/>

<h3>Firefox</h3>
<a href="http://mozilla.com/firefox/">Firefox</a>

<hr/>
<h3>Chrome</h3>
<a href="http://www.google.com/chrome/">Chrome</a>

<hr/>
<h3>Opera</h3>
<a href="http://www.opera.com/">Opera</a>

<hr/>
<h3>Safari</h3>
<a href="http://www.apple.com/safari/">Safari</a>
<?php 
//echo $_GET["latlng"];
//echo $_GET["YCent"];
//echo $_GET["Tens_Years"];
//echo $_GET["Years"];
//echo $_GET["Months"];
//echo $_GET["Days_Tens"];
//echo $_GET["Days"];
//echo $_GET["Hour_Tens"];
//echo $_GET["Hour"];
//echo $_GET["Min_Tens"];
//echo $_GET["Min"];
try{
   $db = new SQLite3("sqlite/igrf11.db");
   $results = $db->query("select * from test");
   while($row=$results->fetchArray()){
     print $row['0'];
     print $row['1'];
     print $row['2'];
     print $row['3'];
     print $row['4'];
   }
}catch(PDOExcaption $e){
   print("SQLite Connection error<br/>");
   print $e->getTraceAsString();
}

$db->close();
?>
</html>
