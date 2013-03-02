<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
?>

<html>
<head>
  <meta http-equiv="content-type" content="text/html;charset=<?php echo get_sys_pref('CONFIG_PAGE_CHARSET','utf-8') ?>" />
  <title>SwissCenter Config</title>
  <link rel="stylesheet" type="text/css" media="screen" href="sdmenu.css" />
  <link rel="stylesheet" type="text/css" media="screen" href="config.css" />
  <link rel="stylesheet" type="text/css" media="screen" href="slider.css" />
  <script type="text/javascript" defer="defer">
  function handleClick (name, state)
  {
    var Input=document.getElementsByName(name);
    for( var i=0; i<Input.length; i++)
    {
      Input[i].checked=state;
    }
  }
  </script>
  <script type="text/javascript" src="jquery.js"></script>
  <script type="text/javascript" src="slider.js"></script>
  <script type="text/javascript" src="sdmenu.js"></script>
  <script type="text/javascript">
    var myMenu;
    window.onload = function()
    {
      myMenu = new SDMenu("my_menu");
      myMenu.speed = 2;                     // Menu sliding speed (1 - 5 recomended)
      myMenu.remember = true;               // Store menu states (expanded or collapsed) in cookie and restore later
      myMenu.oneSmOnly = false;             // One expanded submenu at a time
      myMenu.markCurrent = true;            // Mark current link / page (link.href == location.href)
      myMenu.init();
    };

    $(document).ready(function() {
      <?php inject_javascript(); ?>
    });
  </script>
</head>
<body style="margin:20px;" background="../images/bgr.png">

<table width="95%" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="eeeeee">
<tr>
  <td width="6" bgcolor="#FFFFFF">
  <img src="../images/dot.gif" width="1" height="1" alt="spacer" />
  </td>
  <td valign="top" bgcolor="#eeeeee">
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
      <td bgcolor="#FFFFFF">
      <img src="../images/dot.gif" width="1" height="10" alt="spacer" />
      </td>
    </tr>
    <tr height="50">
      <td valign="middle">
        <p><font class="title"><?php echo $page_title; ?></font></p>
      </td>
    </tr>
    <tr>
      <td bgcolor="#FFFFFF" height="10">
        <img src="../images/dot.gif" width="1" height="10" alt="spacer" />
      </td>
    </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td width="180" valign="top" bgcolor="#eeeeee">
      <img src="../images/dot.gif" width="170" height="1">
      &nbsp;<br/>
       <?php display_menu(); ?>
      &nbsp;<br/>
      </td>
      <td width="6" bgcolor="#FFFFFF">&nbsp;</td>
      <td valign="top">
       <?php display_content(); ?>
       <br/>
       &nbsp;
      </td>
    </tr>
    </table>

    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
      <td bgcolor="#FFFFFF">
        <img src="../images/dot.gif" width="1" height="10" alt="spacer" />
      </td>
    </tr>
    </table>
  </td>
  <td width="6" bgcolor="#FFFFFF">
    <img src="../images/dot.gif" width="1" height="10" alt="spacer"/>
  </td>
</tr>
</table>
</body>
</html>

<?php
  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
