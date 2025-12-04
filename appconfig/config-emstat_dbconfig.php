<?php
if (!defined("_DEFS_EMSTAT_"))
{
         define ("_DEFS_EMSTAT_", true);

         if (!defined("EMSTATUSER"))
                 define ("EMSTATUSER", "root");

         if (!defined("EMSTATPASSWD"))
                 define ("EMSTATPASSWD", "authentx_7");

         if (!defined("EMSTATHOST"))
                 define ("EMSTATHOST", "mysqldb");

         if (!defined("EMSTATDBASE"))
                 define ("EMSTATDBASE", "authentx_authentx_7");

         define ("EMSTAT_SORTCOL_CONTIME", 1);
         define ("EMSTAT_SORTCOL_EMNAME", 2);
         define ("EMSTAT_SORTCOL_EMAPPNAME", 3);
         define ("EMSTAT_SORTCOL_EMID", 4);
         define ("EMSTAT_SORTCOL_EMUSER", 5);
         define ("EMSTAT_SORTCOL_EMSTATUS", 6);
         define ("EMSTAT_SORTCOL_NLO", 7);
}
?>
