<table width="100%" cellspacing="0" cellpadding="0" border="0" nowrap="nowrap" scope="row" class="tabtable">
<tr height="33" valign="center" align="center">
<td>
<?php
// Determine user's tab display
print "<table width=\"".($ntabs*105)."\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" nowrap=\"nowrap\" scope=\"row\">\n";
print "<tr height=\"33\" valign=\"center\">\n";
print "<td width=\"105\" class=\"tabcell_on\"><span class=\"tabtext\">Appts</span></td>\n";
if ($tab_users)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-user.php\"><span class=\"tabtext\">Users</span></a></td>\n";
if ($tab_sites)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-sites.php\"><span class=\"tabtext\">Sites</span></a></td>\n";
if ($tab_ws)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-emws.php\"><span class=\"tabtext\">EMWS</span></a></td>\n";
if ($tab_holmaps)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-holmap.php\"><span class=\"tabtext\">Holidays</span></a></td>\n";
if ($tab_reports)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-reports.php\"><span class=\"tabtext\">Reports</span></a></td>\n";
if ($tab_invite)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-userinvite.php\"><span class=\"tabtext\">Invite</span></a></td>\n";
if ($tab_mailtmpl)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-mailtmpl.php\"><span class=\"tabtext\">Templates</span></a></td>\n";
if ($tab_repldash)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-repldash.php\"><span class=\"tabtext\">Replication</span></a></td>\n";
print "</tr>\n";
print "</table>\n";
?>
</td>
</tr>
<tr height="20">
<td valign="top" colspan="9">
<table width="100%" cellspacing="0" cellpadding="0" border="0" id="bartable">
<tr height="18" valign="center">
<td>&nbsp;</td>
</tr>
</table>
</td>
</tr>
</table>
<p/>
<table cellspacing="0" cellpadding="0" align="center" border="0" width="858">
<tr><td><img height="12" src="../appcore/images/logoslice_1.gif" width="200" border="0" /></td>
</tr>
<tr><td valign="top" background="../appcore/images/box_mtl_ctr.gif">
<table cellspacing="0" cellpadding="0" border="0" width="858">
<tr height="12"><td width="12" height="12"><img height="12" src="../appcore/images/box_mtl_topl.gif" width="12" /></td>
<td background="../appcore/images/box_mtl_top.gif"><img height="12" src="../appcore/images/logoslice_2.gif" width="188" border="0" /></td>
<td width="12"><img height="12" src="../appcore/images/box_mtl_topr.gif" width="12" /></td>
</tr>
<tr valign="top">
<td width="12" background="../appcore/images/box_mtl_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td align="middle" background="../appcore/images/bg_spacer.gif">
<table cellspacing="0" cellpadding="0" border="0" width="834">
<tr><td align="middle">
<table cellspacing="0" cellpadding="0" border="0" width="834">
<tr height="0"><td align="left" width="220"><img height="56" src="../appcore/images/logoslice_3.gif" width="188" border="0" /></td>
<td align="right">
<table cellspacing="0" cellpadding="0" border="0" width="610">
<tr>
<td align="left" width="450">
<table  cellspacing="0" cellpadding="0" border="0" width="450">
<tr height="28"><td valign="top"><span class="siteheading"><?php print SITEHEADING ?></span></td>
</tr><tr height="28"><td valign="top"><span class="nameheading"></span></td></tr>
</table>
</td>
<td align="right" width="160">
<table cellspacing="0" cellpadding="0" border="0" width="160">
<tr height="28" valign="middle">
<td align="middle" width="80"></td>
<td align="middle" width="40"></td>
<td align="middle" width="40"></td>
</tr>
<tr height="28" valign="middle">
<td align="middle"></td>
<td align="middle" colspan="2"><a href="vec-logout.php" title="Log off the system"><img src="../appcore/images/icon-btnlogoff.gif" width="75" height="24" border="0" onclick='return frmCheckDirty()' /></a></td>
</tr></table>
</td>
</tr></table>
</td></tr>
<tr height="8" valign="top">
<td></td>
<td></td>
</tr></table>
</td></tr>
<tr><td align="center" valign="bottom">
<table cellspacing="0" cellpadding="0" border="0" width="834">
<tr height="2"><td width="2" height="2"><img height="2" src="../appcore/images/bevel_topl.gif" width="2" /></td>
<td background="../appcore/images/bevel_top.gif" height="2"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_topr.gif" width="2" /></td>
</tr>
<tr><td width="2" background="../appcore/images/bevel_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td valign="center" align="left">
<table border="0" cellspacing="0" cellpadding="10" width="830" bgcolor="#ffffff">
<tr><td align="left" valign="top">
<table border="0" cellspacing="0" cellpadding="0" STYLE='table-layout:fixed' width="800" bgcolor="#ffffff">
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />

<tr height="1">
<td></td><td></td><td></td><td></td><td></td>
<td></td><td></td><td></td><td></td><td></td>
<td></td><td></td><td></td><td></td><td></td>
<td></td><td></td><td></td><td></td><td></td>
</tr>