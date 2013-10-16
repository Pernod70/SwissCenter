function initXMLHttpClient() {
  var xmlhttp;
  try {
    // Mozilla / Safari / IE7
    xmlhttp = new XMLHttpRequest();
  } catch (e) {
    // IE
    var XMLHTTP_IDS = new Array('MSXML2.XMLHTTP.6.0',
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

set_message = function(message) {
  var req = initXMLHttpClient();
  req.onreadystatechange = function() {
    if (req.readyState == 4) {
      if (req.status == 200) {
        document.getElementById('message').innerHTML = req.responseText;
      } else {
        alert('Loading Error: ['+req.status+'] '+req.statusText);
      }
    }
  };
  req.open('GET','config_themes.php?action=message&text='+message,true);
  req.send(null);
};

set_image = function(file_id) {
  var req = initXMLHttpClient();
  req.onreadystatechange = function() {
    if (req.readyState == 4) {
      if (req.status == 200) {
        document.getElementById('picturegui').innerHTML = req.responseText;
      } else {
        alert('Loading Error: ['+req.status+'] '+req.statusText);
      }
    }
  };
  if ( file_id == 'no_change' ) {
    file_id = window.file_id;
  }
  else if ( file_id != 'wait' ) {
    window.file_id = file_id;
  }
  req.open('GET','config_themes.php?action=thumbgui&file_id='+file_id+'&media_type='+window.media_type+'&flip='+window.flip+'&greyscale='+window.greyscale+'&use_synopsis='+window.use_synopsis+'&use_series='+window.use_series+'&show_banner='+window.show_banner+'&show_image='+window.show_image,true);
  req.send(null);
};

show_thumbs = function (title, media_type) {
  var req = initXMLHttpClient();
  req.onreadystatechange = function() {
    if (req.readyState == 4) {
      if (req.status == 200) {
        document.getElementById('thumbnails').innerHTML = req.responseText;
      } else {
        alert('Loading Error: ['+req.status+'] '+req.statusText);
      }
    }
  };
  window.title = title;
  req.open('GET','config_themes.php?action=showthumbs&title='+encodeURIComponent(title)+'&media_type='+media_type,true);
  req.send(null);
};

select_title = function (title, media_type) {
  set_message('');
  window.title = title;
  window.flip = 0;
  window.greyscale = 0;
  window.use_synopsis = 0;
  window.use_series = 0;
  window.show_banner = 0;
  window.show_image = 0;
  set_image('not_selected');
  show_thumbs(title, media_type);
};

config_inverse = function(value) {
  if (value == 1) { return 0; } else { return 1; }
};

config_gui_flip = function() {
  window.flip = config_inverse(window.flip);
  set_image('no_change');
};

config_gui_greyscale = function() {
  window.greyscale = config_inverse(window.greyscale);
  set_image('no_change');
};

save_theme_settings = function() {
  var req = initXMLHttpClient();
  req.onreadystatechange = function() {
    if (req.readyState == 4) {
      if (req.status == 200) {
        set_message('THEME_APPLY_OK');
        set_image('no_change');
        show_thumbs(window.title, window.media_type);
      } else {
        alert('Loading Error: ['+req.status+'] '+req.statusText);
      }
    }
  };
  set_image("wait");
  req.open('GET','config_themes.php?action=apply&file_id='+window.file_id+'&flip='+window.flip+'&greyscale='+window.greyscale+'&use_synopsis='+window.use_synopsis+'&use_series='+window.use_series+'&show_banner='+window.show_banner+'&show_image='+window.show_image,true);
  req.send(null);
};
