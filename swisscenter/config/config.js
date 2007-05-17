
menu_status = new Array();
var last_expanded = '';

function showHide(id)
{
  var obj = document.getElementById(id);
  var status = obj.className;

  if (status == 'hide') 
  {
    if (last_expanded != '') 
    {
      var last_obj = document.getElementById(last_expanded);
      last_obj.className = 'hide';
    }

    obj.className = 'show';
    last_expanded = id;
  } 
  else 
  {
    obj.className = 'hide';
  }
}
