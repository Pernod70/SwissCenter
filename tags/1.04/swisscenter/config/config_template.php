<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
?>

<html>
<head>
</head>
<body style="margin:20px;" background="../images/bgr.png">
<style>
  .title               {   font-family       : sans-serif;
                           font-size         : 20px;
                           font-weight       : bold;
                           color             : #000000;
                           margin-left       : 10px; }
  
  h1                   {   font-family       : sans-serif;
                           font-size         : 18px;
                           text-align        : center;
                           font-weight       : bold;
                           padding-top       : 10px;
                           color             : #ff8800; }
                    
  h2                   {   font-family       : sans-serif;
                           font-size         : 14px;
                           text-align        : center;
                           font-weight       : bold;
                           padding-top       : 10px;
                           color             : #ff4444; }
  
  tr, p, div            {  font-family       : Verdana, Arial, Helvetica, sans-serif;
                           font-size         : 11px; }
  
  hr                    {  background        : #999999;
                           height            : 1px;
                           width             : 100%;  }
  
.menu a                 {  color             : #ffffff;
                           padding-left      : 15px;
                           text-decoration   : none; }

.menu a:hover           {  color             : #000000; }
  
.stdformreq             {  color             : #000000; }
.stdform                {  color             : #999999; }
.stdformlabel           {  color             : #dd4400; }
.stdformlabel em        {  color             : #992200; }

.form_select_tab        { margin             : 0px;
                          padding            : 0px; }
                          
.form_select_tab th     { background         : #ffbb88;
                          text-align         : left;
                          font-size          : 11px; }
                          
.form_select_tab tr     { background         : #ffffff; }

.message                { color              : #000000;
                          background         : #bbffbb;
                          padding            : 2px;
                          text-align         : center;
                          font-weight        : bold; }

.warning                { color              : #000000;
                          background         : #ffbbbb;
                          padding            : 2px;
                          text-align         : center;
                          font-weight        : bold; }
                          
</style>
<table width="750px" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="eeeeee">
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
        <p><font class="title">SwissCenter Configuration Utility</font></p>
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
      <td width="170" valign="top" bgcolor="#eeeeee">
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

<?
  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
