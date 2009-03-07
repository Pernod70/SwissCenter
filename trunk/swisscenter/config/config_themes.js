function initXMLHttpClient() {
  var xmlhttp;
  try {
    // Mozilla / Safari / IE7
    xmlhttp = new XMLHttpRequest();
  } catch (e) {
    // IE
    var XMLHTTP_IDS = new Array('MSXML2.XMLHTTP.5.0',
                                'MSXML2.XMLHTTP.4.0',
                                'MSXML2.XMLHTTP.3.0',
                                'MSXML2.XMLHTTP',
                                'Microsoft.XMLHTTP');
    var success = false;
    for (var i=0;i < XMLHTTP_IDS.length && !success; i++) {
      try {
        xmlhttp = new ActiveXObject(XMLHTTP_IDS[i]);
        success = true;
      } catch (e) {}
    }
    if (!success) {
      throw new Error('Unable to create XMLHttpRequest.');
    }
  }
  return xmlhttp;
}

select_title = function (value) {
  var req = initXMLHttpClient();
  req.onreadystatechange = function() {
    if (req.readyState == 4) {
      if (req.status == 200) {
        document.getElementById('picturechooser').innerHTML = req.responseText;
      } else {
        alert('Loading Error: ['+req.status+'] ' +req.statusText);
      }
    }
  }
  window.title=value;
  req.open('GET','config_themes.php?action=showthumbs&title='+value,true);
  req.send(null);
}

set_image = function(file_id) {
  var req = initXMLHttpClient();
  req.onreadystatechange = function() {
    if (req.readyState == 4) {
      if (req.status == 200) {
        document.getElementById('picturegui').innerHTML = req.responseText;
      } else {
        alert('Loading Error: ['+req.status+'] ' +req.statusText);
      }
    }
  }
  if(file_id=='no_change') {
    file_id=window.file_id;
  }
  else if (file_id!='pause') {
    window.file_id=file_id;
  }
  req.open('GET','config_themes.php?action=thumbgui&file_id='+file_id+'&media_type='+window.media_type+'&flip='+window.flip+'&greyscale='+window.greyscale+'&use_synopsis='+window.use_synopsis+'&use_series='+window.use_series+'&show_banner='+window.show_banner+'&show_image='+window.show_image,true);
  req.send(null);
}

config_inverse = function(value) {
  if (value==1) { return 0 } else { return 1 }
}

config_gui_flip = function() {
  window.flip=config_inverse(window.flip);
  set_image('no_change');
}

config_gui_greyscale = function() {
  window.greyscale=config_inverse(window.greyscale);
  set_image('no_change');
}

config_write_to_db = function() {
  var req = initXMLHttpClient();
  req.onreadystatechange = function() {
    if (req.readyState == 4) {
      if (req.status == 200) {
        req.open('GET','config_themes.php?action=apply&file_id='+file_id+'&flip='+window.flip+'&greyscale='+window.greyscale+'&use_synopsis='+window.use_synopsis+'&use_series='+window.use_series+'&show_banner='+window.show_banner+'&show_image='+window.show_image,true);
        req.send(null);
        set_image('no_change');
        select_title(window.title);
      } else {
        alert('Loading Error: ['+req.status+'] ' +req.statusText);
      }
    }
  }
  set_image("pause");
  req.open('GET','config_themes.php?action=apply&file_id='+file_id+'&flip='+window.flip+'&greyscale='+window.greyscale+'&use_synopsis='+window.use_synopsis+'&use_series='+window.use_series+'&show_banner='+window.show_banner+'&show_image='+window.show_image,true);
  req.send(null);
}
