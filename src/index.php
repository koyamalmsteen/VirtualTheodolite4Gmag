<!DOCTYPE html>
<html lang="ja">

  <head>
    <meta charset="UTF-8">
    <title>Virtual Theodolite for Geomag.</title>

    <link rel="stylesheet" href="css/vtg.css" type="text/css">
<!--
    <script type="text/javascript" src="GLGE/src/core/glge.js"></script>
    <script type="text/javascript" src="GLGE/src/core/glge_math.js"></script>
    <script type="text/javascript" src="GLGE/src/extra/glge_input.js"></script>
    <script type="text/javascript" src="GLGE/src/extra/glge_collada.js"></script>
-->
   <script type="text/javascript" src="GLGE/glge-compiled.js"></script>

<!-- My Home -->
<!--
    <script src="http://www.google.com/jsapi?key=ABQIAAAAS4qBG7DJ-yv0nk8H-hNt3xTR_OUNfNue5CMVlWm4iYNeevMKnBReu-Hi2K9bsIUaxstChsl9Wm9d2g" type="text/javascript"></script>
-->

<!-- Office -->
    <script src="http://www.google.com/jsapi?key=ABQIAAAAS4qBG7DJ-yv0nk8H-hNt3xQzzjFwKq0yoBO0UvcHRHBPiRdznRTL9F5Syb27p8A-0mMfudaRF4vDEw" type="text/javascript"></script>
<!-- Server -->
<!--
    <script src="http://www.google.com/jsapi?key=ABQIAAAAS4qBG7DJ-yv0nk8H-hNt3xTNXMhtga44bMHcdBNiXv7cEhcBpBRnPD7Wa-feKe1MJIx7zNJe_Ljxdg" type="text/javascript"></script>
-->

    <script type="text/javascript">
      var geMaster, geSlave;
      var laMaster, laSlave;
      var camMaster, camSlave;
      var geocoder;
      
      // Nijyo Castle (35.012800, 135.751257)
      var initLat = <? if( $_GET['latlng']== NULL ){ echo "35.012800"; }else{ echo substr($_GET['latlng'],0,strpos($_GET['latlng'],','));}?>;
      var initLng = <? if( $_GET['latlng']== NULL ){ echo "135.751257"; }else{ echo substr($_GET['latlng'],strpos($_GET['latlng'],',')+1,strlen($_GET['latlng']));}?>;

      google.load("earth","1"); 
      google.load("maps","2");

      function webGLStart(){
        var canvas = document.getElementById("canvas");
        initGL(canvas);
        initShaders();
        initBuffers();
      
//        gl.clearColor(0.0, 0.0, 0.0, 1.0);
//        gl.enable(gl.DEPTH_TEST);

        drawScene();
      }

      function init() {
        var syncActive = false;
      
        google.earth.createInstance('geMaster', initCBMaster, failureCB);
        google.earth.createInstance('geSlave', initCBSlave, failureCB);
      }

      function initCBMaster(instance) {
        geMaster = instance;
        geMaster.getWindow().setVisibility(true);
        geMaster.getLayerRoot().enableLayerById(geMaster.LAYER_BUILDINGS, true);
        geMaster.getOptions().setAtmosphereVisibility(false); // Atmoshere->OFF
//        var hrefMaster ="http://192.168.9.5/vtg/ScreenOverlay.kml";
        var hrefMaster ="http://10.226.89.152/vtg/ScreenOverlay.kml";
        google.earth.fetchKml(geMaster, hrefMaster, function(kmlObjectMaster) {
          if(kmlObjectMaster){
            geMaster.getFeatures().appendChild(kmlObjectMaster);
          }
        }
      );

      initGeoWhizMaster(initLat, initLng);

      syncSlave();
      if (!syncActive) {
        google.earth.addEventListener(geMaster.getView(),'viewchange',syncSlave);
        syncActive = true;}
      }

      function initGL(canvas){
        try{
          gl = canvas.getContext("experimental-webgl");
          gl.viewportWidth = canvas.width;
          gl.viewportHeight = canvas.height;
        }catch(e){
        }
        if(!gl){
          alert("Could not initialise WebGL");
        }
      }

      function drawScene(){
        gl.viewport(0, 0, gl.viewportWidth, gl.viewportHeight);
        gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);
        mat4.perspective(45, gl.viewportWidth/gl.viewportHeight, 0.1, 100.0, pMatrix);
        mat4.identity(mvMatrix);
      }
      

      function tick(){
        requestAnimFrame(tick);
        handleKeys();
        drawScene();
        animate();
      }

      function initCBSlave(instance) {
        geSlave = instance;
        geSlave.getWindow().setVisibility(true);
        geSlave.getLayerRoot().enableLayerById(geMaster.LAYER_BUILDINGS, true);
        geSlave.getOptions().setAtmosphereVisibility(false); // Atmoshere->OFF
        geSlave.getOptions().setOverviewMapVisibility(true); // Overview->ON

//        var hrefSlave = "http://192.168.0.5/vtg/models/vtg.kml";
        var hrefSlave = "http://10.226.89.152/vtg/models/vtg.kml";
        google.earth.fetchKml(geSlave, hrefSlave, function(kmlObjectSlave) {
          if(kmlObjectSlave){
            geSlave.getFeatures().appendChild(kmlObjectSlave);
          }
        });
        initGeoWhizSlave(initLat, initLng);
        camSlave = geSlave.createCamera('');
        syncSlave();
        if (!syncActive && geMaster){
          google.earth.addEventListener(geMaster.getView(),'viewchange', syncSlave);
          syncActive = true;

<!--
   full
-->
         createFullScreenIcon();
         createNormalScreenIcon();

         google.earth.addEventListener(geSlave.getWindow(),"click",handleMouseClick);
<!--
   fullend
-->
         }
      }

      function failureCB(errorCode) {
      }

      function syncSlave() {
        if ( geMaster && geSlave && laMaster && laSlave ){
          laMaster = geMaster.getView().copyAsLookAt(geMaster.ALTITUDE_RELATIVE_TO_GROUND);
	  laSlave.set(-laMaster.getLatitude(),180,
	              laMaster.getAltitude(), laMaster.getAltitudeMode(),
		      laMaster.getHeading(), laMaster.getTilt(),
		      laMaster.getRange());
          geSlave.getView().setAbstractView(laSlave);
	}
      }

      function el(e) {
        return document.getElementById(e);
      }

      function submitLocation_and_Time() {
        var address = el('latlng').value;
        geocoder.getLatLng(address,
          function(point){
            laMaster.set(point.y, point.x, 1.5, geMaster.ALTITUDE_RELATIVE_TO_GROUND, 0, 90, 99.99);
	    laSlave.set(point.y, point.x, 1.5, geMaster.ALTITUDE_RELATIVE_TO_GROUND, 0, 90, 99.99);
            geMaster.getView().setAbstractView(laMaster);
            geSlave.getView().setAbstractView(laSlave);
	  }
        );
      }

      google.setOnLoadCallback(init);

      function initGeoWhizMaster(lat, lng) {
        // Set the initial view
        laMaster = geMaster.createLookAt('');
        laMaster.set(lat, lng, 1.5, geMaster.ALTITUDE_RELATIVE_TO_GROUND, 0, 90, 99.99); 

	camMaster = geMaster.getView().copyAsCamera(geMaster.ALTITUDE_RELATIVE_TO_GROUND);
	camMaster.setLatitude(lat);
	camMaster.setLongitude(lng);
	camMaster.setAltitude(1.5);
	camMaster.setHeading(0);
        camMaster.setTilt(90);
	camMaster.setRoll(0);
        geMaster.getView().setAbstractView(camMaster);

        window.createReticle();
      }

      function initGeoWhizSlave(lat, lng) {
        // Set the initial view
        laSlave = geSlave.createLookAt('');
        laSlave.set(lat, lng, 1.5, geSlave.ALTITUDE_RELATIVE_TO_GROUND, 0, 90, 99.99);
        geSlave.getView().setAbstractView(laSlave);
        window.createReticle();
      }
    </script>

    <!-- Basic Sample: Fullscreen -->
    <script>
      function handleMouseClick(event){
      var INSET_PIXELS_X = document.getElementById("content").offsetWidth - event.getClientX();
      var INSET_PIXELS_Y = event.getClientY();
      if (INSET_PIXELS_X<32){
      if(INSET_PIXELS_Y <32 ){ toggleFullScreen();}			  
      }
      }
      
      function toggleFullScreen(){
      if( fullScreenState == true){makeNormalScreen();}
      else{makeFullScreen();}
      }

      function makeFullScreen(){
      var samplecontainer = document.getElemntById('top_right_bottom');
      var container = document.getElementById('content');
      container.style.left = 0;
      container.style.top = 0;
      container.style.width = samplecontainer.offsetWidth +'px';
      contaner.style.hegiht = samplecontainer.offsetHeight + 'px';
      fullScreenState = true;
      noFullScreenIcon.setVisibility(fullScreenState);
      fullScreenIcon.setVisibility(!fullScreenState);
      }

      function makeNormalScreen(){
      var samplecontainer = document.getElementById('sizecontainer');
      var container = document.getElementById('container');
      container.style.left = samplecontainer.style.left;
      container.sytle.top = samplecontainer.style.top;
      container.style.width = samplecontainer.offsetWidth + 'px';
      container.style.height = samplecontainer.offsetHeight + 'px';
      fullScreenState = false;
      noFullScreenIcon.setVisibility(fullScreenState);
      fullScreenIcon.setVisibility(!fullScreenState);
      }

      function createFullScreenIcon(){
      // create an image for the screen overlay
      var icon = geSlave.createIcon('');
      icon.setHref('http://earth-api-samples.googlecode.com/svn/trunk/external/dinther_fullscreen_tofull.png');
      // create the screen overlay
      fullScreenIcon = geSlave.createScreenOverlay('');
      fullScreenIcon.setDrawOrder(60);
      fullScreenIcon.setIcon(icon);
      // anchor point in top left of icon.
      fullScreenIcon.getScreenXY().setXUnits(geSlave.UNITS_FRACTION);
      fullScreenIcon.getScreenXY().setYUnits(geSlave.UNITS_FRACTION);
      fullScreenIcon.getScreenXY().setX(1);
      fullScreenIcon.getScreenXY().setY(1);
      // place icon in top left of screen.
      fullScreenIcon.getOverlayXY().setXUnits(geSlave.UNITS_INSET_PIXELS);
      fullScreenIcon.getOverlayXY().setYUnits(geSlave.UNITS_INSET_PIXELS);
      fullScreenIcon.getOverlayXY().setX(2);
      fullScreenIcon.getOverlayXY().setY(4);
      // Set icon size.
      fullScreenIcon.getSize().setXUnits(geSlave.UNITS_PIXELS);
      fullScreenIcon.getSize().setYUnits(geSlave.UNITS_PIXELS);
      fullScreenIcon.getSize().setX(32);
      fullScreenIcon.getSize().setY(32);
      // add the screen overlay to Earth
      geSlave.getFeatures().appendChild(fullScreenIcon);
      }

      function createNormalScreenIcon(){
      // create an image for the screen overlay
      var icon = geSlave.createIcon('');
      icon.setHref('http://earth-api-samples.googlecode.com/svn/trunk/external/dinther_fullscreen_tonormal.png');
      // create the screen overlay
      noFullScreenIcon = geSlave.createScreenOverlay('');
      noFullScreenIcon.setDrawOrder(62);
      noFullScreenIcon.setIcon(icon);
      // anchor point in top left of icon.
      noFullScreenIcon.getScreenXY().setXUnits(geSlave.UNITS_FRACTION);
      noFullScreenIcon.getScreenXY().setYUnits(geSlave.UNITS_FRACTION);
      noFullScreenIcon.getScreenXY().setX(1);
      noFullScreenIcon.getScreenXY().setY(1);
      // place icon in top right of screen.
      noFullScreenIcon.getOverlay().setXUnits(geSlave.UNITS_INSET_PIXELS);
      noFullScreenIcon.getOverlay().setYUnits(geSlave.UNITS_INSET_PIXELS);
      noFullScreenIcon.getOverlay().setX(2);
      noFullScreenIcon.getOverlay().setY(4);
      // Set icon size.
      noFullScreenIcon.getSize().setXUnits(geSlave.UNITS_PIXELS);
      noFullScreenIcon.getSize().setYUnits(geSlave.UNITS_PIXELS);
      noFullScreenIcon.getSize().setX(32);
      noFullScreenIcon.getSize().setY(32);
      // add the screen overlay to Earth
      geSlave.getFeature().appendChild(noFullScreenIcon);
      }

      function handleFullScreen(){
      if ( window.innerWidth == screen.width){
      if ( window.innerHeight > screen.height - 10){
      // this is likely caused by pressing F11 on the browser
      makeFullScreen();
      } else if (fullScreenState == true) { makeNormalScreen();}
      } else {makeNormalScreen();}
      }
    </script>

    <!-- Theodolite -->
    <script id="shader-fs" type="x-shader/x-fragment"> 
      varying vec2 vTextureCoord;
 
      uniform sampler2D uSampler;
 
      void main(void) {
      gl_FragColor = texture2D(uSampler, vec2(vTextureCoord.s, vTextureCoord.t));
      }
    </script> 
 
    <script id="shader-vs" type="x-shader/x-vertex"> 
      attribute vec3 aVertexPosition;
      attribute vec2 aTextureCoord;
 
      uniform mat4 uMVMatrix;
      uniform mat4 uPMatrix;
      
      varying vec2 vTextureCoord;
 
      void main(void) {
      gl_Position = uPMatrix * uMVMatrix * vec4(aVertexPosition, 1.0);
      vTextureCoord = aTextureCoord;
      }
    </script> 

       <script type="text/javascript">
	 var doc = new GLGE.Document();
	 doc.onLoad=function(){
	 //create the renderer
	 var gameRenderer=new GLGE.Renderer(document.getElementById('canvas'));
	 gameScene=new GLGE.Scene();
	 gameScene=doc.getElement("mainscene");
	 gameRenderer.setScene(gameScene);
	 
	 var mouse=new GLGE.MouseInput(document.getElementById('canvas'));
	 var keys=new GLGE.KeyInput();
	 var mouseovercanvas;
	 var hoverobj;

	 function mouselook(){
	 if(mouseovercanvas){
         var mousepos=mouse.getMousePosition();
         mousepos.x=mousepos.x-document.getElementById("container").offsetLeft;
         mousepos.y=mousepos.y-document.getElementById("container").offsetTop;
         var camera=gameScene.camera;
         camerarot=camera.getRotation();
         inc=(mousepos.y-(document.getElementById('canvas').offsetHeight/2))/500;
	 //      var trans=camera.getRotMatrix().x([0,0,-1,1]);
         var trans=GLGE.mulMat4Vec4(camera.getRotMatrix(),[0,0,-1,1]);
         var mag=Math.pow(Math.pow(trans[0],2)+Math.pow(trans[1],2),0.5);
         trans[0]=trans[0]/mag;
         trans[1]=trans[1]/mag;
         camera.setRotX(1.56-trans[1]*inc);
         camera.setRotZ(-trans[0]*inc);
         var width=document.getElementById('canvas').offsetWidth;
         if(mousepos.x<width*0.3){
			 var turn=Math.pow((mousepos.x-width*0.3)/(width*0.3),2)*0.005*(now-lasttime);
			 camera.setRotY(camerarot.y+turn);
			 }
			 if(mousepos.x>width*0.7){
           var turn=Math.pow((mousepos.x-width*0.7)/(width*0.3),2)*0.005*(now-lasttime);
           camera.setRotY(camerarot.y-turn);
           }
	   }
	   }

	   function checkkeys(){
	   var camera=gameScene.camera;
	   camerapos=camera.getPosition();
	   camerarot=camera.getRotation();
	   var mat=camera.getRotMatrix();
	   //      var trans=mat.x([0,0,-1]);
	   var trans=GLGE.mulMat4Vec4(mat,[0,0,-1,1]);
	   var mag=Math.pow(Math.pow(trans[0],2)+Math.pow(trans[1],2),0.5);
	   trans[0]=trans[0]/mag;
	   trans[1]=trans[1]/mag;
	   var yinc=0;
	   var xinc=0;
	   if(keys.isKeyPressed(GLGE.KI_M)) {
           addduck();
	   }
	   if(keys.isKeyPressed(GLGE.KI_W)) {
           yinc=yinc+parseFloat(trans[1]);
           xinc=xinc+parseFloat(trans[0]);

	   document.getElementById("az").innerHTML=yinc*100;
	   camMaster.setTilt(yinc);
	   camSlave.setTilt(yinc);
	   geMaster.getView().setAbstractView(camMaster);
	   geSlave.getView().setAbstractView(camSlave);
	   }
	   if(keys.isKeyPressed(GLGE.KI_S)) {
           yinc=yinc-parseFloat(trans[1]);
           xinc=xinc-parseFloat(trans[0]);

	   document.getElementById("az").innerHTML=-yinc*100;
	   camMaster.setTilt(yinc);
	   camSlave.setTilt(yinc);
	   geMaster.getView().setAbstractView(camMaster);
	   geSlave.getView().setAbstractView(camSlave);
	   }
	   if(keys.isKeyPressed(GLGE.KI_A)) {
           yinc=yinc+parseFloat(trans[0]);
           xinc=xinc-parseFloat(trans[1]);
	   
	   document.getElementById("el").innerHTML=xinc*100;
	   camMaster.setTilt(xinc);
	   camSlave.setTilt(xinc);
	   geMaster.getView().setAbstractView(camMaster);
	   geSlave.getView().setAbstractView(camSlave);
	   }
	   if(keys.isKeyPressed(GLGE.KI_D)) {
           yinc=yinc-parseFloat(trans[0]);
           xinc=xinc+parseFloat(trans[1]);
	   
	   document.getElementById("el").innerHTML=-xinc*100;
	   camMaster.setTilt(xinc);
	   camSlave.setTilt(xinc*100);
	   geMaster.getView().setAbstractView(camMaster);
	   geSlave.getView().setAbstractView(camSlave);
	   }
	   if(levelmap.getHeightAt(camerapos.x+xinc,camerapos.y)>30) xinc=0;
    if(levelmap.getHeightAt(camerapos.x,camerapos.y+yinc)>30) yinc=0;
    if(levelmap.getHeightAt(camerapos.x+xinc,camerapos.y+yinc)>30){
    yinc=0;xinc=0;
    }else{
    camera.setLocZ(levelmap.getHeightAt(camerapos.x+xinc,camerapos.y+yinc)+8);
    }
    if(xinc!=0 || yinc!=0){
    camera.setLocY(camerapos.y+yinc*0.05*(now-lasttime));camera.setLocX(camerapos.x+xinc*0.05*(now-lasttime));
    }
    }

//    levelmap=new GLGE.HeightMap("http://192.168.0.5/GLGE/examples/collada/images/map.png",120,120,-50,50,-50,50,0,50);
    levelmap=new GLGE.HeightMap("http://10.226.89.152/GLGE/examples/collada/images/map.png",120,120,-50,50,-50,50,0,50);

    var lasttime=0;
    var frameratebuffer=60;
    start=parseInt(new Date().getTime());
    var now;
    function render(){
    now=parseInt(new Date().getTime());
    frameratebuffer=Math.round(((frameratebuffer*9)+1000/(now-lasttime))/10);
    //      document.getElementById("debug").innerHTML="Frame Rate:"+frameratebuffer;
    mouselook();
    checkkeys();
    gameRenderer.render();
    lasttime=now;
    }
    setInterval(render,1);
    var inc=0.2;
    document.getElementById("canvas").onmouseover=function(e){mouseovercanvas=true;}
    document.getElementById("canvas").onmouseout=function(e){mouseovercanvas=false;}
    }
//    doc.load("http://192.168.0.5/vtg/level.xml");
    doc.load("http://10.226.89.152/vtg/level.xml");
</script>

  </head>


  <body onload="webGLStart()">
  
    <div id="contents">
      <div id="maincontents">

	<div id="top">
	  <div id="top_left">
            <canvas id="canvas"></canvas>
	  </div>
	  <div id="top_right">
            <div id="top_right_top">
              <div id="geMaster"></div>
            </div>
            <div id="top_right_bottom">
              <div id="geSlave"></div>
            </div>
	  </div>
	</div>

	<div id="bottom">
          <form name="latlng" onSubmit="return submitLocation_and_Time()">
            <div id="bottom_left">
              <table id="location">
		<tr>
		  <td>Geographic Location</td>
		</tr>
		<tr>
		  <td>
<?
if( $_GET['latlng']==NULL ){ 
?>
                    <input type="text" id="latlng" size="18" value="35.012800,135.751257" title="Input Latitude &amp; Lngitude [deg.] / name of the place." name="latlng">
<?
}else{
?>
                    <input type="text" id="latlng" size="18" value="<? echo $_GET['latlng']; ?>" title="Input Latitude &amp; Lngitude [deg.] / name of the place." name="latlng">
<?
}
?>
<a href="javascript:w=window.open('help2.php','','scrollbars=yes,Width=800,Height=600');w.focus();">?</a>
		  </td>
		</tr>
              </table>
            </div>
	    
            <div id="wrapper">
              <div id="bottom_center">
                <table id="year">
                  <tr>
		    <td>
                      <select name="YCent">
                        <option value="19" <? if( $_GET['YCent']==19 )print "selected" ?> >1900</option>
                        <option value="20" <? if( $_GET['YCent']==20 || $_GET['YCent']=="" )print "selected" ?> >2000</option>
                      </select>
		    </td>
		    <td>+</td>
		    <td> 
                      <select name="Tens_Years">
                        <option value="0" <? if( $_GET['Tens_Years']==0 )print "selected" ?> >00</option>
                        <option value="1" <? if( $_GET['Tens_Years']==1 || $_GET['Tens_Years']=="" )print "selected" ?> >10</option>
                        <option value="2" <? if( $_GET['Tens_Years']==2 )print "selected" ?> >20</option>
                        <option value="3" <? if( $_GET['Tens_Years']==3 )print "selected" ?> >30</option>
                        <option value="4" <? if( $_GET['Tens_Years']==4 )print "selected" ?> >40</option>
                        <option value="5" <? if( $_GET['Tens_Years']==5 )print "selected" ?> >50</option>
			<option value="6" <? if( $_GET['Tens_Years']==6 )print "selected" ?> >60</option>
			<option value="7" <? if( $_GET['Tens_Years']==7 )print "selected" ?> >70</option>
			<option value="8" <? if( $_GET['Tens_Years']==8 )print "selected" ?> >80</option>
			<option value="9" <? if( $_GET['Tens_Years']==9 )print "selected" ?> >90</option>
                      </select>
		    </td>
		    <td>+</td>
		    <td>
                      <select name="Years">
			<option value="0" <? if( $_GET['Years']==0 )print "selected" ?>>0</option>
			<option value="1" <? if( $_GET['Years']==1 || $_GET['Years']=="" )print "selected" ?> >1</option>
			<option value="2" <? if( $_GET['Years']==2 )print "selected" ?>>2</option>
			<option value="3" <? if( $_GET['Years']==3 )print "selected" ?>>3</option>
			<option value="4" <? if( $_GET['Years']==4 )print "selected" ?>>4</option>
			<option value="5" <? if( $_GET['Years']==5 )print "selected" ?>>5</option>
			<option value="6" <? if( $_GET['Years']==6 )print "selected" ?>>6</option>
			<option value="7" <? if( $_GET['Years']==7 )print "selected" ?>>7</option>
			<option value="8" <? if( $_GET['Years']==8 )print "selected" ?>>8</option>
			<option value="9" <? if( $_GET['Years']==9 )print "selected" ?>>9</option>
                      </select>
		    </td>
		  </tr>
		</table>
		
		<table id="date">
		  <tr>
		    <td>
                      <select name="Months">
			<option value="Jan" <? if( $_GET['Months']=="Jan" || $_GET['Months']=="" )print "selected" ?> >Jan</option>
			<option value="Feb" <? if( $_GET['Months']=="Feb" )print "selected" ?>>Feb</option>
			<option value="Mar" <? if( $_GET['Months']=="Mar" )print "selected" ?>>Mar</option>
			<option value="Apr" <? if( $_GET['Months']=="Apr" )print "selected" ?>>Apr</option>
			<option value="May" <? if( $_GET['Months']=="May" )print "selected" ?>>May</option>
			<option value="Jun" <? if( $_GET['Months']=="Jun" )print "selected" ?>>Jun</option>
			<option value="Jul" <? if( $_GET['Months']=="Jul" )print "selected" ?>>Jul</option>
			<option value="Aug" <? if( $_GET['Months']=="Aug" )print "selected" ?>>Aug</option>
			<option value="Sep" <? if( $_GET['Months']=="Sep" )print "selected" ?>>Sep</option>
			<option value="Oct" <? if( $_GET['Months']=="Oct" )print "selected" ?>>Oct</option>
			<option value="Nov" <? if( $_GET['Months']=="Nov" )print "selected" ?>>Nov</option>
			<option value="Dec" <? if( $_GET['Months']=="Dec" )print "selected" ?>>Dec</option>
		      </select>
		    </td>
		    <td>/</td>
		    <td>
		      <select name="Days_Tens">
			<option value="0" <? if( $_GET['Days_Tens']==0 || $_GET['Days_Tens']=="" )print "selected" ?> >00</option>
			<option value="1" <? if( $_GET['Days_Tens']==1 )print "selected" ?>>10</option>
			<option value="2" <? if( $_GET['Days_Tens']==2 )print "selected" ?>>20</option>
			<option value="3" <? if( $_GET['Days_Tens']==3 )print "selected" ?>>30</option>
		      </select>
		    </td>
		    <td>+</td>
		    <td>
		      <select name="Days">
			<option value="0" <? if( $_GET['Days']==0 )print "selected" ?>>0</option>
			<option value="1" <? if( $_GET['Days']==1 || $_GET['Days']=="" )print "selected" ?> >1</option>
			<option value="2" <? if( $_GET['Days']==2 )print "selected" ?>>2</option>
			<option value="3" <? if( $_GET['Days']==3 )print "selected" ?>>3</option>
			<option value="4" <? if( $_GET['Days']==4 )print "selected" ?>>4</option>
			<option value="5" <? if( $_GET['Days']==5 )print "selected" ?>>5</option>
			<option value="6" <? if( $_GET['Days']==6 )print "selected" ?>>6</option>
			<option value="7" <? if( $_GET['Days']==7 )print "selected" ?>>7</option>
			<option value="8" <? if( $_GET['Days']==8 )print "selected" ?>>8</option>
			<option value="9" <? if( $_GET['Days']==9 )print "selected" ?>>9</option>
		      </select>
		    </td>
		  </tr>
		</table>
		
		<table id="time">
		  <tr>
		    <td>
		      UT: 
		    </td>
		    <td>
		      <select name="Hour_Tens">
			<option value="0" <? if( $_GET['Hour_Tens']==0 || $_GET['Hour_Tens']=="" ) print "selected" ?> >00</option>
			<option value="1" <? if( $_GET['Hour_Tens']==1 )print "selected" ?>>10</option>
			<option value="2" <? if( $_GET['Hour_Tens']==2 )print "selected" ?>>20</option>
		      </select>
		    </td>
		    <td>+</td> 
		    <td> 
		      <select name="Hour">
			<option value="0" <? if( $_GET['Hour']==0 || $_GET['Hour']=="" )print "selected" ?> >0</option>
			<option value="1" <? if( $_GET['Hour']==1 )print "selected" ?>>1</option>
			<option value="2" <? if( $_GET['Hour']==2 )print "selected" ?>>2</option>
			<option value="3" <? if( $_GET['Hour']==3 )print "selected" ?>>3</option>
			<option value="4" <? if( $_GET['Hour']==4 )print "selected" ?>>4</option>
			<option value="5" <? if( $_GET['Hour']==5 )print "selected" ?>>5</option>
			<option value="6" <? if( $_GET['Hour']==6 )print "selected" ?>>6</option>
			<option value="7" <? if( $_GET['Hour']==7 )print "selected" ?>>7</option>
			<option value="8" <? if( $_GET['Hour']==8 )print "selected" ?>>8</option>
			<option value="9" <? if( $_GET['Hour']==9 )print "selected" ?>>9</option>
		      </select>
		    </td>
		    <td>:</td>
		    <td>
		      <select name="Min_Tens">
			<option value="0" <? if( $_GET['Min_Tens']==0 || $_GET['Min_Tens']=="" )print "selected" ?> >00</option>
			<option value="1" <? if( $_GET['Min_Tens']==1 )print "selected" ?>>10</option>
			<option value="2" <? if( $_GET['Min_Tens']==2 )print "selected" ?>>20</option> 
			<option value="3" <? if( $_GET['Min_Tens']==3 )print "selected" ?>>30</option> 
			<option value="4" <? if( $_GET['Min_Tens']==4 )print "selected" ?>>40</option> 
			<option value="5" <? if( $_GET['Min_Tens']==5 )print "selected" ?>>50</option> 
		      </select>
		    </td>
		    <td>+</td>
		    <td> 
		      <select name="Min">
			<option value="0" <? if( $_GET['Min']==0 || $_GET['Min']=="" )print "selected" ?> >0</option>
			<option value="1" <? if( $_GET['Min']==1 )print "selected" ?>>1</option>
			<option value="2" <? if( $_GET['Min']==2 )print "selected" ?>>2</option>
			<option value="3" <? if( $_GET['Min']==3 )print "selected" ?>>3</option>
			<option value="4" <? if( $_GET['Min']==4 )print "selected" ?>>4</option>
			<option value="5" <? if( $_GET['Min']==5 )print "selected" ?>>5</option>
			<option value="6" <? if( $_GET['Min']==6 )print "selected" ?>>6</option>
			<option value="7" <? if( $_GET['Min']==7 )print "selected" ?>>7</option>
			<option value="8" <? if( $_GET['Min']==8 )print "selected" ?>>8</option>
			<option value="9" <? if( $_GET['Min']==9 )print "selected" ?>>9</option>
                      </select>
		    </td>
		  </tr>
		</table>
              </div>
	      
              <div id="bottom_right">
		<input type="submit" value="Submit"/>
<?
try{
   $db = new SQLite3("igrf11/igrf11.db");
   $query = "select date from igrf11 where date=".$_GET['YCent'].$_GET['Tens_Years'].$_GET['Years'];
   $results = $db->query($query);
   while($row=$results->fetchArray()){
     print $row['0'];
     print $row['1'];
     print $row['2'];
   }
}catch(PDOExcaption $e){
   print("SQLite Connection error<br/>");
   print $e->getTraceAsString();
}

$db->close();
?>
              </div>
	    </div>
          </form>
	</div>
      </div>
      
      <div id="menubar">
	<div id="menubar_top">
	  <div id="vtg_banner">
<!--            <a href="http://192.168.0.5/vtg/"> -->
              <a href="http://10.226.89.152/vtg/">
<!--            <a href="http://10.226.89.152/~ykoyama/vtg/"> -->
	      <img src="images/vtg_banner2.png" alt="vtg_banner2.png" width="100">
            </a>
	  </div>
	  <div id="help_banner"> 
            <a href="javascript:window.open('help.php','help','width=350,heigh=400');">
	      <img src="images/vtg_banner.png" alt="vtg_banner.png" width="100">
            </a>
	  </div>
	</div>
	
	<div id="menubar_bottom">
          <div id="bunner">
            <a href="http://www.iugonet.org/en/">
	      <img src="images/renkei_logo_150.png" alt="renkei_logo_150.png" width="100" >
            </a>
            <a href="http://wdc.kugi.kyoto-u.ac.jp/index.html">
	      <img src="images/logoh.gif" alt="logoh.gif" width="100" >
            </a>
            <a href="http://validator.w3.org/check?uri=refer">
	      <img src="images/valid-xhtml10.png" alt="valid-xhtml10.png">
            </a>
            <a href="http://jigsaw.w3.org/css-validator/check/referer">
	      <img style="border:0;width:88px;height:31px"
		   src="http://jigsaw.w3.org/css-validator/images/vcss-blue"
		   alt="valid css!" />
            </a>
	  </div>
	</div>
      </div>
    </div>
  </body>

</html>
