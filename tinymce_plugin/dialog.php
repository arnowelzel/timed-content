<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{#timed_content_dlg.window_title}</title>
<script type="text/javascript" src="/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
<script type="text/javascript" src="/wp-includes/js/tinymce/utils/mctabs.js"></script>
<script type="text/javascript" src="/wp-includes/js/jquery/jquery.js"></script>
<link rel="stylesheet" type="text/css" href="img/anytime.css" />
<script type="text/javascript" src="js/anytime.js"></script>
<script type="text/javascript" src="js/anytimetz.js"></script>
<script type="text/javascript" src="js/dialog.js"></script>
</head>
<body style="display: none">
<div class="tabs">
  <ul>
    <li id="client_tab" class="current"><span><a href="javascript:mcTabs.displayTab('client_tab','client_panel');generatePreview();" onmousedown="return false;">{#timed_content_dlg.client_tab}</a></span></li>
    <li id="server_tab"><span><a href="javascript:mcTabs.displayTab('server_tab','server_panel');" onmousedown="return false;">{#timed_content_dlg.server_tab}</a></span></li>
  </ul>
</div>
<div class="panel_wrapper">
  <div id="client_panel" class="panel current">
    <form name="TimedContentDialogClient" id="TimedContentDialogClient" onsubmit="TimedContentDialog.client_action();return false;" action="#">
      <p>{#timed_content_dlg.client_instruction}</p>
      <fieldset>
      <legend>
      <input name="do_client_show" type="checkbox" id="do_client_show" value="show" />
      {#timed_content_dlg.show} </legend>
      <p>{#timed_content_dlg.time}:
        <input id="client_show_minutes" name="client_show_minutes" type="text" class="text" size="2" disabled="disabled" />
        :
        <input id="client_show_seconds" name="client_show_seconds" type="text" class="text" size="2" disabled="disabled" />
      </p>
      <p>{#timed_content_dlg.fadein}:
        <input id="client_show_fade" name="client_show_fade" type="text" class="text" size="4" disabled="disabled" />
        <em>({#timed_content_dlg.optional})</em></p>
      </fieldset>
      <fieldset>
      <legend>
      <input name="do_client_hide" type="checkbox" id="do_client_hide" value="hide" />
      {#timed_content_dlg.hide} </legend>
      <p>{#timed_content_dlg.time}:
        <input id="client_hide_minutes" name="client_hide_minutes" type="text" class="text" size="2" disabled="disabled" />
        :
        <input id="client_hide_seconds" name="client_hide_seconds" type="text" class="text" size="2" disabled="disabled" />
      </p>
      <p>{#timed_content_dlg.fadeout}:
        <input id="client_hide_fade" name="client_hide_fade" type="text" class="text" size="4" disabled="disabled" />
        <em>({#timed_content_dlg.optional})</em></p>
      </fieldset>
      <div class="mceActionPanel">
        <input type="button" id="insert" name="insert" value="{#insert}" onclick="TimedContentDialog.client_action();" />
        <input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
      </div>
    </form>
  </div>
  <div id="server_panel" class="panel">
    <form name="TimedContentDialogServer" id="TimedContentDialogServer" onsubmit="TimedContentDialog.server_action();return false;" action="#">
      <p>{#timed_content_dlg.server_instruction}</p>
      <p>{#timed_content_dlg.server_debug}:
        <input name="server_debug" type="checkbox" id="server_debug" value="true" />
      <fieldset>
      <legend>
      <input name="do_server_show" type="checkbox" id="do_server_show" value="show" />
      {#timed_content_dlg.show} </legend>
      <p>{#timed_content_dlg.dt}:
        <input name="server_show_dt" type="text" disabled="disabled" class="text" id="server_show_dt" size="50" />
      </fieldset>
      <fieldset>
      <legend>
      <input name="do_server_hide" type="checkbox" id="do_server_hide" value="hide" />
      {#timed_content_dlg.hide} </legend>
      <p>{#timed_content_dlg.dt}:
        <input name="server_hide_dt" type="text" disabled="disabled" class="text" id="server_hide_dt" size="50" />
      </fieldset>
	  <p>{#timed_content_dlg.server_dt}: <?php echo date("Y-M-d H:i:s O"); ?></p>
	  <p>{#timed_content_dlg.server_tz}: <?php echo date("e"); ?></p>
      <div class="mceActionPanel">
        <input type="button" id="insert" name="insert" value="{#insert}" onclick="TimedContentDialog.server_action();" />
        <input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
      </div>
    </form>
  </div>
</div>
</body>
</html>
